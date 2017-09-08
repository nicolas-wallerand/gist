export COMPOSER 		?= composer
export BOWER 			?= bower
export GIT 				?= git
export MKDIR 			?= mkdir
export PHP 			 	?= php
export COMPOSE_FILE	 	?= docker-compose.yml
export COMPOSE_PROJECT_NAME ?= gist

docker-compose = docker-compose --file ${COMPOSE_FILE} --project-name ${COMPOSE_PROJECT_NAME}

all: update

composer:
	@echo "Installing application's dependencies"
	@echo "-------------------------------------"
	@echo 

	$(COMPOSER) install $(COMPOSER_INSTALL_FLAGS)
bower:
	@echo "Installing application's dependencies"
	@echo "-------------------------------------"
	@echo 

	$(BOWER) install

optimize:
	@echo "Optimizing Composer's autoloader, can take some time"
	@echo "----------------------------------------------------"
	@echo 

	$(COMPOSER) dump-autoload --optimize

update:
	@echo "Updating application's dependencies"
	@echo "-----------------------------------"
	@echo 

	sh -c 'test -d app && $(GIT) add app && $(GIT) commit -m "Configuration"'
	$(GIT) pull origin master
	${MKDIR} -p data/git
	$(COMPOSER) update
	$(BOWER) install

run:
	@echo "Run development server"
	@echo "----------------------"
	@echo 

	${docker-compose} up --build --remove-orphans -d app

vendor: composer.lock
	@{docker-compose} run --rm app composer install

composer.lock: composer.json
	@echo compose.lock is not up to date.

propel:
	@echo "Propel migration"
	@echo "----------------"
	@echo 
	
	./vendor/propel/propel/bin/propel config:convert
	./vendor/propel/propel/bin/propel model:build --recursive
	./vendor/propel/propel/bin/propel migration:diff --recursive
	./vendor/propel/propel/bin/propel migration:migrate --recursive
