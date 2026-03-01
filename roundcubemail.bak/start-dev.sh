#!/bin/bash
# start-dev.sh — One-shot startup for the Roundcube dev environment.
set -e

command -v docker >/dev/null 2>&1 || { echo "❌ Docker not found. Install Docker Desktop first."; exit 1; }

echo "🔨 Building and starting containers..."
docker compose up -d --build

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
echo "Logs:  docker compose logs -f roundcube"
echo "Stop:  docker compose down"

