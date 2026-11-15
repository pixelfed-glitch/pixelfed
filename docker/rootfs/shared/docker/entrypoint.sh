#!/usr/bin/env bash
# short circuit the entrypoint if $ENTRYPOINT_SKIP isn't set to 0
if [[ ${ENTRYPOINT_SKIP:=0} != 0 ]]; then
    exec "$@"
fi

: "${ENTRYPOINT_ROOT:="/docker"}"
export ENTRYPOINT_ROOT

# Directory where entrypoint scripts lives
: "${ENTRYPOINT_D_ROOT:="${ENTRYPOINT_ROOT}/entrypoint.d/"}"
export ENTRYPOINT_D_ROOT

: "${DOCKER_APP_HOST_OVERRIDES_PATH:="${ENTRYPOINT_ROOT}/overrides"}"
export DOCKER_APP_HOST_OVERRIDES_PATH

# Space separated list of scripts the entrypoint runner should skip
: "${ENTRYPOINT_SKIP_SCRIPTS:=""}"

# Load helper scripts
#
# shellcheck source=SCRIPTDIR/helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

# Set the entrypoint name for logging
entrypoint-set-script-name "entrypoint.sh"

# Convert ENTRYPOINT_SKIP_SCRIPTS into a native bash array for easier lookup
declare -a skip_scripts
# shellcheck disable=SC2034
IFS=' ' read -r -a skip_scripts <<<"$ENTRYPOINT_SKIP_SCRIPTS"

# Ensure the entrypoint root folder exists
mkdir -p "${ENTRYPOINT_D_ROOT}"

# If ENTRYPOINT_D_ROOT directory is empty, warn and run the regular command
if directory-is-empty "${ENTRYPOINT_D_ROOT}"; then
    log-warning "No files found in ${ENTRYPOINT_D_ROOT}, skipping configuration"

    exec "$@"
fi

# If the overrides directory exists, then copy all files into the container
if ! directory-is-empty "${DOCKER_APP_HOST_OVERRIDES_PATH}"; then
    log-info "Overrides directory is not empty, copying files"
    run-as-current-user cp --verbose --recursive "${DOCKER_APP_HOST_OVERRIDES_PATH}/." /
fi

# Start scanning for entrypoint.d files to source or run
log-info "looking for shell scripts in [${ENTRYPOINT_D_ROOT}]"

find "${ENTRYPOINT_D_ROOT}" -follow -type f -print | sort -V | while read -r file; do
    if in-array "$(get-entrypoint-script-name "any")" skip_scripts; then
        log-warning "Skipping script [${file}] since the skip list (\$ENTRYPOINT_SKIP_SCRIPTS) contains any."

        continue
    elif in-array "$(get-entrypoint-script-name "true")" skip_scripts; then
        log-warning "Skipping script [${file}] since the skip list (\$ENTRYPOINT_SKIP_SCRIPTS) contains true."

        continue
    # Skip the script if it's in the skip-script list
    elif in-array "$(get-entrypoint-script-name "${file}")" skip_scripts; then
        log-warning "Skipping script [${file}] since it's in the skip list (\$ENTRYPOINT_SKIP_SCRIPTS)"

        continue
    fi

    # Inspect the file extension of the file we're processing
    case "${file}" in
        *.envsh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            log-info "${section_message_color}============================================================${color_clear}"
            log-info "${section_message_color}Sourcing [${file}]${color_clear}"
            log-info "${section_message_color}============================================================${color_clear}"

            # shellcheck disable=SC1090
            source "${file}"

            # the sourced file will (should) than the log prefix, so this restores our own
            # "global" log prefix once the file is done being sourced
            entrypoint-restore-script-name
            ;;

        *.sh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            skip_value="$(trim-whitespace "$(trim-whitespace "$ENTRYPOINT_SKIP_SCRIPTS") $script_name")"

            log-info "${section_message_color}============================================================${color_clear}"
            log-info "${section_message_color}Executing [${file}]${color_clear}"
            log-info "${section_message_color}You can disable this script by setting [\$ENTRYPOINT_SKIP_SCRIPTS=\"${skip_value}\"] in your .env file"
            log-info "${section_message_color}============================================================${color_clear}"

            "${file}"
            ;;

        *)
            log-warning "Ignoring unrecognized file [${file}]"
            ;;
    esac
done

# [worker] and [cron] skip the entrypoint.d scripts that populate [storage/], so stop here with a
# useful message instead of failing later on missing media or keys.
storage_marker="./storage/app/public"

# Run a check as the runtime user, since root can read paths the application can't
function runtime-user-check()
{
    if ((EUID == 0)); then
        gosu "${runtime_username}" "$@"
    else
        "$@"
    fi
}

if ! runtime-user-check test -d "${storage_marker}"; then
    # SELinux can allow path lookups but deny listing, so a missing path also needs the listing checked
    log-if-selinux-denial "$(LC_ALL=C ls -d "${storage_marker}" 2>&1 || true; LC_ALL=C ls -A ./storage 2>&1 >/dev/null || true)" "./storage"
    log-error-and-exit "[${storage_marker}] is missing or not accessible by [${runtime_username}]. The [web] container populates [storage/] when it starts, so check its logs if this does not clear."
fi

# The [web] container generates APP_KEY and fixes [.env] ownership on first start. Laravel silently
# ignores an unreadable [.env], so caching config before then would bake in defaults.
if [[ -z "${APP_KEY:-}" ]] && ! runtime-user-check grep -q '^APP_KEY="\?[^"[:space:]]' ./.env 2>/dev/null; then
    log-error-and-exit "[APP_KEY] is not set in [.env], or [.env] is not readable by [${runtime_username}]. The [web] container sets both on first start, so this should clear on restart."
fi

# Each container builds its own config/route/event cache from its own image and .env
# (paths come from APP_*_CACHE in the Dockerfile). Compiled views are built into the image.
run-as-runtime-user php artisan config:cache
run-as-runtime-user php artisan route:cache
run-as-runtime-user php artisan event:cache

touch /tmp/entrypoint-ran-to-completion

log-info "Configuration complete; ready for start up"

exec "$@"
