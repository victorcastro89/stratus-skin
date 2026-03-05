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
mkdir -p "$DOCKER_DIR/db/sqlite" "$DOCKER_DIR/logs"
echo "✅ Volume directories ready (docker/db/sqlite, docker/logs)"

# ── 3. Clone Roundcube + create symlinks ────────────────────────────────────
echo ""
"$REPO_ROOT/scripts/clone-roundcube.sh"

# ── 4. Install Node.js dev dependencies (for LESS compilation) ──────────────
echo ""
if [ ! -d "$REPO_ROOT/node_modules" ]; then
    echo "📦 Installing Node.js dependencies..."
    cd "$REPO_ROOT" && npm install
    echo "✅ Node.js dependencies installed"
else
    echo "✅ Node.js dependencies already installed"
fi

# ── 5. Build Docker image (if not already built) ───────────────────────────
echo ""
echo "🐳 Building Roundcube Docker image..."
cd "$DOCKER_DIR" && docker compose build
echo "✅ Docker image ready"

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
