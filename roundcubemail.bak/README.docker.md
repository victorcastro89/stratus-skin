# Roundcube Development Environment with Docker

This Docker setup provides a live-reloading development environment for Roundcube, specifically designed for skin development while using external IMAP/SMTP servers.

## Features

- 🔄 **Live Reloading** - Changes to skins, plugins, and config are immediately reflected
- 🌐 **External IMAP/SMTP** - Connect to your existing mail servers
- 🐳 **Docker-based** - Consistent development environment
- 🎨 **Skin Development** - Perfect for creating and testing custom skins
- 📦 **SQLite Database** - No separate database server needed for development

## Prerequisites

- Docker and Docker Compose installed
- Access to external IMAP and SMTP servers

## Quick Start

### 1. Configure External Mail Servers

Copy the example environment file and update with your mail server details:

```bash
cp .env.example .env
```

Edit `.env` file with your IMAP/SMTP server information:

```env
ROUNDCUBE_DEFAULT_HOST=ssl://imap.example.com
ROUNDCUBE_DEFAULT_PORT=993
ROUNDCUBE_SMTP_SERVER=tls://smtp.example.com
ROUNDCUBE_SMTP_PORT=587
```

### 2. Update Configuration

Edit `config/config.dev.inc.php` and replace the IMAP/SMTP settings:

```php
// Line 23: Set your IMAP server
$config['imap_host'] = 'ssl://your-imap-server.com:993';

// Line 37: Set your SMTP server
$config['smtp_host'] = 'tls://your-smtp-server.com:587';
```

**For self-signed certificates**, uncomment these lines in both `imap_conn_options` and `smtp_conn_options`:

```php
'verify_peer'       => false,
'verify_peer_name'  => false,
'allow_self_signed' => true,
```

### 3. Build and Start

```bash
# Build the Docker image
docker-compose build

# Start the development server
docker-compose up
```

The first startup will:
- Install PHP dependencies via Composer
- Create initial configuration
- Initialize SQLite database

### 4. Access Roundcube

Open your browser and go to:
- **Roundcube**: http://localhost:8000
- **Installer** (first time): http://localhost:8000/installer

Login with your IMAP credentials.

## Development Workflow

### Skin Development

All skins are mounted with live reloading. Changes are immediately visible:

```bash
# Edit skin files
vim skins/elastic/styles/styles.css
# Refresh browser - changes appear instantly
```

Available skins in `./skins/`:
- elastic (default modern skin)
- larry (classic skin)
- And other custom skins

To switch skins, update in `config/config.inc.php` or `.env`:
```php
$config['skin'] = 'elastic';  // or 'larry', etc.
```

### Plugin Development

Plugins are also live-mounted:

```bash
# Edit or create plugins
vim plugins/myplugin/myplugin.php
```

Enable plugins in `config/config.inc.php`:
```php
$config['plugins'] = ['archive', 'zipdownload', 'myplugin'];
```

### Configuration Changes

Edit `config/config.inc.php` or `config/config.dev.inc.php` directly. Changes take effect on next page load.

### Viewing Logs

```bash
# View PHP/Roundcube logs
docker-compose exec roundcube tail -f /var/www/html/logs/errors.log

# View Apache access logs
docker-compose logs -f roundcube
```

## Directory Structure

```
.
├── skins/              # Skin files (live-reloaded)
├── plugins/            # Plugin files (live-reloaded)
├── config/             # Configuration files (live-reloaded)
│   └── config.dev.inc.php
├── temp/               # Temporary files & SQLite DB
├── logs/               # Application logs
├── Dockerfile
├── docker-compose.yml
└── .env               # Environment variables
```

## Common Tasks

### Reset Database

```bash
docker-compose down -v
docker-compose up
```

### Install Additional PHP Dependencies

```bash
docker-compose exec roundcube composer require vendor/package
```

### Run Composer Scripts

```bash
docker-compose exec roundcube composer update
```

### Shell Access

```bash
docker-compose exec roundcube bash
```

## Troubleshooting

### Cannot Connect to IMAP/SMTP

1. Check your server addresses in `config/config.dev.inc.php`
2. Verify ports are correct (993 for IMAP, 587/465 for SMTP)
3. For self-signed certificates, disable SSL verification (see config)
4. Check Docker container can reach external servers:
   ```bash
   docker-compose exec roundcube ping your-imap-server.com
   ```

### Permission Errors

```bash
docker-compose exec roundcube chown -R www-data:www-data /var/www/html/temp /var/www/html/logs
```

### Changes Not Appearing

- For skin changes: Hard refresh browser (Ctrl+Shift+R)
- For PHP changes: They should be immediate
- Check logs: `docker-compose logs -f`

### Port 8000 Already in Use

Edit `docker-compose.yml` and change the port:
```yaml
ports:
  - "8080:80"  # Use port 8080 instead
```

## Using MySQL Instead of SQLite

Uncomment the `db` service in `docker-compose.yml` and update `config/config.dev.inc.php`:

```php
$config['db_dsnw'] = 'mysql://roundcube:roundcube@db/roundcubemail';
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

## Production Deployment

⚠️ **This setup is for DEVELOPMENT ONLY**. Do not use in production without:
- Proper SSL certificates
- Secure database configuration
- Removing debug settings
- Disabling the installer
- Proper security hardening

## Additional Resources

- [Roundcube Wiki](https://github.com/roundcube/roundcubemail/wiki)
- [Skin Development](https://github.com/roundcube/roundcubemail/wiki/Skins)
- [Plugin Development](https://github.com/roundcube/roundcubemail/wiki/Plugin-API)

## Support

For issues specific to this Docker setup, check:
- Container logs: `docker-compose logs`
- Application logs: `./logs/errors.log`
- Apache logs: `docker-compose exec roundcube tail -f /var/log/apache2/error.log`
