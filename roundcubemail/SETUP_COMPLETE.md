# ✅ CalDAV/CardDAV Setup Complete

## What Was Installed

### 1. Calendar Plugin
- **Status**: ✅ Enabled and configured
- **Location**: `plugins/calendar/`
- **Config**: `plugins/calendar/config.inc.php`
- **Mode**: Local database storage (no external server required)
- **Features**:
  - Create/edit/delete events
  - Recurring events
  - Reminders
  - Multiple calendars per user
  - iCal import/export

### 2. CardDAV Plugin (RCMCardDAV)
- **Status**: ✅ Installed with auto-setup
- **Location**: `plugins/carddav/`
- **Config**: `plugins/carddav/config.inc.php`
- **Features**:
  - Sync with external CardDAV servers
  - User-configurable via Settings UI
  - Support for multiple accounts
  - Automatic discovery of addressbooks

### 3. Automatic Database Initialization
- **Status**: ✅ Configured in docker-compose.yml
- **What it does**:
  - Automatically installs CardDAV composer dependencies
  - Automatically creates all CardDAV database tables
  - Runs on every container start (idempotent)
  - No manual setup required for new users

## Current Configuration

### Calendar
- **Driver**: `database` (local storage)
- **Works for**: Alice, Bob, and all future users
- **No setup required**: Login and start using calendar

### CardDAV
- **Default**: Not configured (users add their own servers)
- **Optional**: Users can add CardDAV servers via Settings → CardDAV
- **Supported servers**: Nextcloud, Radicale, Baikal, iCloud, Google

## Files Modified

```
roundcubemail/
├── config/config.inc.php                 # Added 'carddav' to plugins
├── plugins/
│   ├── calendar/config.inc.php           # Created with CalDAV examples
│   └── carddav/                          # Installed via git clone
│       ├── config.inc.php                # Created from dist
│       └── vendor/                       # Auto-installed via docker-compose
└── docker-compose.yml                    # Added auto-setup scripts
```

## How It Works

### On Container Start:
1. ✅ Install Roundcube dependencies (if missing)
2. ✅ Install CardDAV plugin dependencies (if missing)
3. ✅ Install JS/CSS dependencies
4. ✅ Compile LESS stylesheets
5. ✅ Initialize Roundcube database (if new)
6. ✅ Create CardDAV tables (if missing)

### For Each User:
- Login to Roundcube
- Calendar tab automatically works
- Create events, set reminders, etc.
- Optionally configure CardDAV servers in Settings

## Testing

### Test Calendar (Already Working)
1. Login as alice@example.test / password123
2. Click **Calendar** tab
3. Create a new event
4. It's saved in local database ✅

### Test Fresh Setup
```bash
# Stop containers
docker-compose down

# Remove database
rm roundcubemail/temp/roundcube.db

# Start fresh
docker-compose up -d

# Watch logs
docker-compose logs -f roundcube

# You should see:
# ✅ CardDAV dependencies installed
# ✅ CardDAV tables created
```

## Optional: Add CardDAV Server

If you want to sync calendars/contacts with external servers:

### Option 1: Install Radicale (CalDAV/CardDAV Server)

Add to `docker-compose.yml`:
```yaml
  radicale:
    image: tomsquest/docker-radicale:latest
    container_name: radicale
    ports:
      - "5232:5232"
    volumes:
      - radicale_data:/data
    restart: unless-stopped

volumes:
  radicale_data:
```

Create users:
```bash
docker exec -it radicale htpasswd -B -c /data/users alice
docker exec -it radicale htpasswd -B /data/users bob
```

### Option 2: Use Existing Server

Users can configure their own servers:
1. Settings → CardDAV
2. Click "Add CardDAV Account"
3. Enter server URL, username, password
4. Save

## Documentation

- **Full Setup Guide**: `roundcubemail/CALDAV_CARDDAV_SETUP.md`
- **RCMCardDAV Docs**: https://github.com/mstilkerich/rcmcarddav
- **Roundcube Calendar**: https://plugins.roundcube.net/packages/kolab/calendar

## Support

### Calendar Not Loading?
- Check `logs/errors.log`
- Verify CardDAV tables exist in database
- Try: `docker-compose restart roundcube`

### CardDAV Plugin Error?
- Check composer dependencies: `ls plugins/carddav/vendor/`
- Reinstall: `cd plugins/carddav && composer install --no-dev`

### Fresh Start?
```bash
docker-compose down
rm -rf roundcubemail/temp/roundcube.db
docker-compose up -d
# Everything initializes automatically!
```

---

**Status**: ✅ Complete and Ready to Use

- Calendar works out of the box
- CardDAV plugin ready for user configuration
- All future users get full setup automatically
- No manual database setup required

Enjoy your fully functional calendar and contact synchronization! 🎉
