.DEFAULT_GOAL := help

filter := "default"
dirname := $(notdir $(CURDIR))
envprefix := $(shell echo "$(dirname)" | tr A-Z a-z)
envname := $(envprefix)test
debug := "false"

help:
	@grep -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

install-plugin: .refresh-plugin-list ## Install and activate the plugin
	@echo "Install the plugin"
	php ./../../../../../../bin/console sw:plugin:install $(dirname) --activate
	php ./../../../../../../bin/console sw:cache:clear

.refresh-plugin-list:
	@echo "Refresh the plugin list"
	php ./../../../../../../bin/console sw:plugin:refresh

CS_FIXER_RUN=
fix-cs: ## Run the code style fixer
	php ./../../../../../../vendor/bin/php-cs-fixer fix --config=./php-cs-fixer.php -v $(CS_FIXER_RUN)

fix-cs-dry: CS_FIXER_RUN= --dry-run
fix-cs-dry: fix-cs  ## Run the code style fixer in dry mode

check-js-code: check-eslint-backend ## Run esLint
fix-js-code: fix-eslint-backend fix-eslint-e2e fix-eslint-jest-tests ## Fix js code

ESLINT_FIX=
check-eslint-backend:
	./../../../../../../themes/node_modules/eslint/bin/eslint.js --ignore-path .eslintignore -c ./../../../../../../themes/Backend/.eslintrc.js Views/backend $(ESLINT_FIX)

fix-eslint-backend: ESLINT_FIX= --fix
fix-eslint-backend: check-eslint-backend # TODO: remove comment tests12345678

composer-install: ## Install composer requirements
	@echo "Install composer requirements"
