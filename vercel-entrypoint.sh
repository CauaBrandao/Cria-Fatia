#!/bin/sh
set -eu

exec frankenphp run --config /etc/caddy/Caddyfile
