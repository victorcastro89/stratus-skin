#!/usr/bin/env node

const fs = require('node:fs');
const path = require('node:path');

const cssPath = path.join(__dirname, '..', 'skins', 'stratus', 'styles', 'styles.min.css');

if (!fs.existsSync(cssPath)) {
  console.error(`[stratus] CSS not found: ${cssPath}`);
  process.exit(1);
}

let css = fs.readFileSync(cssPath, 'utf8');

// Elastic's imported font-face declarations point to ../fonts (relative to stratus/styles)
// which resolves to /skins/stratus/fonts. We must always resolve to elastic fonts instead.
css = css
  .replace(/url\("\.\.\/fonts\//g, 'url("../../elastic/fonts/')
  .replace(/url\('\.\.\/fonts\//g, "url('../../elastic/fonts/");

fs.writeFileSync(cssPath, css);
console.log('[stratus] Font URLs rewritten to ../../elastic/fonts in styles.min.css');
