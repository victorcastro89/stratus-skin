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
if ! docker compose -f "$SCRIPT_DIR/docker-compose.yml" ps | grep -q "Up"; then
    echo "❌ Docker containers are not running."
    echo "   Start them with: cd docker && docker compose up -d"
    exit 1
fi

# Get the roundcube container name
CONTAINER=$(docker compose -f "$SCRIPT_DIR/docker-compose.yml" ps -q roundcube 2>/dev/null)

if [ -z "$CONTAINER" ]; then
    echo "❌ Could not find roundcube container."
    exit 1
fi

echo "📦 Installing PHP IMAP extension if needed..."
docker exec "$CONTAINER" bash -c "
    if ! php -m | grep -q imap; then
        apt-get update -qq && apt-get install -y -qq libc-client-dev libkrb5-dev > /dev/null 2>&1
        docker-php-ext-configure imap --with-kerberos --with-imap-ssl > /dev/null 2>&1
        docker-php-ext-install -j\$(nproc) imap > /dev/null 2>&1
        echo '✅ IMAP extension installed'
    else
        echo '✅ IMAP extension already installed'
    fi
"

echo ""
echo "📧 Running email seeder..."
echo ""

# Run the seeder script inside the container
docker exec "$CONTAINER" php /var/www/html/docker/seed-emails.php

echo ""
echo "🎉 Done! Log in to Roundcube at http://localhost:8000"
echo ""
echo "Test accounts:"
echo "  - victor@example.test / password123"
echo "  - alice@example.test / password123"
echo "  - bob@example.test / password123"
