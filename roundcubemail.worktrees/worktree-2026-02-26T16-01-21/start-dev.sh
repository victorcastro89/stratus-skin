#!/bin/bash

# Roundcube Docker Development - Quick Start Script

set -e

echo "========================================="
echo "Roundcube Docker Development Setup"
echo "========================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose found"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env with your IMAP/SMTP server details!"
    echo ""
fi

# Check if config.inc.php exists
if [ ! -f config/config.inc.php ]; then
    echo "📝 Creating config.inc.php from config.dev.inc.php..."
    cp config/config.dev.inc.php config/config.inc.php
    echo "⚠️  Please edit config/config.inc.php with your mail server details!"
    echo ""
fi

echo "🔨 Building Docker image..."
docker-compose build

echo ""
echo "🚀 Starting Roundcube development environment..."
docker-compose up -d

echo ""
echo "========================================="
echo "✅ Roundcube is starting!"
echo "========================================="
echo ""
echo "Access Roundcube at: http://localhost:8000"
echo ""
echo "📋 Next steps:"
echo "   1. Edit .env with your IMAP/SMTP server details"
echo "   2. Edit config/config.inc.php if needed"
echo "   3. Open http://localhost:8000 in your browser"
echo ""
echo "📊 Useful commands:"
echo "   View logs:        docker-compose logs -f"
echo "   Stop:             docker-compose down"
echo "   Restart:          docker-compose restart"
echo "   Shell access:     docker-compose exec roundcube bash"
echo ""
echo "Happy coding! 🎨"
echo ""
