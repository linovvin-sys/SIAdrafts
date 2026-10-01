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

# Confirmed via the actual build log (railway logs --build) that the base
# image + the apt-get step above already leave exactly one MPM enabled
# (mpm_prefork, nothing else) -- three rounds of "fix" attempts here
# (a2dismod, removing symlinks, rebuilding them manually) were all
# solving a problem that didn't exist in the static config, and none of
# them changed the runtime crash at all. Reverted back to not touching
# MPM modules -- whatever's actually causing "More than one MPM loaded"
# happens at container start, not at build time, so it has to be
# diagnosed from railway-entrypoint.sh instead (see the apache2ctl calls
# added there).
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

# The docroot's own bare / has nothing in it by design (same as local dev
# -- hitting bare localhost:8888/ shows nothing either; the real app
# always lives under /SIAdrafts/...), which 403s on a public domain with
# no SIAdrafts/ path typed in: "Cannot serve directory /var/www/html/:
# No matching DirectoryIndex found, and server-generated directory index
# forbidden." A real visitor to the Railway domain's bare root needs
# somewhere to land, so this sends them to the actual public landing
# page instead of a dead end.
RUN echo '<?php header("Location: /SIAdrafts/Frontend/View/index.php"); exit;' > /var/www/html/index.php

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
# RW_HTTPS, not just HTTPS: mod_rewrite's %{HTTPS} is a special variable
# that only ever reflects mod_ssl's own real-TLS-handshake state -- it
# ignores the generic HTTPS env var entirely, unlike PHP's $_SERVER and
# Header's `env=` condition, which both read the generic env table and
# so already worked correctly from the HTTPS=on line alone. Confirmed
# live: the HSTS header appeared correctly, but a %{HTTPS}-conditioned
# RewriteCond in .htaccess still always evaluated to "off" behind
# Railway's proxy. A distinctly-named variable, read via %{ENV:RW_HTTPS}
# instead of the special %{HTTPS} token, is what .htaccess's redirect
# rule actually needs.
RUN printf 'SetEnvIf X-Forwarded-Proto "https" HTTPS=on\nSetEnvIf X-Forwarded-Proto "https" RW_HTTPS=on\n' > /etc/apache2/conf-enabled/railway-trust-proxy.conf

COPY railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/railway-entrypoint.sh"]
CMD ["apache2-foreground"]
