const fs = require('fs')
const path = require('path')
const cheerio = require('cheerio')

const folder = path.join(__dirname, 'corporations')

let empties = 0
for (const filename of fs.readdirSync(folder)) {
  const html = fs.readFileSync(path.join(folder, filename), 'utf-8')
  const results = parseHTML(html)

  if (!Object.keys(results).length) {
    fs.unlinkSync(path.join(folder, filename))
  }
}
console.log(empties)

function parseHTML (html) {
  const results = {}

  // Legal Name
  const legalName = html.match(/Legal Name:.+\n.+<p>(.+)<\/p>/)
  if (legalName) { results.legalName = legalName[1] }

  // Operating Name
  const operatingName = html.match(/Operating Name:.+\n.+<p>(.+)<\/p>/)
  if (operatingName) { results.operatingName = operatingName[1] }

  // Alternate Name
  const alternateName = html.match(/Alternate Name:.+[\n\s]+<p.+>(.+)<\/p>/)
  if (alternateName) { results.alternateName = alternateName[1] }

  // Email
  const email = html.match(/mailto:(.+)" /)
  if (email) { results.email = email[1] }

  // Website
  const website = html.match(/Website URL">(.+)<\/a>/)
  if (website) { results.website = website[1] }

  return results
}

// const page = fs.readFileSync(path.join(folder, '3D COURSEWARE - LES EDITIONS 3D.html'), 'utf-8')
// parseHTML(page)