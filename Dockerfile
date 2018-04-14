FROM php:5.6-apache

RUN a2enmod rewrite

# Instal OS dependencies
RUN apt-get -y update
RUN apt-get install -y zlibc zlib1g-dev libxml2-dev libicu-dev libpq-dev nodejs zip unzip git \
 && pecl install memcache \
 && docker-php-ext-enable memcache

# Install PHP dependencies
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
 && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl soap \
 && docker-php-ext-enable pdo pdo_mysql pdo_pgsql intl soap memcache

RUN apt-get install -y libz-dev libmemcached-dev \
 && pecl install memcached-2.2.0 \
 && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

# Create link to nodejs
RUN ln -s /usr/bin/nodejs /usr/bin/node

# Install XDebug
#RUN yes | pecl install xdebug

# Configure XDebug
#RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
# && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
# && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

# Configure PHP and Apache
RUN echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/conf.d/php-timezone.ini \
 && echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory_limit.ini \
 && echo "<VirTualHost *:80>" > /etc/apache2/conf-enabled/lc-docroot.conf \
 && echo "    DocumentRoot /var/www/html/web" >> /etc/apache2/conf-enabled/lc-docroot.conf \
 && echo "</VirtualHost>" >> /etc/apache2/conf-enabled/lc-docroot.conf

# Instal composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php

RUN mkdir -p /var/www/.composer \
 && chown -R www-data:www-data /var/www/.composer

USER www-data
WORKDIR /var/www/html

# Instal composer dependencies
COPY app/config/parameters.yml.dist /var/www/html/app/config
COPY composer.lock /var/www/html
COPY composer.json /var/www/html
RUN php composer.phar config cache-dir
RUN php composer.phar install --no-interaction --no-scripts --no-autoloader

COPY . /var/www/html
USER root
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R u+rw /var/www/html/app/cache \
 && chmod -R u+rw /var/www/html/app/logs
USER www-data

RUN php composer.phar dump-autoload -d /var/www/html
RUN php composer.phar run-script post-install-cmd --no-interaction
# RUN php app/console assets:install \
#  && php app/console assets:install -e prod \
#  && php app/console assetic:dump -e prod

USER root
