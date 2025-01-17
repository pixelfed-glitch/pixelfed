#!/bin/bash

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
    DOCKER_PROXY_ACME_PROFILE="disabled" \
    DOCKER_PROXY_LETSENCRYPT_TEST="disabled" \
    DOCKER_PROXY_PROFILE="disabled" \
    ENFORCE_EMAIL_VERIFICATION="false" \
    INSTANCE_CONTACT_EMAIL="github@example.com" \
    OAUTH_ENABLED="true"
