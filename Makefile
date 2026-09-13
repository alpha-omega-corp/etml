# -----------------------------------------------------------------------------
# MTU
#
# `make dev` is the whole local setup: the database container, the PHP server
# and Vite, in one terminal. Ctrl+C stops all three.
# -----------------------------------------------------------------------------

# `trap`, `wait` and job control below are bash, not POSIX sh.
SHELL := /bin/bash
.DEFAULT_GOAL := help

.PHONY: help dev db stop test build

help:  ## Show the available targets
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "} {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

## Start Postgres and wait until it actually accepts connections — `--wait`
## holds until the healthcheck in docker-compose.yml passes, so the server
## never comes up against a database that is still booting.
db:  ## Start the database and wait for it
	docker compose up -d --wait

## The two servers run as background jobs of one shell, and the trap kills the
## whole process group on the way out. Without it, Ctrl+C leaves `php artisan
## serve` holding port 8000 and the next `make dev` fails.
dev: db  ## Start the database, the PHP server and Vite
	@trap 'kill 0' EXIT INT TERM; \
	php artisan serve & \
	npm run dev & \
	wait

stop:  ## Stop the database
	docker compose stop

test: db  ## Run the test suite
	php artisan test --compact

build:  ## Build the front-end assets for production
	npm run build
