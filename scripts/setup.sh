#!/usr/bin/env bash
# scripts/setup.sh — One-command developer onboarding for Stratus skin project.
# Usage: ./scripts/setup.sh
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RC_DIR="$REPO_ROOT/roundcubemail"
RC_REPO="https://github.com/roundcube/roundcubemail.git"
RC_VERSION="1.6.9"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   Stratus Skin — Developer Environment Setup ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── 1. Clone Roundcube if not present ────────────────────────────────────────
if [ ! -f "$RC_DIR/index.php" ]; then
    echo "📦 Cloning Roundcube v${RC_VERSION}..."
    git submodule update --init --recursive
    if [ ! -f "$RC_DIR/index.php" ]; then
        echo "⚠️  Submodule init didn't work. Cloning directly..."
        rm -rf "$RC_DIR"
        git clone --depth 1 --branch "${RC_VERSION}" "$RC_REPO" "$RC_DIR"
    fi
    echo "✅ Roundcube source ready"
else
    echo "✅ Roundcube source already present"
fi

# ── 2. Patch composer.json with extra plugins ───────────────────────────────
# Upstream Roundcube doesn't ship kolab/calendar or roundcube/carddav.
# We inject them into composer.json so `composer install` (in Docker) picks
# them up automatically. Node.js is already a prerequisite for LESS builds.
echo ""
echo "📦 Ensuring extra plugins in composer.json..."
node -e "
  const fs = require('fs');
  const path = process.argv[1] + '/composer.json';
  const pkg = JSON.parse(fs.readFileSync(path, 'utf8'));
  const extras = {
    'kolab/calendar':    '~3.6.1',
    'roundcube/carddav': '~5.0'
  };
  let changed = false;
  for (const [name, ver] of Object.entries(extras)) {
    if (!pkg.require[name]) {
      pkg.require[name] = ver;
      changed = true;
      console.log('  ➕ Added ' + name + ' ' + ver);
    } else {
      console.log('  ✅ ' + name + ' already present');
    }
  }
  if (changed) {
    fs.writeFileSync(path, JSON.stringify(pkg, null, 4) + '\n');
  }
" "$RC_DIR"

# ── 3. Symlink our skins into Roundcube ──────────────────────────────────────
echo ""
echo "🔗 Linking custom skins..."
for skin_dir in "$REPO_ROOT"/skins/*/; do
    skin_name=$(basename "$skin_dir")
    target="$RC_DIR/skins/$skin_name"
    if [ -L "$target" ]; then
        echo "  ✅ $skin_name (already linked)"
    elif [ -d "$target" ]; then
        echo "  ⚠️  $skin_name exists as a directory — backing up and linking"
        mv "$target" "${target}.bak.$(date +%s)"
        ln -sfn "$skin_dir" "$target"
        echo "  ✅ $skin_name (linked, old dir backed up)"
    else
        ln -sfn "$skin_dir" "$target"
        echo "  ✅ $skin_name"
    fi
done

# ── 4. Symlink our plugins into Roundcube ────────────────────────────────────
echo ""
echo "🔗 Linking custom plugins..."
for plugin_dir in "$REPO_ROOT"/plugins/*/; do
    [ -d "$plugin_dir" ] || continue
    plugin_name=$(basename "$plugin_dir")
    target="$RC_DIR/plugins/$plugin_name"
    if [ -L "$target" ]; then
        echo "  ✅ $plugin_name (already linked)"
    elif [ -d "$target" ]; then
        echo "  ⚠️  $plugin_name exists — backing up and linking"
        mv "$target" "${target}.bak.$(date +%s)"
        ln -sfn "$plugin_dir" "$target"
        echo "  ✅ $plugin_name (linked)"
    else
        ln -sfn "$plugin_dir" "$target"
        echo "  ✅ $plugin_name"
    fi
done

# ── 5. Generate Roundcube config from template ──────────────────────────────
echo ""
RC_CONFIG="$RC_DIR/config/config.inc.php"
if [ ! -f "$RC_CONFIG" ]; then
    echo "⚙️  Generating Roundcube config..."
    mkdir -p "$RC_DIR/config"
    DES_KEY=$(openssl rand -base64 18 | head -c 24)
    sed "s|__DES_KEY__|$DES_KEY|g" "$REPO_ROOT/config/config.inc.php.dist" > "$RC_CONFIG"
    echo "✅ Config created with random des_key"
else
    echo "✅ Roundcube config already exists"
fi

# ── 6. Copy Docker env if missing ───────────────────────────────────────────
echo ""
DOCKER_ENV="$REPO_ROOT/docker/.env"
if [ ! -f "$DOCKER_ENV" ]; then
    cp "$REPO_ROOT/docker/.env.example" "$DOCKER_ENV"
    echo "✅ Docker .env created from template"
else
    echo "✅ Docker .env already exists"
fi

# ── 7. Install Node.js dev dependencies (for LESS compilation) ──────────────
echo ""
if [ ! -d "$REPO_ROOT/node_modules" ]; then
    echo "📦 Installing Node.js dependencies..."
    cd "$REPO_ROOT" && npm install
    echo "✅ Node.js dependencies installed"
else
    echo "✅ Node.js dependencies already installed"
fi

# ── 8. Create necessary directories ─────────────────────────────────────────
mkdir -p "$RC_DIR/temp" "$RC_DIR/logs"

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   ✅  Setup complete!                        ║"
echo "╠══════════════════════════════════════════════╣"
echo "║                                              ║"
echo "║   Start the dev environment:                 ║"
echo "║     ./start-dev.sh                           ║"
echo "║                                              ║"
echo "║   Or manually:                               ║"
echo "║     cd docker && docker compose up -d        ║"
echo "║                                              ║"
echo "║   Compile LESS:                              ║"
echo "║     npm run less:build                       ║"
echo "║     npm run less:watch  (auto-recompile)     ║"
echo "║                                              ║"
echo "║   Open in browser:                           ║"
echo "║     http://localhost:8000                    ║"
echo "║                                              ║"
echo "║   Test accounts:                             ║"
echo "║     victor@example.test / password123        ║"
echo "║     alice@example.test  / password123        ║"
echo "║     bob@example.test    / password123        ║"
echo "╚══════════════════════════════════════════════╝"
echo ""
