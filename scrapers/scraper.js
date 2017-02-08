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
  console.log('Get details')

  const offset = load.sync('status.json').offset
  console.log('Status offset:', offset)
  const formData = {
    'searchCriteriaBean.textField': '*',
    'searchCriteriaBean.column': 'nm',
    'prtl': 1,
    'V_SEARCH.docsStart': offset,
    'searchCriteriaBean.hitsPerPage': 25,
    'searchCriteriaBean.sortSpec': 'title asc',
    'searchCriteriaBean.isSummaryOn': 'N'
  }
  return request.post('https://www.ic.gc.ca/app/ccc/srch/srch.do', {formData, jar})
    .then(details => { return {details, jar} })
}

/**
 * Get available links from details page
 *
 * @param {CookieJar} jar
 * @param {HTML} details
 * @returns {Object, jar} links {name: <href>}
 */
function getLinks ({jar, details}) {
  console.log('Get links')

  const results = {}
  const html = cheerio.load(details)
  const links = html('ul.list-group.list-group-hover').find('a')
  links.map((index, element) => {
    if (element.children.length) {
      let name = element.children[0].data.trim()
      name = name.replace(/\//g, '-').replace('.', '')
      name = unidecode(name)
      name = name.toUpperCase()
      const href = element.attribs.href
      if (href.match(/nvgt.do/)) {
        if (!fs.existsSync(path.join(__dirname, 'corporations', name + '.html'))) {
          results[name] = href
        }
      }
    }
  })
  return new Promise(resolve => {
    resolve({links: results, jar})
  })
}

/**
 * Get Corporation
 *
 * @param {Object} links {name: <href>}
 * @param {CookieJar} jar
 */
function getCorporations ({links, jar}) {
  console.log('Get Corporations...')

  const q = d3.queue(25)
  for (const [name, href] of entries(links)) {
    q.defer(callback => {
      request.get('https://www.ic.gc.ca/app/ccc/srch/' + href, {jar}).then(details => {
        // Parse title to check for errors
        const title = cheerio.load(details)('title').text().trim()
        if (title.match(/Error/i)) {
          console.log('Error:', name)
          callback(null)
        } else {
          console.log('Saving HTML:', name)
          fs.writeFileSync(path.join(__dirname, 'corporations', name + '.html'), details)
          callback(null)
        }
      })
    })
  }
  q.awaitAll(() => {
    console.log('done')

    // Add 25 to offset
    const status = load.sync('status.json')
    status.offset = status.offset + 25
    write.sync('status.json', status)
    main()
  })
}

function main () {
  if (!fs.existsSync('status.json')) {
    write.sync('status.json', {offset: 0})
  }
  login()
    .then(getDetails)
    .then(getLinks)
    .then(getCorporations)
}

main()
