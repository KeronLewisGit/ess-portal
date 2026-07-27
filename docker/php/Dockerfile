FROM php:8.3-fpm

# System dependencies + PHP extensions needed by Laravel + this project.
# gd/zip are included now because later phases (dompdf letterhead images,
# maatwebsite/excel) require them and we want one stable image.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official image; pinned major version 2)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dev-only entrypoint: relaxes permissions on storage/ and bootstrap/cache
# because Windows bind mounts appear root-owned inside the container.
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
