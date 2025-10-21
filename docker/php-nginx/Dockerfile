# === Base image PHP-FPM + Debian ===
FROM php:8.2-fpm

# === Install system dependencies and PHP extensions ===
RUN apt-get update && apt-get install -y \
        nginx \
        git \
        unzip \
        zip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libicu-dev \
        libssl-dev \
        libxslt1-dev \ 
        default-mysql-client \
        curl \
        vim \
        less \
        iputils-ping \
        telnet \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd intl soap sockets bcmath opcache ftp xsl \
    && docker-php-ext-enable pdo_mysql zip gd intl soap sockets bcmath opcache ftp xsl \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug   

# PHP for Magento
RUN echo "memory_limit=2G" > /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "max_execution_time=600" >> /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "post_max_size=128M" >> /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "max_input_vars=5000" >> /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "realpath_cache_size=10M" >> /usr/local/etc/php/conf.d/99-magento.ini \
 && echo "realpath_cache_ttl=7200" >> /usr/local/etc/php/conf.d/99-magento.ini

# === Install Composer ===
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# === Copy Nginx configuration for Magento ===
COPY default.conf /etc/nginx/conf.d/default.conf

# === Remove default Nginx config ===
RUN rm /etc/nginx/sites-enabled/default || true

# === Set working directory ===
WORKDIR /var/www/html

# === Expose ports ===
EXPOSE 80

# === Start PHP-FPM + Nginx together ===
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
