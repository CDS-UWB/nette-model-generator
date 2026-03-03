FROM php:8.4

RUN apt update && apt install -y \
        zip \
        unzip \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libpng-dev \
        libyaml-dev \
        postgresql-client \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli \
    && docker-php-ext-install -j$(nproc) pdo_pgsql \
    && pecl install xdebug-3.5.0 && docker-php-ext-enable xdebug \
    && pecl install yaml && docker-php-ext-enable yaml \
    && apt autoremove

COPY --from=composer:2.9.5 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/app

CMD ["tail", "-f", "/dev/null"] 
