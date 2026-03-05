#!/usr/bin/env bash
# scripts/clone-roundcube.sh — Clone Roundcube and create symlinks for skins/plugins.
# Composer install + plugin install happen inside the Docker container (see setup.sh).
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RC_DIR="$REPO_ROOT/roundcubemail"
RC_REPO="https://github.com/roundcube/roundcubemail.git"
# Pin to a release tag for reproducible builds. Change as needed.
RC_TAG="1.6.13"

echo ""
echo "── Roundcube source clone ──────────────────────────────────────"

# ── 1. Clone (skip if already present) ─────────────────────────────────────
if [ -d "$RC_DIR/.git" ]; then
    echo "✅ Roundcube clone already exists at roundcubemail/"
else
    echo "📥 Cloning Roundcube $RC_TAG …"
    git clone --depth 1 --branch "$RC_TAG" "$RC_REPO" "$RC_DIR"
    echo "✅ Cloned Roundcube $RC_TAG"
fi

# ── 2. Create symlinks for our custom skins ────────────────────────────────
echo ""
echo "🔗 Creating symlinks for custom skins…"
for skin_dir in "$REPO_ROOT"/skins/*/; do
    skin_name="$(basename "$skin_dir")"
    link_target="$RC_DIR/skins/$skin_name"
    if [ -L "$link_target" ]; then
        echo "   ✅ skins/$skin_name (symlink exists)"
    elif [ -d "$link_target" ]; then
        echo "   ⚠️  skins/$skin_name is a real directory — skipping (not overwriting)"
    else
        ln -s "../../skins/$skin_name" "$link_target"
        echo "   ✅ skins/$skin_name → ../../skins/$skin_name"
    fi
done

# ── 3. Create symlinks for our custom plugins ──────────────────────────────
echo ""
echo "🔗 Creating symlinks for custom plugins…"
for plugin_dir in "$REPO_ROOT"/plugins/*/; do
    plugin_name="$(basename "$plugin_dir")"
    link_target="$RC_DIR/plugins/$plugin_name"
    if [ -L "$link_target" ]; then
        echo "   ✅ plugins/$plugin_name (symlink exists)"
    elif [ -d "$link_target" ]; then
        echo "   ⚠️  plugins/$plugin_name is a real directory — skipping"
    else
        ln -s "../../plugins/$plugin_name" "$link_target"
        echo "   ✅ plugins/$plugin_name → ../../plugins/$plugin_name"
    fi
done

# ── 4. Ensure required directories exist ───────────────────────────────────
echo ""
echo "📁 Ensuring runtime directories…"
mkdir -p "$RC_DIR/temp" "$RC_DIR/logs"

echo ""
echo "── Clone setup complete ────────────────────────────────────────"
echo "   Next: run 'npm run docker:up' to start containers."
echo "   Config is bind-mounted from docker/config/roundcube.config.inc.php"
echo "   Composer install + calendar plugin run inside Docker on first start."
echo ""
