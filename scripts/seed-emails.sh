#!/bin/bash
# Email seeding wrapper script
# Runs the PHP seeder from inside the Docker container

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "🌱 Roundcube Email Seeder"
echo "=========================="
echo ""

# Check if docker-compose is running
PS_OUTPUT=$(docker compose -f "$PROJECT_ROOT/docker/docker-compose.yml" ps 2>/dev/null)
if ! echo "$PS_OUTPUT" | grep -q "Up"; then
    echo "❌ Docker containers are not running."
    echo "   Start them with: cd docker && docker compose up -d"
    exit 1
fi

# Get the roundcube container name
CONTAINER=$(docker compose -f "$PROJECT_ROOT/docker/docker-compose.yml" ps -q roundcube 2>/dev/null)

if [ -z "$CONTAINER" ]; then
    echo "❌ Could not find roundcube container."
    exit 1
fi

echo "📦 Checking PHP IMAP extension..."
if docker exec "$CONTAINER" php -m 2>/dev/null | grep -qi '^imap$'; then
    echo "  ✅ IMAP extension already installed"
else
    echo "  ℹ️  IMAP extension missing. Installing..."
    docker exec "$CONTAINER" bash -c "
        set -e

        # Use mlocati/docker-php-extension-installer (handles deps automatically)
        if [ ! -x /usr/local/bin/install-php-extensions ]; then
            curl -sSLf -o /usr/local/bin/install-php-extensions \
                https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
            chmod +x /usr/local/bin/install-php-extensions
        fi

        install-php-extensions imap

        if php -m | grep -qi '^imap\$'; then
            echo '  ✅ IMAP extension installed successfully'
        else
            echo '  ❌ Failed to install/enable PHP IMAP extension'
            exit 1
        fi
    "
fi

echo ""
echo "📧 Running email seeder..."
echo ""

# Copy the seeder script into the container and run it
docker cp "$SCRIPT_DIR/seed-emails.php" "$CONTAINER:/tmp/seed-emails.php"
ENV_ARGS=""
[ -n "$SEED_COUNT" ] && ENV_ARGS="$ENV_ARGS -e SEED_COUNT=$SEED_COUNT"
[ -n "$IMAP_USER" ] && ENV_ARGS="$ENV_ARGS -e IMAP_USER=$IMAP_USER"
[ -n "$IMAP_PASS" ] && ENV_ARGS="$ENV_ARGS -e IMAP_PASS=$IMAP_PASS"
[ -n "$IMAP_HOST" ] && ENV_ARGS="$ENV_ARGS -e IMAP_HOST=$IMAP_HOST"
[ -n "$IMAP_PORT" ] && ENV_ARGS="$ENV_ARGS -e IMAP_PORT=$IMAP_PORT"
[ -n "$IMAP_SSL" ] && ENV_ARGS="$ENV_ARGS -e IMAP_SSL=$IMAP_SSL"
docker exec $ENV_ARGS "$CONTAINER" php /tmp/seed-emails.php

echo ""
echo "🎉 Done! Log in to Roundcube at http://localhost:8000"
echo ""
echo "Test accounts:"
echo "  - victor@example.test / password123"
echo "  - alice@example.test / password123"
echo "  - bob@example.test / password123"
