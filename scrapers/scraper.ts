import * as fs from 'fs'
import * as path from 'path'
import * as request from 'request-promise'
import * as cheerio from 'cheerio'
import * as d3 from 'd3-queue'
const unidecode = require('unidecode')

async function main() {
  // Store Unique Corporation names from File System - Used as index
  const corporations = {}
  fs.readdirSync(path.join(__dirname, 'corporations')).map(filename => {
    const name = filename.replace('.html', '')
    corporations[name] = true
  })
  // Request Session from initial Get
  const jar = request.jar()
  const login = await request.get('https://www.ic.gc.ca/app/ccc/srch/', {jar})

  // Get list of corporation names
  const formData = {
    'searchCriteriaBean.textField': '*',
    'searchCriteriaBean.column': 'nm',
    'prtl': 1,
    'searchCriteriaBean.hitsPerPage': 50,
    'searchCriteriaBean.sortSpec': 'title asc',
    'searchCriteriaBean.isSummaryOn': 'N'
  }
  const search = await request.post('https://www.ic.gc.ca/app/ccc/srch/srch.do', {formData, jar})

  // Parse HTML
  const $ = cheerio.load(search)
  const links = $('ul.list-group.list-group-hover').find('a')

  const q = d3.queue(10)
  // Iterate over available links
  links.map(async (index, element: any) => {
    if (element.children.length) {
      let name: string = element.children[0].data.trim()
      name = name.replace('/', '-').replace('.', '')
      name = unidecode(name)
      const href: string = element.attribs.href
      if (href.match(/nvgt.do/)) {
        const V_TOKEN = Number(href.match(/V_TOKEN=(\d*)/)[1])

        // Create new request for details
        if (corporations[name] === true) {
          // console.log('Skipped:', name)
        } else {
          q.defer(async callback => {
            console.log('Saving HTML:', `-${name}-`)
            const baseUrl = 'https://www.ic.gc.ca/app/ccc/srch/'
            const details = await request.get(baseUrl + href, {jar})
            fs.writeFileSync(path.join(__dirname, 'corporations', name + '.html'), details)
            callback(null)
          })
        }
      }
    }
  })
  q.awaitAll(error => {
    console.log('done')
  })
}
main()