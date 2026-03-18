#!/usr/bin/env node
'use strict';

/**
 * Release script — bumps versions, builds CSS, commits, tags, and pushes.
 *
 * Requires changelog/<version>.md to exist (create with `npm run changelog`).
 *
 * Usage:
 *   npm run release           # patch:  1.0.0 → 1.0.1
 *   npm run release -- minor  # minor:  1.0.0 → 1.1.0
 *   npm run release -- major  # major:  1.0.0 → 2.0.0
 */

const { execSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

// ── Helpers ──────────────────────────────────────────────────────────

function run(cmd, opts = {}) {
  const result = execSync(cmd, { cwd: ROOT, encoding: 'utf8', ...opts });
  return result ? result.trim() : '';
}

function readJson(rel) {
  return JSON.parse(fs.readFileSync(path.join(ROOT, rel), 'utf8'));
}

function writeJson(rel, obj) {
  const raw   = fs.readFileSync(path.join(ROOT, rel), 'utf8');
  const useTab = raw.startsWith('{\n\t');
  fs.writeFileSync(path.join(ROOT, rel), JSON.stringify(obj, null, useTab ? '\t' : '    ') + '\n');
}

function abort(msg) {
  console.error(msg);
  process.exit(1);
}

// ── Determine next version ──────────────────────────────────────────

const bump = process.argv[2] || 'patch';
if (!['patch', 'minor', 'major'].includes(bump)) {
  abort('Usage: npm run release [patch|minor|major]');
}

const pkg = readJson('package.json');
const [maj, min, pat] = pkg.version.split('.').map(Number);

const newVersion =
  bump === 'major' ? `${maj + 1}.0.0` :
  bump === 'minor' ? `${maj}.${min + 1}.0` :
                     `${maj}.${min}.${pat + 1}`;

console.log(`\nReleasing ${pkg.version} → ${newVersion} (${bump})\n`);

// ── Pre-flight checks ───────────────────────────────────────────────

// Abort if tag already exists
try {
  run(`git rev-parse v${newVersion} 2>/dev/null`);
  abort(`✗ Tag v${newVersion} already exists. Aborting.`);
} catch (_) { /* tag doesn't exist — good */ }

// Abort if working tree is dirty (changelog file is allowed)
const dirty = run('git status --porcelain');
if (dirty) {
  const nonChangelog = dirty.split('\n').filter(l => !l.includes(`changelog/${newVersion}.md`));
  if (nonChangelog.length) {
    abort('✗ Working tree has uncommitted changes. Commit or stash them first.');
  }
}

// Abort if changelog entry is missing
const changelogDir  = path.join(ROOT, 'changelog');
const entryFile     = path.join(changelogDir, `${newVersion}.md`);
if (!fs.existsSync(entryFile)) {
  abort(`✗ changelog/${newVersion}.md not found. Run \`npm run changelog\` first and edit it.`);
}

// ── Build CSS (before any file changes) ─────────────────────────────

console.log('Building CSS...');
try {
  run('npm run less:build', { stdio: 'inherit' });
  console.log('✓ CSS compiled\n');
} catch (_) {
  abort('✗ less:build failed — aborting (no files were modified)');
}

// ── Bump versions in all files ──────────────────────────────────────

const versionFiles = [
  'package.json',
  'skins/stratus/composer.json',
  'plugins/stratus_helper/composer.json',
  'plugins/undo_send/composer.json',
];

for (const rel of versionFiles) {
  const obj = readJson(rel);
  obj.version = newVersion;
  writeJson(rel, obj);
  console.log(`✓ ${rel}`);
}

// ── Git commit + tag ────────────────────────────────────────────────

console.log('\nCommitting...');
const stagedFiles = [
  ...versionFiles,
  `changelog/${newVersion}.md`,
  'skins/stratus/styles/styles.min.css',
];
run(`git add ${stagedFiles.join(' ')}`);
run(`git commit -m "chore: release v${newVersion}"`);
run(`git tag v${newVersion}`);

console.log(`✓ Committed and tagged v${newVersion}`);

// ── Push (rollback commit + tag on failure) ─────────────────────────

console.log('\nPushing...');
try {
  run('git push origin main --tags', { stdio: 'inherit' });
  console.log(`\n✓ v${newVersion} released and pushed successfully\n`);
} catch (_) {
  console.error('\n✗ Push failed — rolling back commit and tag...');
  try {
    run(`git tag -d v${newVersion}`);
    run('git reset --soft HEAD~1');
    console.error('✓ Rolled back commit and tag. Working tree preserved (files still staged).');
    console.error('  Fix the issue and run the release again.');
  } catch (rollbackErr) {
    console.error('✗ Rollback failed — manual cleanup needed:');
    console.error(`    git tag -d v${newVersion}`);
    console.error('    git reset --soft HEAD~1');
  }
  process.exit(1);
}
