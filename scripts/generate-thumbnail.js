/**
 * Generate thumbnail.png (320×240) for stratus skin selector.
 * Source: docker/www/skins/stratus/images/thumb.png  (any size/RGBA)
 * Resize to 320×240 via macOS `sips` (built-in, no extra deps needed).
 * Run: node scripts/generate-thumbnail.js  or  npm run thumbnail
 */
'use strict';
const { execSync } = require('child_process');
const path = require('path');

const root = path.join(__dirname, '..');
const src  = path.join(root, 'docker/www/skins/stratus/images/thumb.png');
const dest = path.join(root, 'docker/www/skins/stratus/thumbnail.png');

execSync(`sips -z 240 320 "${src}" --out "${dest}"`, { stdio: 'inherit' });
console.log(`✅ thumbnail.png written: ${dest}`);
