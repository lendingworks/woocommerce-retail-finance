ARG PHPVERSION=5.6-fpm

FROM php:5.6-fpm

ARG XDEBUG_VERSION=2.5.5
ARG XDEBUG_REMOTE_HOST=hostmachine.docker

MAINTAINER LendingWorks <tech@lendingworks.co.uk>

# Cleanup sources.list as php:5.5-fpm is a little outdated
RUN sed -i 's,security.debian.org jessie/updates main,archive.debian.org/debian jessie-backports main,g' /etc/apt/sources.list \
    && sed -i '/deb http:\/\/httpredir.debian.org\/debian jessie-updates main/d' /etc/apt/sources.list

# Install php extensions required by wordpress
RUN apt-get update -o Acquire::Check-Valid-Until=false \
    && apt-get install --yes --no-install-recommends libjpeg-dev libpng-dev unzip zip git subversion less

RUN docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install gd mysqli opcache zip

RUN pecl channel-update pecl.php.net \
   && pecl install xdebug-2.5.5 \
   && docker-php-ext-enable xdebug

# set recommended PHP.ini settings
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=2'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN { \
		echo 'error_reporting = 4339'; \
		echo 'display_errors = Off'; \
		echo 'display_startup_errors = Off'; \
		echo 'log_errors = On'; \
		echo 'error_log = /dev/stderr'; \
		echo 'log_errors_max_len = 1024'; \
		echo 'ignore_repeated_errors = On'; \
		echo 'ignore_repeated_source = Off'; \
		echo 'html_errors = Off'; \
} > /usr/local/etc/php/conf.d/error-logging.ini

# Change the php-fpm port. This is required to not conflict with other PHP-FPM running from other docker-compose projects,
# As dinghy does not support more than 1 docker network
RUN sed -i 's/listen = 9000/listen = 9020/g' /usr/local/etc/php-fpm.d/zz-docker.conf

# Create a user with UID 1001 and GID 1002 to map Jenkins user and mount writable volumes.
RUN groupadd -g 1002 lendingworks && useradd -m -u 1001 -g lendingworks lendingworks

# Intall wp-cli
RUN curl -o /usr/local/bin/wp -fSL 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar' \
    && chown -R lendingworks:lendingworks /usr/local/bin/wp \
    && chmod 755 /usr/local/bin/wp

RUN mkdir -p /var/www/wordpress \
    && chown -R lendingworks:lendingworks /var/www/wordpress

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

USER lendingworks

WORKDIR /var/www/wordpress/wp-content/plugins/lendingworks

ADD ./lendingworks .

EXPOSE 9020

CMD ["php-fpm"]
