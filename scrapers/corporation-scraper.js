const fs = require('fs')
const path = require('path')
const request = require('request-promise')
const entries = require('lodash').entries
const d3 = require('d3-queue')
const chalk = require('chalk')
const cheerio = require('cheerio')
const unidecode = require('unidecode')
const write = require('write-json-file')
const load = require('load-json-file')

const headers = {
  'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
  'Host': 'www.ic.gc.ca',
  'Referer': 'https://www.ic.gc.ca/app/ccc/srch/'
}
const timeout = 10000
const statusPath = path.join(__dirname, 'status.json')

/**
 * Login it to receive session credentials
 *
 * @param {CookieJar} jar
 * @returns {Promise<CookieJar>}
 */
function login (jar) {
  if (jar) {
    console.log('Login | reusing CookieJar')
    return new Promise(resolve => resolve(jar))
  }

  console.log('Login | retrieve new CookieJar')
  jar = request.jar()
  return request.get('https://www.ic.gc.ca', {headers, jar})
    .then(() => jar)
}

/**
 * HTTP Post - Get Details
 *
 * @param {CookieJar} jar
 * @param {number} [offset=0] Pagination offset
 * @returns {Promise<CookieJar, data>}
 */
function getDetails (jar) {
  const status = load.sync(statusPath)
  const offset = (status.offset === 0) ? 1 : status.offset
  const hitsPerPage = status.hitsPerPage
  console.log('Get details | offset:', offset)

  const qs = {
    lang: 'eng',
    profileId: '',
    'prtl': 1,
    'V_SEARCH.docsStart': offset,
    'searchCriteriaBean.textField': '*',
    'searchCriteriaBean.column': 'nm',
    'searchCriteriaBean.hitsPerPage': hitsPerPage,
    'searchCriteriaBean.sortSpec': 'title asc',
    'searchCriteriaBean.conceptOperator': 'and',
    'searchCriteriaBean.isSummaryOn': 'Y',
    'searchCriteriaBean.isExportingOrInterested': 'exportingActively',
    'searchCriteriaBean.companyName': '',
    'searchCriteriaBean.province': '',
    'searchCriteriaBean.city': '',
    'searchCriteriaBean.postalCode': '',
    'searchCriteriaBean.companyProfile': '',
    'searchCriteriaBean.naicsCodeText': '',
    'searchCriteriaBean.product': '',
    'searchCriteriaBean.primaryBusinessActivity': '',
    'searchCriteriaBean.numberOfEmployees': '',
    'searchCriteriaBean.totalSales': '',
    'searchCriteriaBean.exportSales': '',
    'searchCriteriaBean.isExporter': '',
    'searchCriteriaBean.exportingCountry': '',
    'searchCriteriaBean.exportingState': '',
    'searchCriteriaBean.nsnCode': '',
    'searchCriteriaBean.fscCode': '',
    'searchCriteriaBean.niinCode': '',
    'searchCriteriaBean.marketInterest': '',
    'searchCriteriaBean.cageCode': '',
    'searchCriteriaBean.jcoCode': '',
    'searchCriteriaBean.dunNumber': '',
    'searchCriteriaBean.dunSuffix': '',
    'sbmtBtn': ''
  }
  return request.get('https://www.ic.gc.ca/app/ccc/srch/srch.do', {headers, qs, jar, timeout})
    .then(details => { return {details, jar} })
}

/**
 * Parse available links from details page
 *
 * @param {CookieJar} jar
 * @param {HTML} details
 * @returns {Object, jar} links {name: <href>}
 */
function parseLinks ({jar, details} = {}) {
  const total = details.match(/Canadian Company Capabilities \((\d+)\)/)[1]
  console.log('Parsing links | total: ' + total)

  const results = {}
  const links = findLinks(details)
  for (let {href, name} of links) {
    if (!fs.existsSync(path.join(__dirname, 'corporations', name + '.html'))) {
      results[name] = href
    }
  }
  // Scraper is finished
  if (links.length === 0) {
    const status = load.sync(statusPath)
    status.offset = 0
    write.sync(statusPath, status)
    console.log(chalk.bgRed.white('Scraper stoped, no more results'))
    process.exit(1)
  }
  return new Promise(resolve => {
    console.log('Found links:', links.length)
    resolve({links: results, jar})
  })
}

/**
 * Find Links
 *
 * @param {HTML} details
 * @returns {Array<Object>} {href, name}
 */
function findLinks (details) {
  const $ = cheerio.load(details)
  const links = []
  $('a').each((index, link) => {
    const href = link.attribs.href
    if (href.match(/docsCount/)) {
      const name = cleanName(link.childNodes[0].data)
      links.push({href, name})
    }
  })
  return links
}

/**
 * Clean name
 *
 * @param {string} name
 * @returns {string} clean name
 */
function cleanName (name) {
  name = name.trim()
  name = name.replace(/\//g, '-').replace(/\./g, '')
  name = unidecode(name)
  name = name.toUpperCase()
  return name
}

/**
 * Get Corporation
 *
 * @param {Object} links {name: <href>}
 * @param {CookieJar} jar
 */
function getCorporations ({links, jar} = {}) {
  console.log('Get corporations:', Object.keys(links).length)

  const q = d3.queue(1)
  for (const [name, href] of entries(links)) {
    q.defer(callback => {
      request.get('https://www.ic.gc.ca/app/ccc/srch/' + href, {headers, jar, timeout}).then(details => {
        // Parse title to check for errors
        const title = cheerio.load(details)('title').text().trim()
        if (title.match(/Error/i) || details.match(/End Footer/) === null) {
          console.log(chalk.bgRed.white('Error:', name))
          callback(new Error('error in html'))
        } else {
          console.log(chalk.bgGreen.black('Saving HTML:', name))
          fs.writeFileSync(path.join(__dirname, 'corporations', name + '.html'), details)
          callback(null)
        }
      }, error => {
        if (error) {
          console.log(chalk.bgRed.white(error))
          callback(new Error('http error'))
        }
      })
    })
  }
  q.awaitAll(errors => {
    if (!errors) {
      // Restart main application & add 25 to offset
      const status = load.sync(statusPath)
      status.offset = status.offset + status.hitsPerPage
      write.sync(statusPath, status)
      main(jar)
    } else {
      // Restart main app without adding any offset
      console.log(chalk.bgRed.white(errors))
      main()
    }
  })
}

function main (jar) {
  if (!fs.existsSync(statusPath)) {
    write.sync(statusPath, {offset: 0})
  }
  login(jar)
    .then(getDetails)
    .catch(() => main())
    .then(parseLinks)
    .then(getCorporations)
}

main()
