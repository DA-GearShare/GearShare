#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"

docker compose exec app composer fix

cd frontend
npm run lint:oxlint
npm run lint:eslint
