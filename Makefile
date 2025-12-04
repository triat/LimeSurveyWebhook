.PHONY: help install update test test-coverage test-all test-php74 test-php80 test-php81 test-php82 lint lint-fix clean up down logs

# PHP versions to test
PHP_VERSIONS := 7.4 8.0 8.1 8.2

# Default target
help:
	@echo "LimeSurveyWebhook - Available commands:"
	@echo ""
	@echo "  make install        Install dependencies"
	@echo "  make update         Update dependencies"
	@echo "  make test           Run tests (local PHP)"
	@echo "  make test-coverage  Run tests with coverage report"
	@echo "  make test-all       Run tests on all PHP versions (Docker)"
	@echo "  make test-php74     Run tests on PHP 7.4 (Docker)"
	@echo "  make test-php80     Run tests on PHP 8.0 (Docker)"
	@echo "  make test-php81     Run tests on PHP 8.1 (Docker)"
	@echo "  make test-php82     Run tests on PHP 8.2 (Docker)"
	@echo "  make lint           Check code style (PSR-12)"
	@echo "  make lint-fix       Fix code style issues"
	@echo "  make clean          Remove generated files"
	@echo ""
	@echo "  make up             Start LimeSurvey containers"
	@echo "  make down           Stop LimeSurvey containers"
	@echo "  make logs           View container logs"
	@echo ""

# Dependencies
install:
	composer install

update:
	composer update

# Testing (local)
test:
	composer test

test-coverage:
	composer test-coverage

# Testing (Docker - multiple PHP versions)
test-all: test-php74 test-php80 test-php81 test-php82
	@echo ""
	@echo "✅ All PHP version tests completed!"

test-php74:
	@echo "🐘 Testing with PHP 7.4..."
	@docker run --rm -v "$(CURDIR):/app" -w /app composer:2.2 composer install --quiet
	@docker run --rm -v "$(CURDIR):/app" -w /app php:7.4-cli vendor/bin/phpunit --colors=always
	@echo "✅ PHP 7.4 tests passed!"
	@echo ""

test-php80:
	@echo "🐘 Testing with PHP 8.0..."
	@docker run --rm -v "$(CURDIR):/app" -w /app php:8.0-cli vendor/bin/phpunit --colors=always
	@echo "✅ PHP 8.0 tests passed!"
	@echo ""

test-php81:
	@echo "🐘 Testing with PHP 8.1..."
	@docker run --rm -v "$(CURDIR):/app" -w /app php:8.1-cli vendor/bin/phpunit --colors=always
	@echo "✅ PHP 8.1 tests passed!"
	@echo ""

test-php82:
	@echo "🐘 Testing with PHP 8.2..."
	@docker run --rm -v "$(CURDIR):/app" -w /app php:8.2-cli vendor/bin/phpunit --colors=always
	@echo "✅ PHP 8.2 tests passed!"
	@echo ""

# Linting
lint:
	composer lint

lint-fix:
	composer lint-fix

# Cleanup
clean:
	rm -rf vendor/
	rm -rf coverage/
	rm -rf limesurvey/
	rm -rf tests/mocks/
	rm -f .phpunit.result.cache

# Docker (LimeSurvey)
up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f
