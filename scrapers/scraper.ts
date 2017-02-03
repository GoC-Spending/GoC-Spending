import * as fs from 'fs'
import * as path from 'path'
import * as request from 'request-promise'
import * as cheerio from 'cheerio'
import * as d3 from 'd3-queue'
import * as chalk from 'chalk'
const unidecode = require('unidecode')

// Create Folder
if (!fs.existsSync('corporations')) { fs.mkdirSync('corporations') }
let thread = 0

async function main() {
  thread ++

  // Request Session from initial Get
  const jar = request.jar()
  const login = await request.get('https://www.ic.gc.ca/app/ccc/srch/', {jar})

  // Get list of corporation names
  console.log('Starting thread...', thread)
  const formData = {
    'searchCriteriaBean.textField': '*',
    'searchCriteriaBean.column': 'nm',
    'prtl': 1,
    'searchCriteriaBean.hitsPerPage': 10000,
    'searchCriteriaBean.sortSpec': 'title asc',
    'searchCriteriaBean.isSummaryOn': 'N'
  }
  const search = await request.post('https://www.ic.gc.ca/app/ccc/srch/srch.do', {formData, jar})

  // Parse HTML
  const $ = cheerio.load(search)
  const links = $('ul.list-group.list-group-hover').find('a')

  const q = d3.queue(5)
  const start = new Date().getTime()
  let count = 0
  let errors = 0

  // Iterate over available links
  links.map(async (index, element: any) => {
    if (element.children.length) {
      let name: string = element.children[0].data.trim()
      name = name.replace(/\//g, '-').replace('.', '')
      name = unidecode(name)
      name = name.toUpperCase()
      const href: string = element.attribs.href
      if (href.match(/nvgt.do/)) {
        const V_TOKEN = Number(href.match(/V_TOKEN=(\d*)/)[1])
        const offset_V_TOKEN = new Date().getTime() - V_TOKEN

        // Create new request for details
        if (fs.existsSync(path.join(__dirname, 'corporations', name + '.html'))) {
          // console.log('Skipped:', name)
        } else {
          q.defer(async callback => {
            if (errors > 5) { return callback(null) }
            const fake_href = href.replace(/V_TOKEN=\d*/, `V_TOKEN=${new Date().getTime() - offset_V_TOKEN}`)
            const baseUrl = 'https://www.ic.gc.ca/app/ccc/srch/'
            const details = await request.get(baseUrl + href, {jar})
            const title = cheerio.load(details)('title').text().trim()

            if (title.match(/Error/i)) {
              errors ++
              if (errors > 5) {
                console.log('Restarting...', thread)
                main()
              }
              callback(null)
            } else {
              // console.log('Count:', count, 'Time:', new Date().getTime() - start)
              // count++
              console.log('Saving HTML:', `-${name}-`)
              fs.writeFileSync(path.join(__dirname, 'corporations', name + '.html'), details)
              callback(null)
            }
          })
        }
      }
    }
  })
  q.awaitAll(error => {
    console.log('done...', thread)
  })
}
main()