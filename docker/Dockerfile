FROM php:8.1-cli

# Get-Content C:\www\vodmalbot-auto\docker\Dockerfile | docker build -t vodmalbot-auto -
# docker run --rm -it -v C:\www\vodmalbot-auto:/var/www/vodmalbot-auto vodmalbot-auto bash

# php config
RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

# install dependencies
RUN apt-get update -yq
RUN apt-get install -y zip git

ENV COMPOSER_ALLOW_SUPERUSER 1

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

WORKDIR /var/www/vodmalbot-auto
