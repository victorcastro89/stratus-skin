#!/usr/bin/env node
'use strict';

/**
 * Generate a changelog draft for the next release.
 *
 * Usage:
 *   npm run changelog           # patch:  1.0.0 → 1.0.1
 *   npm run changelog -- minor  # minor:  1.0.0 → 1.1.0
 *   npm run changelog -- major  # major:  1.0.0 → 2.0.0
 *
 * Creates changelog/<version>.md grouped by conventional commit type.
 * Edit the file before running `npm run release`.
 */

const { execSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

function run(cmd) {
  const result = execSync(cmd, { cwd: ROOT, encoding: 'utf8' });
  return result ? result.trim() : '';
}

function readJson(rel) {
  return JSON.parse(fs.readFileSync(path.join(ROOT, rel), 'utf8'));
}

// ── Conventional commit → Keep a Changelog mapping ──────────────────

const SECTION_MAP = {
  feat:     'Added',
  fix:      'Fixed',
  perf:     'Changed',
  refactor: 'Changed',
  style:    'Changed',
  build:    'Changed',
  ci:       'Changed',
  docs:     'Changed',
  chore:    'Changed',
};

const SKIP_PATTERNS = [
  /^chore: release\b/i,
  /^merge\b/i,
];

// ── Determine next version ──────────────────────────────────────────

const bump = process.argv[2] || 'patch';
if (!['patch', 'minor', 'major'].includes(bump)) {
  console.error('Usage: npm run changelog [patch|minor|major]');
  process.exit(1);
}

const pkg = readJson('package.json');
const [maj, min, pat] = pkg.version.split('.').map(Number);

const newVersion =
  bump === 'major' ? `${maj + 1}.0.0` :
  bump === 'minor' ? `${maj}.${min + 1}.0` :
                     `${maj}.${min}.${pat + 1}`;

const changelogDir  = path.join(ROOT, 'changelog');
const changelogFile = path.join(changelogDir, `${newVersion}.md`);

// ── Guard: don't overwrite an existing draft ────────────────────────

if (fs.existsSync(changelogFile)) {
  console.log(`\nchangelog/${newVersion}.md already exists — edit it and run \`npm run release\`.\n`);
  process.exit(0);
}

// ── Collect and parse commits since last tag ────────────────────────

let rawMessages = [];
try {
  const lastTag = run('git describe --tags --abbrev=0');
  const log     = run(`git log ${lastTag}..HEAD --oneline --no-merges`);
  if (log) {
    rawMessages = log.split('\n').map(l => l.replace(/^[0-9a-f]+ /, ''));
  }
} catch (_) { /* no previous tag */ }

// Filter out noise
const messages = rawMessages.filter(msg =>
  !SKIP_PATTERNS.some(re => re.test(msg))
);

// Deduplicate (keep first occurrence)
const seen = new Set();
const unique = messages.filter(msg => {
  const key = msg.toLowerCase();
  if (seen.has(key)) return false;
  seen.add(key);
  return true;
});

// ── Group by section ────────────────────────────────────────────────

const PREFIX_RE = /^(\w+)(?:\(.+?\))?!?:\s*/;

const sections = {};

for (const msg of unique) {
  const match = msg.match(PREFIX_RE);
  let section, text;

  if (match) {
    const prefix = match[1].toLowerCase();
    section = SECTION_MAP[prefix] || 'Other';
    text = msg.slice(match[0].length);
  } else {
    section = 'Other';
    text = msg;
  }

  // Capitalize first letter
  text = text.charAt(0).toUpperCase() + text.slice(1);

  if (!sections[section]) sections[section] = [];
  sections[section].push(text);
}

// ── Build draft content ─────────────────────────────────────────────

const SECTION_ORDER = ['Added', 'Fixed', 'Changed', 'Other'];
const parts = [];

for (const section of SECTION_ORDER) {
  if (!sections[section] || !sections[section].length) continue;
  parts.push(`### ${section}`);
  for (const line of sections[section]) {
    parts.push(`- ${line}`);
  }
  parts.push('');
}

if (!parts.length) {
  parts.push('- (describe your changes here)', '');
}

// ── Write draft ─────────────────────────────────────────────────────

if (!fs.existsSync(changelogDir)) {
  fs.mkdirSync(changelogDir, { recursive: true });
}

fs.writeFileSync(changelogFile, parts.join('\n'));

console.log(`\n✓ Created changelog/${newVersion}.md\n`);
console.log('  Contents:\n');
console.log(parts.map(l => `    ${l}`).join('\n'));
console.log('\n  Edit it with the real release notes, then run:');
console.log(`  npm run release\n`);
