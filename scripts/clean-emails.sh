#!/usr/bin/env bash
# Email cleanup script
# Deletes all messages and custom folders for all configured mailboxes in docker-mailserver.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker/docker-compose.yml"

echo "🧹 Roundcube Mail Cleanup"
echo "=========================="
echo ""

if ! docker compose -f "$COMPOSE_FILE" ps | grep -q "Up"; then
    echo "❌ Docker containers are not running."
    echo "   Start them with: npm run docker:up"
    exit 1
fi

CONTAINER=$(docker compose -f "$COMPOSE_FILE" ps -q mailserver 2>/dev/null)

if [ -z "$CONTAINER" ]; then
    echo "❌ Could not find mailserver container."
    exit 1
fi

echo "📭 Cleaning all configured mailboxes..."
docker exec "$CONTAINER" bash -c '
    set -euo pipefail

    ACCOUNTS_FILE="/tmp/docker-mailserver/postfix-accounts.cf"

    if [ ! -f "$ACCOUNTS_FILE" ]; then
        echo "❌ Accounts file not found: $ACCOUNTS_FILE"
        exit 1
    fi

    users=$(grep -v "^[[:space:]]*$" "$ACCOUNTS_FILE" | grep -v "^[[:space:]]*#" | cut -d"|" -f1)

    if [ -z "$users" ]; then
        echo "⚠️  No users found in $ACCOUNTS_FILE"
        exit 0
    fi

    for user in $users; do
        echo "➡️  $user"

        # Remove every message in every mailbox, including INBOX.
        doveadm expunge -u "$user" mailbox "*" ALL || true

        # Remove all folders except INBOX.
        while IFS= read -r mailbox; do
            if [ -n "$mailbox" ] && [ "$mailbox" != "INBOX" ]; then
                doveadm mailbox delete -u "$user" "$mailbox" || true
            fi
        done < <(doveadm mailbox list -u "$user" 2>/dev/null || true)

        # Ensure INBOX exists.
        doveadm mailbox create -u "$user" INBOX >/dev/null 2>&1 || true

        echo "   ✅ cleaned"
    done
'

echo ""
echo "✅ Mail cleanup complete."
echo "   All messages were deleted and non-INBOX folders were removed."
