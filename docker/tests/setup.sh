#!/usr/bin/env bash

set -o errexit -o nounset -o pipefail

echo
echo "==> Copying .env file"
echo
cp -v .env.docker .env

echo
echo "==> Changing .env permissions"
echo
chmod -v 0777 .env

app_domain="localhost"

# We disable dottie validation since 'localhost' as APP_DOMAIN
# is *technically* a misconfiguration

echo
echo "==> Reconfiguring .env file for testing"
echo
scripts/dottie set \
    APP_DOMAIN="${app_domain}" \
    APP_NAME="docker-pixelfed e2e" \
    DB_PASSWORD="helloworld" \
    DOCKER_APP_TAG="${DOCKER_APP_TAG:?missing}" \
    DOCKER_APP_PHP_VERSION="${DOCKER_APP_PHP_VERSION:?missing}" \
    DOCKER_PROXY_ACME_PROFILE="disabled" \
    DOCKER_PROXY_LETSENCRYPT_TEST="disabled" \
    DOCKER_PROXY_PROFILE="disabled" \
    ENFORCE_EMAIL_VERIFICATION="false" \
    INSTANCE_CONTACT_EMAIL="github@example.com" \
    ACTIVITY_PUB="true" \
    OAUTH_ENABLED="true" \
    PF_LOGIN_WITH_MASTODON_ENABLED="true" \
    DOCKER_APP_PHP_MEMORY_LIMIT="256M"
