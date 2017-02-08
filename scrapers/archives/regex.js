const fs = require('fs')
const cheerio = require('cheerio')

const details = fs.readFileSync('details.html', 'utf-8')
const $ = cheerio.load(details)

const links = []
$('a').each((index, link) => {
  const href = link.attribs.href
  if (href.match(/docsCount/)) {
    console.log(link.childNodes[0].data.trim())
    links.push(href)
  }
})
// console.log(links)

// links = links.filter(link => link.match(/docsCount/g))
// console.log(links.length)

// for (const link of links) {
//   const $ = cheerio.load(link)
//   const name = $.text()
//   const href = $('a').attr('href')
// }

