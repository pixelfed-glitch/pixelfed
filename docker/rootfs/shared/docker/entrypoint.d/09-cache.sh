#!/usr/bin/env bash
# shellcheck disable=SC2119

: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

acquire-lock

# force delete cache files as they sometimes break php artisan (great work Laravel)
run-as-runtime-user rm -f ./bootstrap/cache/config.php
run-as-runtime-user rm -f ./bootstrap/cache/events.php
run-as-runtime-user rm -f ./bootstrap/cache/packages.php
run-as-runtime-user rm -f ./bootstrap/cache/routes*.php
run-as-runtime-user rm -f ./bootstrap/cache/services.php

run-as-runtime-user php artisan config:cache
run-as-runtime-user php artisan route:cache
run-as-runtime-user php artisan view:cache
run-as-runtime-user php artisan optimize
