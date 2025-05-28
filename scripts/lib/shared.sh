#!/usr/bin/env bash

BLUE='\033[1;34m'
GREEN='\033[1;32m'
NO_COLOR='\033[0m'
RED='\033[0;31m'
YELLOW='\033[0;33m'

function action_start()
{
    echo -en "${BLUE}⚙️ $*${NO_COLOR}: "
}

function action_start_newline()
{
    action_start "$*"
    echo
}

function action_ok()
{
    echo -e "✅ ${GREEN}$*${NO_COLOR}"
}

function action_warn()
{
    echo -e "⚠️ ${YELLOW}$*${NO_COLOR}"
}

function action_error()
{
    echo -e "❌ ${RED}$*${NO_COLOR}" >&2
}

function action_error_exit()
{
    action_error "$*. Aborting!"

    exit 1
}

function run_command_with_prefix()
{
    local prefix="| "
    "$@" > >(sed "s/^/$prefix /") 2> >(sed "s/^/$prefix /" >&2)
}

function highlight()
{
    local reset="${2:-$NO_COLOR}"
    echo "${BLUE}$1${reset}"
}

function has_command()
{
    command -v "${1:?missing command}" >/dev/null 2>&1
}

function random_string()
{
    local length="${1:?missing length of string}"

    has_command "pwgen" && pwgen --secure "${length}" 1 && return 0
    has_command "openssl" && openssl rand -base64 "${length}" && return 0
    has_command "mktemp" && mktemp -u "$(printf "%${length}s" | tr " " "X")" && return 0

    action_error_exit "don't know how to generate random string. Can't find 'pwgen', 'openssl' or 'mktemp' binaries"
}

function __dottie()
{
    "${project_root:?missing}/scripts/dottie" "$@"
    return $?
}

function __gum()
{
    "${project_root:?missing}/scripts/gum" "$@"
    return $?
}

function __feature()
{
    "${project_root:?missing}/scripts/feature" "$@"
    return $?
}

function as-boolean()
{
    local input="${1:-}"
    local var
    var="$(echo "$input" | tr '[:upper:]' '[:lower:]')"

    case "$var" in
        1 | true)
            return 0
            ;;

        0 | false)
            return 1
            ;;

        *)
            return 2
            ;;

    esac
}

function ask_input()
{
    local -r key_name="${1:?missing env key name}"
    local -r title="${2:?missing title}"
    local -r placeholder="${3:-""}"
    local value
    value=$(__dottie value "${key_name}" --with-disabled)

    [[ "$value" == "__CHANGE_ME__" ]] && value=""

    while true; do
        action_start_newline "${BLUE}${title}${NO_COLOR}"

        value=$(__gum input --placeholder="${placeholder}" --value "${value}")
        __dottie set "${key_name}"="${value}" && action_ok "Successfully set '${key_name}' to '${value}'" && echo "" && return 0

        action_error "Looks like the name is invalid, try again"
        echo ""
    done

    return 1
}

function confirm_bool()
{
    local -r key_name="${1:?missing env key name}"
    local -r title="${2:?missing title}"
    local value
    value=$(__dottie value "${key_name}" --with-disabled)
    local default="No"

    if as-boolean "${value}"; then
        default="Yes"
    fi

    action_start_newline "${BLUE}${title}${NO_COLOR}"

    __gum confirm --default="${default}" "'Yes' to enable and 'No' to disable"
}

function ask_confirm_boolean()
{
    local -r key_name="${1:?missing env key name}"

    if confirm_bool "$@"; then
        __dottie set "${key_name}"="true" && action_ok "Successfully set '${key_name}' to 'true'" && echo "" && return 0
    else
        __dottie set "${key_name}"="false" && action_ok "Successfully set '${key_name}' to 'false'" && echo "" && return 0
    fi
}

function ask_confirm_profile()
{
    local -r key_name="${1:?missing env key name}"
    local -r title="${2:?missing title}"
    local value
    value=$(__dottie value "${key_name}" --with-disabled)
    local default="Yes"

    # if a profile value has length, its set/disabled
    if [[ -n "${value}" ]]; then
        default="No"
    fi

    action_start_newline "${BLUE}${title}${NO_COLOR}"

    # enable proxy?
    if __gum confirm --default="${default}" "'Yes' to enable and 'No' to disable"; then
        __dottie set --disabled "${key_name}"="" && action_ok "Successfully set '${key_name}' to ''" && echo "" && return 0

        return 0
    fi

    __dottie set "${key_name}"="disabled" && action_ok "Successfully set '${key_name}' to 'disabled'" && echo "" && return 0

    return 1
}

function ensure-dot-env-file()
{
    local project_root="${PWD}"
    command -v git &>/dev/null && project_root=$(git rev-parse --show-toplevel)

    # Check if the .env file exist, or copy the template
    if [[ ! -f "${project_root}/.env" ]]; then
        action_start_newline "No '.env' file found, copying the default one for you"
        cp -v "${project_root}/.env.docker" "${project_root}/.env" || action_error_exit "copy operation failed"
        action_ok "OK"
        echo

        action_start_newline "Ensuring permissions are correct (0777)"
        chmod -v 0777 "${project_root}/.env" || action_error_exit "chmod operation failed"
        action_ok "OK"
        echo
    fi

}
