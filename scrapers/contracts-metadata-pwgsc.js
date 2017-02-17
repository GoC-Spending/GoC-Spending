const fs = require('fs')
const path = require('path')

const ownerAcronym = 'pwgsc'

// User Input
const folder = path.join(__dirname, 'contracts/' + ownerAcronym)

/**
 * Parse HTML
 *
 * @param {string} html HTML document
 * @return {Object} JSON result
 */
function parseHTML (html, filename) {
  const results = {}

  const mapping = {
    vendorName: 'Vendor Name',
    referenceNumber: 'Reference Number',
    contractDate: 'Contract Date',
    description: 'Description of Work',
    contractPeriodStart: 'Contract Period - From',
    contractPeriodEnd: 'Contract Period - To',
    deliveryDate: 'Delivery Date',
    originalValue: 'Contract Value',
    contractValue: 'Total Amended Contract Value',
    comments: 'Comments\\s+'
  }


  for (const key of Object.keys(mapping)) {
    const name = mapping[key]

    const pattern = RegExp(name + '<\\/th>\\s+<td>([a-z0-9-@$#%^&+.,;\\s]*)', 'i')

    const match = html.match(pattern)

    // console.log(name);
    // console.log(pattern);
    // console.log(match[1]);

    // process.exit()

    if (match && match[1]) { results[key] = match[1] }

    // Vendor Name<\/th>\s+<td>([\S\s]*)<\/td>
  }

  // TODO - more values here.

  return results
}

// Create Writer
const writer = fs.createWriteStream(path.join(__dirname, '..', 'contracts-' + ownerAcronym + '.json'))
writer.write('[\n')

// Loop each HTML
let count = 0
const filenames = fs.readdirSync(folder)
for (const filename of filenames) {
  if(filename.match(/\.html/)) {

    const html = fs.readFileSync(path.join(folder, filename), 'utf-8')
    const results = parseHTML(html, filename)
    results.filename = filename

    // Write JSON line
    writer.write(JSON.stringify(results, null, 2))

    // Counter
    count++
    if (count !== filenames.length) { writer.write(',\n') }
    if (count % 1000 === 0) { console.log(count) }

  }

}
writer.end('\n]')
