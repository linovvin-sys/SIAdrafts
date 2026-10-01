# Matches the local dev target exactly (PHP 8.3, Apache with mod_rewrite/
# mod_headers) so every .htaccess rule -- clean URLs, the CSP/HSTS/
# X-Frame-Options headers, the dotfile/uploads lockdown -- keeps working
# unchanged in production. Railway's auto-detected PHP builder runs on
# Caddy instead, which doesn't read .htaccess at all; this Dockerfile
# exists specifically to avoid that silent regression.
FROM php:8.3-apache

# mysqli (the app's only DB driver), zip + curl (DOCX/PPTX quiz-generation
# parsing, PayMongo/reCAPTCHA/Gemini API calls) aren't compiled into the
# base image by default. mbstring is omitted deliberately -- it's already
# built into this image's core PHP build, and explicitly reinstalling it
# fails the build rather than being a harmless no-op.
#
# Deliberately NOT purging libzip-dev/libcurl4-openssl-dev afterward: the
# compiled zip.so extension links against libzip's runtime .so at
# container start, not just at compile time, and `apt-get purge
# --auto-remove` cascades to remove that runtime library along with the
# dev headers -- it did exactly that on the first build, and the
# extension failed to load with "libzip.so.5: cannot open shared object
# file" even though the install step itself had reported success. The
# few MB these dev packages cost is a better trade than a silently broken
# extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install mysqli zip curl \
    && rm -rf /var/lib/apt/lists/*

# The apt-get install above triggers Debian's apache2 package postinst
# script, which silently re-enables mpm_event as a default alongside this
# base image's required mpm_prefork (mod_php isn't thread-safe, so
# php:apache ships with prefork) -- Apache then refuses to start at all
# with "More than one MPM loaded." Force prefork back to being the only
# one enabled, explicitly, regardless of what the package manager did.
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork rewrite headers

# AllowOverride is None in this image's default vhost -- without this,
# .htaccess is silently ignored in its entirety (no error, every rewrite
# rule and security header just stops applying) even though the file is
# sitting right there and is perfectly valid.
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Mirrors the exact local docroot layout (htdocs/ containing SIAdrafts/ as
# a subfolder, reached via /SIAdrafts/... URLs) on purpose -- every
# absolute path and redirect in the app is hardcoded with that prefix, so
# matching the existing layout avoids having to rewrite hundreds of links
# instead of just reproducing what already works.
WORKDIR /var/www/html
COPY SIAdrafts/ /var/www/html/SIAdrafts/

# composer.json requires smalot/pdfparser, but the committed composer.lock
# predates that requirement being added and doesn't actually include it --
# a plain `composer install` refuses to run at all when the lock file is
# out of sync with composer.json, which would hard-fail this build. Updating
# just that one package resolves the drift without silently re-pinning
# every other dependency to a newer version in the process.
RUN cd /var/www/html/SIAdrafts \
    && composer update smalot/pdfparser --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/html/SIAdrafts/storage /var/www/html/SIAdrafts/Backend/uploads

# Railway's edge terminates TLS and forwards plain HTTP to this container
# with an X-Forwarded-Proto header -- Apache's own mod_ssl never sees a
# real TLS handshake here, so $_SERVER['HTTPS'] would otherwise never get
# set, which would silently disable both the HSTS header (.htaccess's
# `env=HTTPS` condition) and the session cookie's Secure flag
# (Backend/env_security.php's apply_https_cookie_security(), which also
# keys off $_SERVER['HTTPS']).
#
# This trust decision lives HERE, in Docker-image-specific Apache config,
# deliberately NOT inside the app's own committed .htaccess: it's only
# safe in Railway's specific network model, where this container has no
# public IP of its own and every request genuinely passed through
# Railway's proxy first. It would NOT be safe to ship inside the portable
# .htaccess if this app is ever deployed on a traditional host with a
# public IP instead, where a client could forge this header directly
# against Apache with no real proxy in between.
RUN echo 'SetEnvIf X-Forwarded-Proto "https" HTTPS=on' > /etc/apache2/conf-enabled/railway-trust-proxy.conf

COPY railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/railway-entrypoint.sh"]
CMD ["apache2-foreground"]
