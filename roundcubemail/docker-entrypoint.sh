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

_user_exists() {
  php -r "
    \$db = new PDO('sqlite:$APP/temp/roundcube.db');
    \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$stmt = \$db->prepare('SELECT 1 FROM users WHERE username = :username AND mail_host = :mail_host LIMIT 1');
    \$stmt->execute([':username' => '$1', ':mail_host' => '$2']);
    exit(\$stmt->fetchColumn() ? 0 : 1);
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

# ── Seed default users ─────────────────────────────────────────────────────
# Pre-creates Roundcube user rows so users can log in without a first-login
# auto-create. Passwords are handled by the mailserver (postfix-accounts.cf).
_seed_user() {
  local username="$1" mail_host="$2"
  if ! _user_exists "$username" "$mail_host"; then
    php -r "
      \$db = new PDO('sqlite:$APP/temp/roundcube.db');
      \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      \$stmt = \$db->prepare('INSERT INTO users (username, mail_host, created) VALUES (:username, :mail_host, datetime(\'now\'))');
      \$stmt->execute([':username' => '$username', ':mail_host' => '$mail_host']);
    " && echo "✅ Added $username"
  else
    echo "✅ $username already exists"
  fi
}

if [ -f "$APP/temp/roundcube.db" ] && _table_exists users; then
  echo "Ensuring default users..."
  _seed_user "victor@example.test" "mailserver"
  _seed_user "alice@example.test"  "mailserver"
  _seed_user "bob@example.test"    "mailserver"
fi

# ── CardDAV plugin tables ─────────────────────────────────────────────────────
# Use INIT-currentschema (single complete schema) — same approach as plugin's Makefile:
#   sed 's/TABLE_PREFIX//g' dbmigrations/INIT-currentschema/sqlite3.sql | sqlite3 <db>
if [ -d "$APP/plugins/carddav" ] && [ -f "$APP/temp/roundcube.db" ]; then
  echo "Checking CardDAV tables..."
  if ! _table_exists carddav_accounts; then
    echo "🗄️  Installing CardDAV tables..."
    CARDDAV_INIT="$APP/plugins/carddav/dbmigrations/INIT-currentschema/sqlite3.sql"
    if [ -f "$CARDDAV_INIT" ]; then
      php -r "
        \$db = new PDO('sqlite:$APP/temp/roundcube.db');
        \$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$sql = str_replace('TABLE_PREFIX', '', file_get_contents('$CARDDAV_INIT'));
        \$db->exec(\$sql);
      " && echo "✅ CardDAV tables created"
    else
      echo "⚠️  CardDAV INIT schema not found: $CARDDAV_INIT"
    fi
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

  victor@example.test / password123
  alice@example.test  / password123
  bob@example.test    / password123
========================================

EOF

# ── Apache ────────────────────────────────────────────────────────────────────
echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null 2>&1 || true
exec apache2-foreground
