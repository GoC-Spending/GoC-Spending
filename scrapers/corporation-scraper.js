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

/**
 * Login it to receive session credentials
 *
 * @returns {Promise<CookieJar>}
 */
function login () {
  console.log('Login')
  const jar = request.jar()
  return request.get('https://www.ic.gc.ca/app/ccc/srch/', {jar})
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
  const status = load.sync('status.json')
  const offset = status.offset
  const hitsPerPage = status.hitsPerPage
  console.log('Get details | offset:', offset)

  const formData = {
    'searchCriteriaBean.textField': '*',
    'searchCriteriaBean.column': 'nm',
    'prtl': 1,
    'V_SEARCH.docsStart': offset,
    'searchCriteriaBean.hitsPerPage': hitsPerPage,
    'searchCriteriaBean.sortSpec': 'title asc',
    'searchCriteriaBean.conceptOperator': 'and',
    'searchCriteriaBean.isSummaryOn': 'N'
  }
  return request.post('https://www.ic.gc.ca/app/ccc/srch/srch.do', {formData, jar})
    .then(details => { return {details, jar} })
}

/**
 * Parse available links from details page
 *
 * @param {CookieJar} jar
 * @param {HTML} details
 * @returns {Object, jar} links {name: <href>}
 */
function parseLinks ({jar, details}) {
  console.log('Parsing links')

  const results = {}
  const links = findLinks(details)
  for (let {href, name} of links) {
    if (!fs.existsSync(path.join(__dirname, 'corporations', name + '.html'))) {
      results[name] = href
    }
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
function getCorporations ({links, jar}) {
  console.log('Get corporations:', Object.keys(links).length)

  const q = d3.queue(25)
  for (const [name, href] of entries(links)) {
    q.defer(callback => {
      request.get('https://www.ic.gc.ca/app/ccc/srch/' + href, {jar}).then(details => {
        // Parse title to check for errors
        const title = cheerio.load(details)('title').text().trim()
        if (title.match(/Error/i)) {
          console.log(chalk.bgRed.white('Error:', name))
          callback(new Error('error in title'))
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
      const status = load.sync('status.json')
      status.offset = status.offset + status.hitsPerPage
      write.sync('status.json', status)
      main()
    } else {
      // Restart main app without adding any offset
      console.log(chalk.bgRed.white(errors))
      main()
    }
  })
}

function main () {
  if (!fs.existsSync('status.json')) {
    write.sync('status.json', {offset: 0})
  }
  login()
    .then(getDetails)
    .then(parseLinks)
    .then(getCorporations)
}

main()
