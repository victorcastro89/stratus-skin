#!/bin/bash
# -------------------------------------------------------
# setup-mail.sh — Create test accounts in docker-mailserver
#
# Run ONCE after `docker compose up -d`:
#   ./setup-mail.sh
#
# Test accounts:
#   alice@example.test / password123
#   bob@example.test   / password123
# -------------------------------------------------------

set -euo pipefail

CONTAINER="mailserver"

echo "⏳ Waiting for mailserver container to be healthy..."
until docker inspect --format='{{.State.Health.Status}}' "$CONTAINER" 2>/dev/null | grep -q "healthy"; do
  printf "."
  sleep 3
done
echo ""
echo "✅ Mailserver is healthy."

echo ""
echo "Creating test accounts..."

docker exec "$CONTAINER" setup email add alice@example.test password123
echo "  ✅ alice@example.test created"

docker exec "$CONTAINER" setup email add bob@example.test password123
echo "  ✅ bob@example.test created"

echo ""
echo "========================================"
echo "  Local mail setup complete!"
echo ""
echo "  Login at: http://localhost:8000"
echo ""
echo "  Accounts:"
echo "    alice@example.test / password123"
echo "    bob@example.test   / password123"
echo "========================================"
