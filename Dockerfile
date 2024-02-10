ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-fpm

ARG GITHUB_TOKEN

RUN apt-get update && apt-get install -y git zip supervisor cron procps

# intl
RUN apt-get install -y zlib1g-dev libicu-dev g++
RUN docker-php-ext-configure intl

# Other extension
RUN docker-php-ext-install iconv pdo pdo_mysql bcmath

# Xdebug
RUN pecl install xdebug

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV APP_ENV=prod
ENV XDEBUG_MODE=off
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN cd /usr/local/etc/php/conf.d/ && echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini
COPY . /app
COPY ./.docker/cmd.sh /cmd.sh
RUN chmod +x /cmd.sh
COPY ./.docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
COPY ./.docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
WORKDIR /app
RUN chmod +x /app/bin/console
# this checks that the YAML config files contain no syntax errors
RUN ./bin/console lint:yaml config --parse-tags
# this checks that the Symfony container has a correct services declarations
RUN ./bin/console lint:container
 # this checks that Doctrine's mapping configurations are valid
RUN ./bin/console doctrine:schema:validate --skip-sync -vvv --no-interaction
RUN if [ -n "$GITHUB_TOKEN" ]; then composer config --global github-oauth.github.com ${GITHUB_TOKEN}; fi
RUN composer check-platform-reqs
RUN composer validate --strict
RUN composer install --no-dev --no-scripts --prefer-dist
RUN ./bin/console cache:warmup --env=prod --no-debug
RUN rm $(composer config home)/auth.json -f

CMD ["bash", "/cmd.sh"]
ENTRYPOINT ["bash", "/entrypoint.sh"]
EXPOSE 80
