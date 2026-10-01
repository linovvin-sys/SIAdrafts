#!/bin/sh
set -e

# Railway injects a dynamic $PORT at container start (not known at build
# time) and routes traffic to whatever port this container actually
# listens on -- the base image's Apache config hardcodes port 80, so this
# substitutes the real port in before Apache starts, falling back to 80
# for any other host (local `docker run`, etc.) where $PORT isn't set.
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# Diagnosing "AH00534: More than one MPM loaded" -- confirmed via the
# build log that mods-enabled only ever contains one MPM (mpm_prefork) at
# build time, so whatever's causing this has to be visible only at
# container start. Printed with `|| true` so a non-zero exit here (e.g.
# configtest itself failing) doesn't stop this script before the real
# `exec` below runs and the actual crash (with its own log line) happens.
echo "=== runtime: mods-enabled listing ==="
ls -la /etc/apache2/mods-enabled/ || true
echo "=== runtime: apache2ctl -M (loaded modules) ==="
apache2ctl -M 2>&1 || true
echo "=== runtime: apache2ctl configtest ==="
apache2ctl configtest 2>&1 || true
echo "=== end diagnostics ==="

exec "$@"
