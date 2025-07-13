ARG PHP_VERSION=8.4

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
COPY ./.docker/.gitconfig /root/.gitconfig
WORKDIR /app
RUN chmod +x /app/bin/console
RUN if [ -n "$GITHUB_TOKEN" ]; then composer config --global github-oauth.github.com ${GITHUB_TOKEN}; fi
RUN composer check-platform-reqs
RUN composer validate --strict
RUN composer install --no-dev --no-scripts --prefer-dist
RUN rm $(composer config home)/auth.json -f
RUN ./bin/console cache:warmup --env=prod --no-debug
# this checks that the YAML config files contain no syntax errors
RUN ./bin/console lint:yaml config --parse-tags
# this checks that the Symfony container has a correct services declarations
RUN ./bin/console lint:container

CMD ["bash", "/cmd.sh"]
ENTRYPOINT ["bash", "/entrypoint.sh"]
EXPOSE 80
