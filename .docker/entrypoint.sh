#!/bin/sh

printenv >> /etc/environment

if [ $XDEBUG_MODE == "debug" ]; then
    docker-php-ext-enable xdebug
fi

exec "$@"
