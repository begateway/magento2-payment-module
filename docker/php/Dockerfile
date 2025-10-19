FROM php:8.2-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libxml2-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxslt1-dev default-mysql-client curl vim libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo_mysql \
        soap \
        sockets \
        xsl \
        zip \
        gd \
        opcache \
        ftp \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Настройки PHP
COPY php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html

# Создаем пользователя для безопасности
RUN useradd -m magento && chown -R magento:magento /var/www/html
USER magento

CMD ["php-fpm"]
