# CalDAV/CardDAV Setup Summary

## ✅ What's Been Added

1. **Calendar Plugin** - Already enabled with CalDAV support
   - Config: `roundcubemail/plugins/calendar/config.inc.php`
   - Current mode: `database` (local storage)
   - Can switch to `caldav` driver for server sync

2. **CardDAV Plugin** - Newly installed (RCMCardDAV)
   - Plugin: `roundcubemail/plugins/carddav/`
   - Config: `roundcubemail/plugins/carddav/config.inc.php`
   - Enabled in: `roundcubemail/config/config.inc.php`

## 🚀 Quick Start

### Current Setup (Ready to Use)
- **Calendar**: Local database storage (no external server needed)
- **CardDAV**: User-configurable via Roundcube Settings UI

### To Enable CalDAV Sync
1. Edit `roundcubemail/plugins/calendar/config.inc.php`
2. Change: `$config['calendar_driver'] = "caldav";`
3. Set server URL: `$config['calendar_caldav_server'] = "http://your-server:5232/";`

## 📖 Full Documentation

See `roundcubemail/CALDAV_CARDDAV_SETUP.md` for complete setup instructions.

