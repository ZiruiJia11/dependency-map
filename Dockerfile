FROM php:8.5-cli

# Install Linux packages required by Laravel and PHP extensions.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

RUN echo 'variables_order = "EGPCS"' \
    > "$PHP_INI_DIR/conf.d/variables-order.ini"

# Copy Composer from its official Docker image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# All commands inside the container will run from this directory.
WORKDIR /var/www/html

EXPOSE 8000

# Temporary development server; we will replace this for production later.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]