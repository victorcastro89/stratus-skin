#!/usr/bin/env bash
# scripts/setup.sh — One-command developer onboarding for Stratus skin project.
# Usage: ./scripts/setup.sh
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_DIR="$REPO_ROOT/docker"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   Stratus Skin — Developer Environment Setup ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── 1. Copy Docker env if missing ───────────────────────────────────────────
echo "⚙️  Checking Docker .env..."
DOCKER_ENV="$DOCKER_DIR/.env"
if [ ! -f "$DOCKER_ENV" ] && [ -f "$DOCKER_DIR/.env.example" ]; then
    cp "$DOCKER_DIR/.env.example" "$DOCKER_ENV"
    echo "✅ Docker .env created from template"
else
    echo "✅ Docker .env already exists"
fi

# ── 2. Create host directories for Docker volumes ───────────────────────────
echo ""
echo "📁 Ensuring host volume directories exist..."
mkdir -p "$DOCKER_DIR/www" "$DOCKER_DIR/db/sqlite"
echo "✅ Volume directories ready (docker/www, docker/db/sqlite)"

# ── 3. Pull official Roundcube image ────────────────────────────────────────
echo ""
echo "🐳 Pulling official Roundcube Docker image..."
docker pull roundcube/roundcubemail:latest-apache
echo "✅ Roundcube image ready"

# ── 4. Extract elastic skin sources for host-side LESS compilation ──────────
# Our styles.less imports elastic's LESS (for variable/mixin inheritance).
# We extract just the elastic skin from the image so `npm run less:build`
# works on the host without needing the full Roundcube source tree.
# echo ""
# ELASTIC_DIR="$REPO_ROOT/roundcubemail/skins/elastic"
# if [ ! -f "$ELASTIC_DIR/styles/styles.less" ]; then
#     echo "📦 Extracting elastic skin sources from Docker image..."
#     mkdir -p "$REPO_ROOT/roundcubemail/skins"

#     # Create a temporary container (don't start it) to copy files out
#     TEMP_CONTAINER=$(docker create roundcube/roundcubemail:latest-apache /bin/true)
#     docker cp "$TEMP_CONTAINER:/usr/src/roundcubemail/skins/elastic" "$ELASTIC_DIR"
#     docker rm "$TEMP_CONTAINER" > /dev/null
#     echo "✅ Elastic skin sources extracted for host LESS compilation"
# else
#     echo "✅ Elastic skin sources already present"
# fi

# ── 5. Install Node.js dev dependencies (for LESS compilation) ──────────────
echo ""
if [ ! -d "$REPO_ROOT/node_modules" ]; then
    echo "📦 Installing Node.js dependencies..."
    cd "$REPO_ROOT" && npm install
    echo "✅ Node.js dependencies installed"
else
    echo "✅ Node.js dependencies already installed"
fi

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   ✅  Setup complete!                        ║"
echo "╠══════════════════════════════════════════════╣"
echo "║                                              ║"
echo "║   Start the dev environment:                 ║"
echo "║     npm start                                ║"
echo "║                                              ║"
echo "║   Or just containers (skip setup):           ║"
echo "║     npm run docker:up                        ║"
echo "║                                              ║"
echo "║   Compile LESS:                              ║"
echo "║     npm run less:build                       ║"
echo "║     npm run less:watch  (auto-recompile)     ║"
echo "║                                              ║"
echo "║   Logs / Stop:                               ║"
echo "║     npm run docker:logs                      ║"
echo "║     npm stop                                 ║"
echo "║                                              ║"
echo "║   Open in browser:                           ║"
echo "║     http://localhost:8000                    ║"
echo "║                                              ║"
echo "║   Test accounts (auto-created on first login)║"
echo "║     victor@example.test / password123        ║"
echo "║     alice@example.test  / password123        ║"
echo "║     bob@example.test    / password123        ║"
echo "╚══════════════════════════════════════════════╝"
echo ""
