#!/bin/bash

set -o errexit -o nounset -o pipefail

app_domain="localhost"

echo
echo "==> Starting Pixelfed containers"
echo
docker compose up -d --no-build --quiet-pull

cd docker/

echo
echo "==> Starting npm dependencies"
echo
npm ci

echo
echo "==> Install playwright depdendencies"
echo
npx playwright install chromium --with-deps

echo
echo "==> Wait for the site to come up, while streaming the logs"
echo
curl --retry-delay 1 --retry 180 --retry-max-time 180 --retry-all-errors --fail -o /dev/null "http://${app_domain}:8080"

echo
echo "==> Run playwright tests"
echo
export E2E_URL="http://${app_domain}:8080"
exec npx playwright test --config playwright.config.ts
