#!/usr/bin/env bash

set -o errexit -o nounset -o pipefail

declare project_root="${PWD}"
command -v git &>/dev/null && project_root=$(git rev-parse --show-toplevel)

# We skip these entrypoints as they require other containers (like DB or Redis)
# which won't be running during GOSS testing
export ENTRYPOINT_SKIP_SCRIPTS="02-check-config.sh,11-first-time-setup.sh,12-migrations.sh"

export EXPECTED_PHP_VERSION=${DOCKER_APP_PHP_VERSION:?missing}

export PHP_BASE_TYPE=${PHP_BASE_TYPE:?missing}

for tag in ${TAGS:?missing}; do
    dgoss run \
        -v "${project_root}/.env.testing:/var/www/.env" \
        -e ENTRYPOINT_SKIP_SCRIPTS \
        -e EXPECTED_PHP_VERSION \
        -e PHP_BASE_TYPE \
        "$tag"
done
