# Raccourcis de développement.
#
# Sous Windows, `make` n'est pas disponible nativement : utiliser WSL,
# ou les commandes docker compose détaillées dans le README.

SHELL := /bin/sh
COMPOSE := docker compose
WPCLI := $(COMPOSE) run --rm wpcli

.DEFAULT_GOAL := help
.PHONY: help up down install reset logs shell wp lint lint-fix test pot

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Démarre la stack puis installe le site
	$(COMPOSE) up -d
	@$(MAKE) install

down: ## Arrête la stack (les données sont conservées)
	$(COMPOSE) down

install: ## (Ré)exécute le script d'installation — idempotent
	$(COMPOSE) run --rm --entrypoint /bin/sh wpcli /scripts/init.sh

reset: ## Détruit tout, volumes compris, puis réinstalle de zéro
	$(COMPOSE) down -v
	@$(MAKE) up

logs: ## Suit les logs du conteneur WordPress
	$(COMPOSE) logs -f wordpress

shell: ## Ouvre un shell dans le conteneur WordPress
	$(COMPOSE) exec wordpress bash

wp: ## Exécute une commande WP-CLI : make wp CMD="plugin list"
	$(WPCLI) $(CMD)

lint: ## Analyse le plugin (WordPress Coding Standards)
	composer lint

lint-fix: ## Corrige automatiquement ce qui peut l'être
	composer lint:fix

test: ## Exécute les tests unitaires
	composer test

pot: ## Régénère le fichier de traduction books-list.pot
	$(WPCLI) i18n make-pot wp-content/plugins/books-list \
		wp-content/plugins/books-list/languages/books-list.pot
