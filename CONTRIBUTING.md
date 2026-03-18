# Contributing

## Setup

See [README.md](README.md) for Docker and Node.js setup.

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/). The changelog generator groups entries by prefix.

- `feat:` → Added
- `fix:` → Fixed
- `refactor:`, `chore:`, `docs:`, `style:`, `build:`, `ci:`, `perf:` → Changed

## CSS

Run `npm run less:build` after editing any `.less` file. Use `npm run less:watch` during development.

## Tests

Run `npm test` before submitting changes (93 PHPUnit tests).

## Releasing

Two-step process:

```bash
npm run changelog            # generates changelog/X.Y.Z.md from commits
# edit changelog/X.Y.Z.md with real release notes
npm run release              # builds CSS, bumps versions, assembles CHANGELOG.md, commits, tags, pushes
```

Pass `minor` or `major` to both commands for non-patch releases:

```bash
npm run changelog -- minor
npm run release -- minor
```

Each version gets its own file in `changelog/`. The release script assembles `CHANGELOG.md` from all entries automatically. If the push fails, the release script rolls back the commit and tag.
