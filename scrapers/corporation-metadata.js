const fs = require('fs')
const path = require('path')
const ProgressBar = require('progress')

// User Input
const folder = path.join(__dirname, 'corporations')

/**
 * Parse HTML
 *
 * @param {string} html HTML document
 * @return {Object} JSON result
 */
function parseHTML (html, filename) {
  const results = {}

  // Legal Name
  const legalName = html.match(/Legal Name:.+\n.+<p>(.+)<\/p>/i)
  if (legalName) { results.legalName = legalName[1] }

  // Operating Name
  const operatingName = html.match(/Operating Name:.+\n.+<p>(.+)<\/p>/i)
  if (operatingName) { results.operatingName = operatingName[1] }

  // Alternate Name
  const alternateName = html.match(/Alternate Name:.+[\n\s]+<p.+>(.+)<\/p>/i)
  if (alternateName) { results.alternateName = alternateName[1] }

  // Telephone
  const telephone = html.match(/Telephone:[\s<\/a-z>=]*"col-md-7">\s*([\(\d\) -]*)/i)
  if (telephone) { results.telephone = telephone[1] }

  // Email
  const email = html.match(/Email:[\s<\/a-z>=]*"col-md-7">\s*([a-z\d-@.+]+)\s*/i)
  if (email) { results.email = email[1] }

  // Email
  const employees = html.match(/Number of Employees:[\s</a-z>]*="col-md-7">\s*([\da-z-]*)/i)
  if (employees) {
    results.employees = Number(employees[1])
  }
  // Year Established
  const yearEstablished = html.match(/Year Established:[\s<\/a-z>=]*"col-md-7">\s*([a-z\d-]+)\s*/i)
  if (yearEstablished) { results.yearEstablished = Number(yearEstablished[1]) }

  // Exporting
  const exporting = html.match(/Exporting:[\s<\/a-z>=]*"col-md-7">\s*([a-z]*)/i)
  if (exporting) { results.exporting = exporting[1] }

  // Website
  const website = html.match(/Website URL">(.+)<\/a>/i)
  if (website) { results.website = website[1] }

  // Mailing Address
  const matchMailingAddress = html.match(/Mailing Address:<\/h2>[\s]*<address class="mrgn-bttm-md">([\s\da-z<\/>,]*)<\/address>/i)
  if (matchMailingAddress) {
    const sections = matchMailingAddress[1].split(/<br\/>/g)
    const mailingAddress = sections.map(section => section.trim().replace(/\s+/g, ' ')).join(' ')
    results.mailingAddress = mailingAddress
  }

  // Location Address
  const matchLocationAddress = html.match(/Location Address:<\/h2>[\s]*<address class="mrgn-bttm-md">([\s\da-z<\/>,]*)<\/address>/i)
  if (matchLocationAddress) {
    let sections = matchLocationAddress[1].split(/<br\/>/g)
    const locationAddress = sections.map(section => section.trim().replace(/[\s]+/g, ' ')).join(' ')
    results.locationAddress = locationAddress
  }

  // Primary Industry
  let primaryIndustry = html.match(/Primary Industry \(NAICS\):[\s<\/a-z>=]*"col-md-7">([\sa-z\d-]+)/i)
  if (primaryIndustry) {
    primaryIndustry = primaryIndustry[1].trim()
    results.primaryIndustry = primaryIndustry

    // NAICS
    const primaryIndustryNAICS = primaryIndustry.match(/\d+/)
    if (primaryIndustryNAICS) {
      results.primaryIndustryNAICS = Number(primaryIndustryNAICS[0])
    }
  }

  // Alternate Industries
  let alternateIndustry = html.match(/Alternate Industries \(NAICS\):[\s<\/a-z>=]*"col-md-7">([\sa-z\d-]+)/i)
  if (alternateIndustry) {
    alternateIndustry = alternateIndustry[1].trim()
    results.alternateIndustry = alternateIndustry

    // NAICS
    const alternateIndustryNAICS = alternateIndustry.match(/\d+/)
    if (alternateIndustryNAICS) {
      results.alternateIndustryNAICS = Number(alternateIndustryNAICS[0])
    }
  }

  // Primary Business Activity
  const primaryBusinessActivity = html.match(/Primary Business Activity:[\s<\/a-z>=]*"col-md-7">([\sa-z\d-\/&;&nbsp;]+)/i)
  if (primaryBusinessActivity) { results.primaryBusinessActivity = primaryBusinessActivity[1].replace(/&nbsp;/g, '').trim() }

  return results
}

// Create Writer
const writer = fs.createWriteStream(path.join(__dirname, '..', 'corporations.json'))
writer.write('[\n')

// Loop each HTML
let count = 0
const filenames = fs.readdirSync(folder)
const bar = new ProgressBar('  processing [:bar] :percent :current/:total', {
  complete: '=',
  incomplete: ' ',
  width: 20,
  total: filenames.length
})

for (const filename of filenames) {
  if (filename.match(/\.html/)) {
    const html = fs.readFileSync(path.join(folder, filename), 'utf-8')
    const results = parseHTML(html, filename)
    results.filename = filename

    // Write JSON line
    writer.write(JSON.stringify(results, null, 2))

    // Counter
    count++
    if (count !== filenames.length) { writer.write(',\n') }
    if (count % 250 === 0) { bar.update(count / filenames.length) }
  }
}
writer.end('\n]')
