.DEFAULT_GOAL := help
.PHONY: help init install down serve stop db-create db-drop db-reset migrate migration fixtures \
        phpunit playwright phpstan php-cs-fix php-cs-check build quality ci

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*##' $(MAKEFILE_LIST) | sort | awk -F ':.*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

## Installation

init: install db-reset ## Installation complète (deps + DB + fixtures)

install: ## Installe les dépendances PHP, JS et Playwright
	symfony composer install
	npm install
	npx playwright install chromium

## Serveur

serve: stop ## Lance le serveur Symfony en arrière-plan
	symfony serve

stop: ## Arrête le serveur Symfony
	symfony server:stop

## Base de données

db-create: ## Crée le fichier SQLite si besoin (auto-créé au premier migrate)
	@mkdir -p var && touch var/data.db

db-drop: ## Supprime les fichiers SQLite (dev + test)
	@rm -f var/data.db var/data_test.db

db-reset: db-drop migrate fixtures ## Recrée la base from scratch (drop + migrate + fixtures)

migrate: ## Applique les migrations Doctrine
	symfony console doctrine:migrations:migrate -n

migration: ## Génère une nouvelle migration depuis le diff d'entités
	symfony console make:migration

fixtures: ## Charge les fixtures Doctrine
	symfony console doctrine:fixtures:load -n

## Tests

phpunit: ## Lance les tests PHPUnit (Unit + Functional)
	symfony php bin/phpunit

playwright: ## Lance les tests E2E Playwright
	npm run test:e2e

## Qualité

phpstan: ## Analyse statique PHPStan (niveau 9)
	symfony php vendor/bin/phpstan analyse --no-progress

php-cs-fix: ## Correction automatique avec PHP CS Fixer
	symfony php vendor/bin/php-cs-fixer fix

php-cs-check: ## Vérifie le code style sans modifier (mode CI)
	symfony php vendor/bin/php-cs-fixer fix --dry-run --diff

lint: php-cs-check phpstan ## Lint en lecture seule (CS-Fixer dry-run + PHPStan)

build: ## Build des assets (Tailwind + AssetMapper)
	symfony console tailwind:build --minify
	symfony console asset-map:compile

quality: php-cs-fix phpstan build ## Lance toute la QA en mode dev (CS Fixer + PHPStan + build)

ci: lint phpunit ## Lance la suite CI (lint + tests unitaires)
