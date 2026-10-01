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

# "AH00534: More than one MPM loaded" -- confirmed by direct observation
# (three build-time fix attempts, each verified clean via `ls
# mods-enabled` right after running) that the static image, immediately
# after it's built, has only mpm_prefork enabled. Yet mpm_event.load is
# present again by the time THIS script runs, with a timestamp matching
# the ORIGINAL base image build (not this build), not anything this
# Dockerfile did -- something in the gap between image build and
# container start keeps restoring it, never pinned down exactly where.
# Doing the same fix here instead, as the very last thing before Apache
# actually reads this config, removes that gap entirely regardless of
# the cause: nothing can reintroduce mpm_event after this point because
# nothing else runs before the real `exec` below.
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Backend/uploads is now a mounted Railway volume (added so applicant
# document uploads survive redeploys, instead of living on the
# container's own throwaway disk and vanishing on the next deploy --
# confirmed live that's exactly what was happening). The Dockerfile's own
# chown only touched that path inside the IMAGE layer; a volume mounts
# its own separate storage over that path at container start, owned by
# root by default, which would make every upload fail with a permission
# error. Re-applying chown here, after the mount already exists, is what
# actually makes it writable by the user PHP/Apache runs as.
chown -R www-data:www-data /var/www/html/SIAdrafts/Backend/uploads

exec "$@"
