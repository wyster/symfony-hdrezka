DOCKER=docker
DOCKER_COMPOSE?=docker-compose
RUN=$(DOCKER_COMPOSE) run --rm app

tty:
	$(RUN) /bin/bash
