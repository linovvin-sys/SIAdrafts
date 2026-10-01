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
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install mysqli zip curl \
    && apt-get purge -y --auto-remove libzip-dev libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

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

# The bare Railway domain would otherwise hit an empty docroot; send it to
# the app's entry page.
RUN echo '<?php header("Location: /SIAdrafts/Frontend/View/index", true, 302);' > /var/www/html/index.php

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
