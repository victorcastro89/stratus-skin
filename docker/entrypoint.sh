#!/usr/bin/env bash
# docker/entrypoint.sh
# Installs Kolab calendar plugins from source and prepares Roundcube runtime.
set -euo pipefail

RC_ROOT="/var/www/html"
KOLAB_SRC="/tmp/roundcubemail-plugins-kolab"

cd "$RC_ROOT"

# 1) Get Kolab calendar plugin sources from git and copy into Roundcube plugins/
if [ ! -f "$RC_ROOT/plugins/calendar/calendar.php" ] \
   || [ ! -f "$RC_ROOT/plugins/libcalendaring/libcalendaring.php" ] \
   || [ ! -f "$RC_ROOT/plugins/libkolab/libkolab.php" ]; then
    echo "[entrypoint] Cloning Kolab plugins source …"
    rm -rf "$KOLAB_SRC"
    git clone --depth 1 https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git "$KOLAB_SRC"

    echo "[entrypoint] Installing calendar, libcalendaring, and libkolab plugins …"
    rm -rf "$RC_ROOT/plugins/calendar" "$RC_ROOT/plugins/libcalendaring" "$RC_ROOT/plugins/libkolab"
    cp -r "$KOLAB_SRC/plugins/calendar" "$RC_ROOT/plugins/"
    cp -r "$KOLAB_SRC/plugins/libcalendaring" "$RC_ROOT/plugins/"
    cp -r "$KOLAB_SRC/plugins/libkolab" "$RC_ROOT/plugins/"
else
    echo "[entrypoint] Kolab plugins already present — skipping git install."
fi

# 2) Initialize calendar database tables (idempotent — skip if already present)
SQLITE_DB="/var/roundcube/db/sqlite.db"
if [ -f "$RC_ROOT/bin/initdb.sh" ] && [ -d "$RC_ROOT/plugins/calendar/drivers/database/SQL" ]; then
    if [ -f "$SQLITE_DB" ] && php -r '
        $db = new PDO("sqlite:/var/roundcube/db/sqlite.db");
        $q = $db->query("SELECT name FROM sqlite_master WHERE type=\"table\" AND name=\"calendars\"");
        exit($q && $q->fetchColumn() ? 0 : 1);
    ' 2>/dev/null; then
        echo "[entrypoint] Calendar tables already exist — skipping initdb."
    else
        echo "[entrypoint] Initializing calendar database tables …"
        "$RC_ROOT/bin/initdb.sh" --dir="$RC_ROOT/plugins/calendar/drivers/database/SQL" || true
    fi
fi

# 3) Build Elastic skin CSS for libkolab
if [ -f "$RC_ROOT/plugins/libkolab/skins/elastic/libkolab.less" ]; then
    echo "[entrypoint] Building libkolab Elastic CSS …"
    lessc --rewrite-urls=all \
        "$RC_ROOT/plugins/libkolab/skins/elastic/libkolab.less" \
        > "$RC_ROOT/plugins/libkolab/skins/elastic/libkolab.min.css"
fi

# 4) Build base Elastic skin CSS files (git sources do not ship compiled CSS)
if [ -d "$RC_ROOT/skins/elastic/styles" ]; then
    if [ ! -f "$RC_ROOT/skins/elastic/styles/styles.css" ] \
       || [ "$RC_ROOT/skins/elastic/styles/styles.less" -nt "$RC_ROOT/skins/elastic/styles/styles.css" ]; then
        echo "[entrypoint] Building Elastic styles.css …"
        lessc "$RC_ROOT/skins/elastic/styles/styles.less" > "$RC_ROOT/skins/elastic/styles/styles.css"
    fi

    if [ ! -f "$RC_ROOT/skins/elastic/styles/print.css" ] \
       || [ "$RC_ROOT/skins/elastic/styles/print.less" -nt "$RC_ROOT/skins/elastic/styles/print.css" ]; then
        echo "[entrypoint] Building Elastic print.css …"
        lessc "$RC_ROOT/skins/elastic/styles/print.less" > "$RC_ROOT/skins/elastic/styles/print.css"
    fi

    if [ ! -f "$RC_ROOT/skins/elastic/styles/embed.css" ] \
       || [ "$RC_ROOT/skins/elastic/styles/embed.less" -nt "$RC_ROOT/skins/elastic/styles/embed.css" ]; then
        echo "[entrypoint] Building Elastic embed.css …"
        lessc "$RC_ROOT/skins/elastic/styles/embed.less" > "$RC_ROOT/skins/elastic/styles/embed.css"
    fi
fi

# Install JS deps if needed
if [ ! -f "$RC_ROOT/program/js/jquery.min.js" ] \
   || [ ! -f "$RC_ROOT/skins/elastic/deps/bootstrap.min.css" ] \
   || [ ! -f "$RC_ROOT/skins/elastic/deps/bootstrap.bundle.min.js" ]; then
    echo "[entrypoint] Installing javascript dependencies via bin/install-jsdeps.sh …"
    timeout 300 php "$RC_ROOT/bin/install-jsdeps.sh" || true
fi

# Ensure composer.json exists (Roundcube ships composer.json-dist)
if [ ! -f "$RC_ROOT/composer.json" ] && [ -f "$RC_ROOT/composer.json-dist" ]; then
    echo "[entrypoint] Copying composer.json-dist → composer.json …"
    cp "$RC_ROOT/composer.json-dist" "$RC_ROOT/composer.json"
fi

# Ensure Composer autoload is present and critical classes resolve
if [ ! -f "$RC_ROOT/vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/autoload.php missing — rebuilding Composer autoload …"
    if [ -d "$RC_ROOT/vendor" ]; then
        composer dump-autoload --working-dir="$RC_ROOT" --no-interaction --optimize || true
    fi
fi

if ! php -r 'require "/var/www/html/vendor/autoload.php"; exit((class_exists("Mail_mime") && class_exists("Sabre\\VObject\\Property\\Text")) ? 0 : 1);' >/dev/null 2>&1; then
    echo "[entrypoint] Composer classes missing — running composer install …"
    composer install \
        --working-dir="$RC_ROOT" \
        --no-interaction \
        --prefer-dist \
        --no-progress \
        --optimize-autoloader

    # Kolab plugins are git-cloned (not installed via Composer), so their
    # composer.json dependencies are not automatically resolved.  Require them
    # explicitly so sabre/vobject, http_request2, etc. end up in vendor/.
    echo "[entrypoint] Installing Kolab plugin Composer dependencies …"
    composer require --working-dir="$RC_ROOT" \
        --no-interaction --prefer-dist --no-progress --update-no-dev \
        "sabre/vobject:~4.5.1" \
        "pear/http_request2:~2.7.0" \
        "caxy/php-htmldiff:~0.1" \
        "lolli42/finediff:~1.0.3"
fi

# Roundcube templates reference /deps/* from the web root.
if [ ! -e "$RC_ROOT/public_html/deps" ]; then
    ln -s ../skins/elastic/deps "$RC_ROOT/public_html/deps"
fi

mkdir -p /var/roundcube/db /var/roundcube/logs /tmp/roundcube-temp
chown -R www-data:www-data /var/roundcube /tmp/roundcube-temp 2>/dev/null || true

chown -R www-data:www-data "$RC_ROOT/temp" "$RC_ROOT/logs" 2>/dev/null || true

echo "[entrypoint] ✅ Ready — starting Apache …"
exec apache2-foreground
