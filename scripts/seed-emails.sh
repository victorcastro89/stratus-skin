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
if ! docker compose -f "$PROJECT_ROOT/docker/docker-compose.yml" ps | grep -q "Up"; then
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

echo "📦 Installing PHP IMAP extension if needed..."
docker exec "$CONTAINER" bash -c "
    set -e

    if php -m | grep -qi '^imap$'; then
        echo '✅ IMAP extension already installed'
        exit 0
    fi

    echo 'ℹ️  IMAP extension missing. Installing via install-php-extensions...'

    # Use mlocati/docker-php-extension-installer (handles deps automatically)
    if [ ! -x /usr/local/bin/install-php-extensions ]; then
        curl -sSLf -o /usr/local/bin/install-php-extensions \
            https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
        chmod +x /usr/local/bin/install-php-extensions
    fi

    install-php-extensions imap

    if php -m | grep -qi '^imap$'; then
        echo '✅ IMAP extension installed'
    else
        echo '❌ Failed to install/enable PHP IMAP extension'
        exit 1
    fi
"

echo ""
echo "📧 Running email seeder..."
echo ""

# Copy the seeder script into the container and run it
docker cp "$SCRIPT_DIR/seed-emails.php" "$CONTAINER:/tmp/seed-emails.php"
docker exec "$CONTAINER" php /tmp/seed-emails.php

echo ""
echo "🎉 Done! Log in to Roundcube at http://localhost:8000"
echo ""
echo "Test accounts:"
echo "  - victor@example.test / password123"
echo "  - alice@example.test / password123"
echo "  - bob@example.test / password123"
