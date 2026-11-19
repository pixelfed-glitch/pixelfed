#!/usr/bin/env bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# @description Run chown, and explain the failure if SELinux caused it
# @arg $@ string The chown arguments, ending with the path
function change-ownership()
{
    local -i exit_code=0

    run-as-current-user chown "$@" || exit_code=$?

    if ((exit_code != 0)); then
        log-if-selinux-denial "$(LC_ALL=C chown "$@" 2>&1 >/dev/null || true)" "${!#}"
    fi

    return "${exit_code}"
}

# Optionally fix ownership of configured paths
: "${DOCKER_APP_ENSURE_OWNERSHIP_PATHS:=""}"

declare -a ensure_ownership_paths=()
IFS=' ' read -r -a ensure_ownership_paths <<<"${DOCKER_APP_ENSURE_OWNERSHIP_PATHS}"

if [[ -z "${DOCKER_APP_ENSURE_OWNERSHIP_PATHS}" || "${DOCKER_APP_ENSURE_OWNERSHIP_PATHS}" == "true" ]]; then
    log-info "Checking permissions. Deactivate with [\$DOCKER_APP_ENSURE_OWNERSHIP_PATHS = false] or specify paths to check."
    # Ensure the Docker volumes and required files are owned by the runtime user as other scripts
    # will be writing to these
    change-ownership --verbose "${RUNTIME_UID}:${RUNTIME_GID}" "./.env"
    change-ownership --verbose "${RUNTIME_UID}:${RUNTIME_GID}" "./storage"
    ensure-directory-exists "./storage/docker"
    change-ownership --verbose --recursive "${RUNTIME_UID}:${RUNTIME_GID}" "./storage/docker"
else
    if [[ "${DOCKER_APP_ENSURE_OWNERSHIP_PATHS}" == "false" || ${#ensure_ownership_paths[@]} == 0 ]]; then
        log-info "No paths has been configured for ownership fixes via [\$DOCKER_APP_ENSURE_OWNERSHIP_PATHS]."

        exit 0
    fi

    for path in "${ensure_ownership_paths[@]}"; do
        log-info "Ensure ownership of [${path}] is correct"
        ensure-directory-exists "${path}"
        change-ownership --recursive "${RUNTIME_UID}:${RUNTIME_GID}" "${path}"
    done
fi
