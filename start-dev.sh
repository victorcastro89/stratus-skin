#!/usr/bin/env bash
# start-dev.sh — One-shot startup for the Stratus dev environment.
set -e

REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"

# Run setup if roundcubemail is missing
if [ ! -f "$REPO_ROOT/roundcubemail/index.php" ]; then
    echo "🔧 First run detected — running setup..."
    "$REPO_ROOT/scripts/setup.sh"
fi

command -v docker >/dev/null 2>&1 || { echo "❌ Docker not found. Install Docker Desktop first."; exit 1; }

echo "🔨 Building and starting containers..."
cd "$REPO_ROOT/docker" && docker compose up -d --build

echo ""
echo "========================================="
echo "✅ Roundcube is starting!"
echo ""
echo "   http://localhost:8000"
echo ""
echo "   victor@example.test / password123"
echo "   alice@example.test  / password123"
echo "   bob@example.test    / password123"
echo "========================================="
echo ""
echo "LESS watch:  npm run less:watch"
echo "Logs:        cd docker && docker compose logs -f roundcube"
echo "Stop:        cd docker && docker compose down"

