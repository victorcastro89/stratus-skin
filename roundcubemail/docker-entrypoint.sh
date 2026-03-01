#!/bin/bash
# docker-entrypoint.sh — Dev container init for Roundcube.
# Extracted from docker-compose `command:` so the compose file stays clean.
set -e

APP=/var/www/html

# ── Permissions ───────────────────────────────────────────────────────────────
mkdir -p "$APP/temp" "$APP/logs"
chown -R www-data:www-data "$APP/temp" "$APP/logs"
chmod 770 "$APP/temp" "$APP/logs"

# ── PHP dependencies ──────────────────────────────────────────────────────────
if [ ! -d "$APP/vendor" ]; then
  echo "📦 Installing PHP dependencies..."
  composer install --no-interaction --working-dir="$APP"
else
  echo "✅ PHP dependencies already installed"
fi

# ── CardDAV plugin dependencies ───────────────────────────────────────────────
if [ -d "$APP/plugins/carddav" ] && [ ! -d "$APP/plugins/carddav/vendor" ]; then
  echo "📦 Installing CardDAV plugin dependencies..."
  composer install --no-dev --no-interaction --working-dir="$APP/plugins/carddav"
else
  echo "✅ CardDAV dependencies already installed"
fi

# ── JS/CSS dependencies ───────────────────────────────────────────────────────
if [ ! -f "$APP/program/js/jquery.min.js" ] || [ ! -f "$APP/skins/elastic/deps/bootstrap.min.css" ]; then
  echo "📦 Installing JS/CSS dependencies..."
  "$APP/bin/install-jsdeps.sh"
else
  echo "✅ JS/CSS dependencies already installed"
fi

# ── Compile elastic LESS ──────────────────────────────────────────────────────
if [ ! -f "$APP/skins/elastic/styles/styles.css" ]; then
  echo "🎨 Compiling elastic skin LESS..."
  lessc "$APP/skins/elastic/styles/styles.less" "$APP/skins/elastic/styles/styles.css"
else
  echo "✅ Elastic CSS already compiled"
fi

# ── Helper: check if a table exists in the SQLite DB ─────────────────────────
_table_exists() {
  php -r "
    \$db = new PDO('sqlite:$APP/temp/roundcube.db');
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    try { \$db->query('SELECT 1 FROM $1 LIMIT 1'); exit(0); }
    catch (Exception \$e) { exit(1); }
  " 2>/dev/null
}

# ── Database schema ───────────────────────────────────────────────────────────
if _table_exists users; then
  echo "✅ Database schema already exists"
else
  echo "🗄️  Initializing database..."
  rm -f "$APP/temp/roundcube.db"
  "$APP/bin/initdb.sh" --dir=SQL || true
fi

# Ensure DB is writable by Apache
if [ -f "$APP/temp/roundcube.db" ]; then
  chown www-data:www-data "$APP/temp/roundcube.db" 2>/dev/null || true
  chmod 660 "$APP/temp/roundcube.db" 2>/dev/null || true
fi

# ── CardDAV plugin tables ─────────────────────────────────────────────────────
if [ -d "$APP/plugins/carddav" ] && [ -f "$APP/temp/roundcube.db" ]; then
  echo "Checking CardDAV tables..."
  if ! _table_exists carddav_accounts; then
    echo "🗄️  Installing CardDAV tables..."
    for migration in \
      "$APP/plugins/carddav/dbmigrations/0000-dbinit/sqlite3.sql" \
      "$APP/plugins/carddav/dbmigrations/0017-accountentities/sqlite3.sql"; do
      if [ -f "$migration" ]; then
        php -r "
          \$db = new PDO('sqlite:$APP/temp/roundcube.db');
          \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          \$db->exec(str_replace('TABLE_PREFIX', '', file_get_contents('$migration')));
        " && echo "  ✅ Applied $(basename "$(dirname "$migration")")"
      fi
    done
  else
    echo "✅ CardDAV tables already exist"
  fi
fi

# ── Calendar plugin tables ────────────────────────────────────────────────────
if [ -d "$APP/plugins/calendar" ] && [ -f "$APP/temp/roundcube.db" ]; then
  echo "Checking calendar tables..."
  if ! _table_exists calendars; then
    "$APP/bin/initdb.sh" --dir=plugins/calendar/drivers/database/SQL \
      && echo "✅ Calendar tables created" \
      || echo "⚠️  Calendar initialization skipped"
  else
    echo "✅ Calendar tables already exist"
  fi
fi

# ── Banner ────────────────────────────────────────────────────────────────────
cat <<EOF

========================================
  Roundcube Dev  →  http://localhost:8000

  alice@example.test / password123
  bob@example.test   / password123
========================================

EOF

# ── Apache ────────────────────────────────────────────────────────────────────
echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null 2>&1 || true
exec apache2-foreground
