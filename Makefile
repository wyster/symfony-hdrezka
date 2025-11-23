DOCKER=docker
DOCKER_COMPOSE?=docker compose
RUN=$(DOCKER_COMPOSE) run --rm app

tty:
	$(RUN) /bin/bash

php-cs-fixer:
	$(RUN) ./vendor/bin/php-cs-fixer fix
