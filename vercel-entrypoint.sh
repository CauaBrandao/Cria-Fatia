#!/bin/sh
set -eu

if [ -n "${DATABASE_URL:-}${MYSQLHOST:-}${TIDB_HOST:-}" ]; then
  php /app/public/database/initialize.php
fi

exec frankenphp run --config /etc/caddy/Caddyfile
