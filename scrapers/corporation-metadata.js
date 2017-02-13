const fs = require('fs')
const path = require('path')

// User Input
const folder = path.join(__dirname, 'corporations')

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

// Create Writer
const writer = fs.createWriteStream(path.join(__dirname, '..', 'corporations.json'))
writer.write('[\n')

// Loop each HTML
let count = 0
const filenames = fs.readdirSync(folder)
for (const filename of filenames) {
  const html = fs.readFileSync(path.join(folder, filename), 'utf-8')
  const results = parseHTML(html)
  results.filename = filename

  // Write JSON line
  writer.write(JSON.stringify(results, null, 2))

  // Counter
  count++
  if (count !== filenames.length) { writer.write(',\n') }
  if (count % 1000 === 0) { console.log(count) }
}
writer.end('\n]')

// // Save results to JSON
// write.sync('metadata.json', container)