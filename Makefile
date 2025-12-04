.PHONY: help install update test test-coverage lint lint-fix clean docker-up docker-down docker-logs

# Default target
help:
	@echo "LimeSurveyWebhook - Available commands:"
	@echo ""
	@echo "  make install        Install dependencies"
	@echo "  make update         Update dependencies"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage report"
	@echo "  make lint           Check code style (PSR-12)"
	@echo "  make lint-fix       Fix code style issues"
	@echo "  make clean          Remove generated files"
	@echo ""
	@echo "  make docker-up      Start LimeSurvey containers"
	@echo "  make docker-down    Stop LimeSurvey containers"
	@echo "  make docker-logs    View container logs"
	@echo ""

# Dependencies
install:
	composer install

update:
	composer update

# Testing
test:
	composer test

test-coverage:
	composer test-coverage

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

# Docker
up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

