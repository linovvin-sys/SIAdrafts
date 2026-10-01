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

exec "$@"
