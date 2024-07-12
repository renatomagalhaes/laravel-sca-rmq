green=\033[0;32m
red=\033[0;31m
yellow=\033[0;33m
nc=\033[0m

.PHONY: start init stop restart down install build logs exec fix-permissions chown pint pint-fix test

start: init

init:
	@echo "${green}Starting the project...${nc}"
	@docker-compose up -d
	@echo "${green}Project started successfully!${nc}"

stop:
	@echo "${red}Stopping the project...${nc}"
	@docker-compose stop
	@echo "${red}Project stopped successfully!${nc}"

restart: stop start

down:
	@echo "${red}Stopping the project...${nc}"
	@docker-compose down
	@echo "${red}Project stopped successfully!${nc}"

install: build
	@printf "${green}Project installed successfully!${nc}\n"

build:
	@printf "${green}Building the project...${nc}\n"
	sudo chmod +x ./docker/local/start.sh
	docker-compose up -d --build
	@printf "${green}Project built successfully!${nc}\n"

logs:
	@docker-compose logs -f

exec:
	@docker-compose exec app bash

fix-permissions:
	@echo "${yellow}Fixing permissions...${nc}"
	@docker-compose exec app chown -R www-data:www-data /var/www
	@echo "${yellow}Permissions fixed successfully!${nc}"

chown:
	@echo "${yellow}Changing ownership...${nc}"
	@sudo chown -R $(USER):$(USER) .
	@echo "${yellow}Ownership changed successfully!${nc}"

pint:
	docker-compose exec app vendor/bin/pint --test

pint-fix:
	docker-compose exec app vendor/bin/pint

test:
	docker-compose exec app vendor/bin/phpunit --testdox
