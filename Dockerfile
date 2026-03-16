FROM php:8.4-apache@sha256:1e4d8ddf81c7c55b3306e852ec3cc5a8f0e5ceeb4897efeab2a87de17016786b

# Pakete hinzufügen
RUN apt-get update \
 && apt-get install -y git libpng-dev libzip-dev zlib1g-dev libicu-dev gettext imagemagick libmagickwand-dev zip unzip locales

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql intl gettext gd zip exif opcache

RUN pecl install imagick \
 && docker-php-ext-enable imagick

# Set the locale
RUN sed -i -e 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen
ENV LANG de_DE.UTF-8  
ENV LANGUAGE de_DE:de
ENV LC_ALL de_DE.UTF-8

# Apache von html nach public konfigurieren
RUN a2enmod rewrite \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

# autorise .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# install composer
RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

# install node
RUN apt-get install -y gnupg
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs

# install yarn via corepack (apt-key is removed in modern Debian images)
RUN corepack enable

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN echo 'xdebug.mode=coverage' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.discover_client_host=0' >> /usr/local/etc/php/php.ini
RUN echo 'error_reporting=E_ALL' >> /usr/local/etc/php/php.ini
RUN echo 'upload_max_filesize=20M' >> /usr/local/etc/php/php.ini
RUN echo 'post_max_size=20M' >> /usr/local/etc/php/php.ini
RUN echo 'max_execution_time=600' >> /usr/local/etc/php/php.ini
RUN echo 'memory_limit=512M' >>  /usr/local/etc/php/php.ini

COPY ./.docker_bash_history /root/.bash_history
WORKDIR /var/www
