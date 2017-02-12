const path = require('path')
const fs = require('fs')

fs.readdirSync('corporations').map(filename => {
  filename = filename.replace('.html', '')
  if (filename.match(/\./)) {
    console.log('removed', filename)
    fs.unlinkSync(path.join(__dirname, 'corporations', filename + '.html'))
  }
})
