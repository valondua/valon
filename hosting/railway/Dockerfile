# Exact PHP base used by the active production build (2026-08-30).
FROM php:8.3-apache@sha256:060ed9c0f6e4bbe4f8b25a34ca1ec596b96d8f4011cf7ee7eb6b7eecf01cb74f

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev libzip-dev \
        libicu-dev libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j4 mysqli gd zip exif intl

# Official PECL archive fetched before building; no PECL channel/network lookup.
COPY imagick-3.8.1.tgz /tmp/imagick-3.8.1.tgz
RUN echo '3a3587c0a524c17d0dad9673a160b90cd776e836838474e173b549ed864352ee  /tmp/imagick-3.8.1.tgz' | sha256sum -c - \
    && tar -xzf /tmp/imagick-3.8.1.tgz -C /usr/src \
    && cd /usr/src/imagick-3.8.1 \
    && phpize \
    && ./configure \
    && make -j4 \
    && make install \
    && docker-php-ext-enable imagick \
    && rm -rf /usr/src/imagick-3.8.1 /tmp/imagick-3.8.1.tgz

COPY wordpress.conf /etc/apache2/conf-available/wordpress.conf
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY entrypoint.sh /usr/local/bin/valon-entrypoint
RUN a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork rewrite headers \
    && a2enconf wordpress \
    && chmod 0755 /usr/local/bin/valon-entrypoint \
    && install -d -o www-data -g www-data -m 0700 /var/cache/wordpress \
    && rmdir /var/www/html \
    && ln -s /data/.valon-state-v1/wordpress /var/www/html \
    && apache2ctl -t

# No site files, credentials, salts, uploads or database are baked into this image.
# Existing Railway volume must be staged/verified then mounted at /data.
WORKDIR /var/www
ENTRYPOINT ["valon-entrypoint"]
CMD ["apache2-foreground"]
