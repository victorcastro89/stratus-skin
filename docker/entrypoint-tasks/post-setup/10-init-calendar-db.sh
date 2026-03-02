#!/usr/bin/env bash
set -euo pipefail

echo "[post-setup] Initializing calendar plugin database schema"
cd /var/www/html

if [[ ! -d "plugins/calendar/drivers/database/SQL" ]]; then
  echo "[post-setup] Calendar SQL directory not found, skipping"
  exit 0
fi

if ./bin/initdb.sh --dir=plugins/calendar/drivers/database/SQL; then
  echo "[post-setup] Calendar schema initialization complete"
else
  rc=$?
  if [[ "$rc" -eq 1 ]]; then
    echo "[post-setup] Calendar schema appears to be already initialized (exit 1)"
    exit 0
  fi

  echo "[post-setup] Calendar schema initialization failed with exit code $rc"
  exit "$rc"
fi
