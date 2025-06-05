.PHONY: help install test test-coverage format check-style analyse
.DEFAULT_GOAL := help

help: ## Show this help message
	@echo 'Usage:'
	@echo '  make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	composer install

test: ## Run tests
	vendor/bin/phpunit

test-coverage: ## Run tests with coverage report
	vendor/bin/phpunit --coverage-html coverage

format: ## Format code
	vendor/bin/php-cs-fixer fix

check-style: ## Check code style
	vendor/bin/php-cs-fixer fix --dry-run --diff

analyse: ## Run static analysis
	vendor/bin/phpstan analyse

ci: check-style analyse test ## Run all CI checks

clean: ## Clean build artifacts
	rm -rf build/ coverage/ .phpunit.cache