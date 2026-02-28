# CalDAV & CardDAV Setup Guide

This Roundcube installation now includes support for:
- **CalDAV**: Calendar synchronization with external servers
- **CardDAV**: Contact/addressbook synchronization with external servers

## What's Installed

### Calendar Plugin (with CalDAV)
- **Location**: `plugins/calendar/`
- **Config**: `plugins/calendar/config.inc.php`
- **Drivers**: database (local), caldav (sync), kolab

### CardDAV Plugin (RCMCardDAV)
- **Location**: `plugins/carddav/`
- **Config**: `plugins/carddav/config.inc.php`
- **Repository**: https://github.com/mstilkerich/rcmcarddav

## Quick Start

### Option 1: Using Database Only (No External Server)
**Current default setup** - calendars and contacts stored locally in Roundcube's database.

✅ Already configured and ready to use!
- Calendar: Use the calendar interface to create events
- Contacts: Use the addressbook to add contacts

### Option 2: Connect to CalDAV/CardDAV Server

#### Popular CalDAV/CardDAV Servers:
1. **Nextcloud** (self-hosted or cloud)
2. **Radicale** (lightweight, Python-based)
3. **Baikal** (lightweight, PHP-based)
4. **SOGo** (enterprise groupware)
5. **iCloud** (Apple users)
6. **Google Calendar/Contacts**

## Setup Instructions

### 1. Install a CalDAV/CardDAV Server (Optional)

#### Quick Setup with Radicale (Recommended for Testing)

Add this service to your `docker-compose.yml`:

```yaml
  # CalDAV/CardDAV Server (Radicale)
  radicale:
    image: tomsquest/docker-radicale:latest
    container_name: radicale
    ports:
      - "5232:5232"
    volumes:
      - radicale_data:/data
    environment:
      - RADICALE_AUTH_TYPE=htpasswd
    restart: unless-stopped
```

Add to volumes section:
```yaml
volumes:
  radicale_data:
```

Then create a user:
```bash
docker exec -it radicale htpasswd -B -c /data/users alice
# Enter password when prompted
```

Access Radicale at: http://localhost:5232

### 2. Configure Calendar for CalDAV

Edit `plugins/calendar/config.inc.php`:

```php
// Change driver from 'database' to 'caldav'
$config['calendar_driver'] = "caldav";

// Set your CalDAV server URL
// Examples:
$config['calendar_caldav_server'] = "http://localhost:5232/";  // Radicale
// $config['calendar_caldav_server'] = "https://nextcloud.example.com/remote.php/dav/";  // Nextcloud
// $config['calendar_caldav_server'] = "https://p##-caldav.icloud.com/";  // iCloud (replace ##)
```

### 3. Configure CardDAV for Contacts

The CardDAV plugin allows users to configure their own servers through the UI.

To pre-configure a server for all users, edit `plugins/carddav/config.inc.php`:

```php
// Example preset for Radicale
$prefs['_GLOBAL']['radicale'] = [
    'name'         => 'Radicale Server',
    'username'     => '%u',  // Use roundcube username
    'password'     => '%p',  // Use roundcube password
    'url'          => 'http://radicale:5232/',
    'active'       => true,
    'carddav_name_only' => true,
    'use_categories' => true,
    'readonly'     => false,
    'refresh_time' => '01:00:00',
];
```

### 4. Restart Roundcube

```bash
docker-compose restart roundcube
```

## User Configuration

### Adding CardDAV Accounts (Via UI)

1. Login to Roundcube
2. Go to **Settings** → **CardDAV**
3. Click **Add CardDAV Account**
4. Fill in:
   - **Name**: My Calendar Server
   - **URL**: `http://localhost:5232/`
   - **Username**: your username
   - **Password**: your password
5. Click **Save**

The plugin will discover available addressbooks automatically.

### Supported Server Examples

#### Nextcloud
```
URL: https://nextcloud.example.com/remote.php/dav/
Username: your-nextcloud-username
Password: your-nextcloud-password
```

#### Radicale
```
URL: http://localhost:5232/
Username: alice
Password: password123
```

#### iCloud
```
CalDAV URL: https://caldav.icloud.com/
CardDAV URL: https://contacts.icloud.com/
Username: your@icloud.com
Password: app-specific password (not your iCloud password!)
```

Note: iCloud requires app-specific passwords. Generate one at https://appleid.apple.com

## Troubleshooting

### Calendar Issues

**Problem**: Events not syncing
- Check `plugins/calendar/config.inc.php` has correct `calendar_driver` and URL
- Check server is accessible: `curl http://localhost:5232/`
- Check Roundcube logs: `logs/errors.log`

**Problem**: "CalDAV server not found"
- Verify URL includes protocol (`http://` or `https://`)
- Ensure URL ends with `/` if required by server
- Test with a CalDAV client like Thunderbird first

### CardDAV Issues

**Problem**: No addressbooks appear
- The CardDAV server must support discovery
- Try manually adding addressbook URL in plugin settings
- Check `logs/errors.log` for authentication errors

**Problem**: Authentication failed
- Verify username/password are correct
- Some servers require email address as username
- Check if server requires app-specific passwords

### Database Initialization

If CardDAV plugin reports missing tables:

```bash
# Run CardDAV database migrations
docker exec -it roundcube-dev bash
cd plugins/carddav
php ../../bin/updatedb.sh --dir=dbmigrations --package=carddav
```

## Files Modified

- ✅ `/config/config.inc.php` - Added `carddav` to plugins list
- ✅ `/plugins/calendar/config.inc.php` - Created with CalDAV examples
- ✅ `/plugins/carddav/` - Installed RCMCardDAV plugin

## Additional Resources

- **RCMCardDAV Documentation**: https://github.com/mstilkerich/rcmcarddav
- **Roundcube Calendar Plugin**: https://github.com/roundcube/roundcubemail/tree/master/plugins/calendar
- **Radicale Documentation**: https://radicale.org/v3.html
- **Nextcloud CalDAV**: https://docs.nextcloud.com/server/latest/user_manual/en/groupware/calendar.html

## Testing Your Setup

1. **Calendar**: 
   - Create an event in Roundcube
   - Check if it appears in your CalDAV server's web interface
   - Create event in CalDAV server, verify it appears in Roundcube

2. **Contacts**:
   - Add a contact in Roundcube addressbook
   - Check CardDAV server for the contact
   - Add contact via CalDAV client, verify in Roundcube

## Current Configuration

- **Calendar Driver**: `database` (local storage)
  - To enable CalDAV sync: Edit `plugins/calendar/config.inc.php`
- **CardDAV**: Plugin installed, user-configurable via Settings
  - Pre-configure servers in `plugins/carddav/config.inc.php`

Enjoy your synchronized calendars and contacts! 🎉
