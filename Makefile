# IFT 542 - Student Registration Application (hardening project)
# All project commands in one place. Run `make help` for the full list.
#
# Typical flow:
#   make setup      # create .env from template
#   make up         # build + start stack
#   make seed       # seed data + test accounts
#   make tests      # run the automated security test suite (writes evidence)
#   make clone      # personalise a MATRICNO_IFT542 clone (needs clone.json)

SHELL := /bin/bash
.DEFAULT_GOAL := help

.PHONY: help setup build up down reset clean seed test-users tests \
        test-auth test-sqli test-hash test-webdef test-rate test-logs \
        logs smoke evidence clone config demo

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

setup: ## Create .env from .env.example (safe placeholders; replace secrets)
	@test -f .env || cp .env.example .env
	@echo ".env ready (placeholders only - replace before any non-local deployment)"

build: ## Build the app image
	docker compose build

up: ## Build and start the whole stack (db, app, vulnerable, preview, cli)
	docker compose up -d --build

down: ## Stop the stack (data persists)
	docker compose down

reset: ## Stop the stack and delete all volumes (fresh database on next up)
	docker compose down -v

clean: reset ## Reset and remove generated evidence/logs/seed output
	@rm -rf evidence/generated storage db/seed-output
	@echo "cleaned generated artifacts"

seed: ## Seed the hardened DB with Argon2id-hashed fictitious accounts
	docker compose run --rm cli seed

test-users: ## Create hermetic test accounts used by the test suite
	docker compose run --rm cli make_test_users

tests: ## Run every security test suite and write evidence to evidence/generated
	bash tests/run.sh

test-auth: ## Authentication suite (login, lockout, session rotation, SQLi demo)
	bash tests/test_auth.sh

test-sqli: ## SQL injection remediation suite (parameterization proven)
	bash tests/test_sqli.sh

test-hash: ## Password-storage evidence (Argon2id, no plaintext)
	bash tests/test_password_hashing.sh

test-webdef: ## Web defences (XSS, CSRF, SSRF, headers, uploads, no defaults)
	bash tests/test_webdefences.sh

test-rate: ## Rate limiting suite
	bash tests/test_ratelimit.sh

test-logs: ## Structured logging suite (+ redacted sample evidence)
	bash tests/test_logging.sh

smoke: ## Quick end-to-end smoke check of the hardened app
	@bash -c 'set -euo pipefail; \
	  code=$$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8085/login.php); \
	  echo "login page -> HTTP $$code"; \
	  [ "$$code" = "200" ] || { echo "app not ready"; exit 1; }; \
	  docker compose run --rm cli count "SELECT COUNT(*) FROM users"'

logs: ## Follow the hardened app logs
	docker compose logs -f --tail=50 app

evidence: ## Rebuild evidence folder from the latest test run + config snapshots
	bash tests/run.sh

clone: ## Personalise a MATRICNO_IFT542 clone from clone.json
	python3 tools/clone_project.py --config clone.json

clone-push: ## Clone from clone.json, git init/commit, then push to GitHub
	python3 tools/clone_project.py --config clone.json --push

clone-create: ## Clone + create the GitHub repo (if missing) and push
	python3 tools/clone_project.py --config clone.json --push --create-repo

config: ## Show the current compose configuration
	docker compose config
