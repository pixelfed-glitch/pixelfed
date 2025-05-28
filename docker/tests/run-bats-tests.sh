#!/bin/bash
set -e -o errexit -o nounset -o pipefail

declare project_root="${PWD}"

command -v git &>/dev/null && project_root=$(git rev-parse --show-toplevel)

docker build -t local-bats -f "${project_root}/docker/tests/bats.Dockerfile" .

# [docker run] flags
declare -a docker_flags=(
    --rm
    --env TERM
    --env COLORTERM
    --volume "${project_root}:/var/www"
)

# if the shell is interactive, attach the tty and stdin to the container
tty -s && [ $# -eq 0 ] && docker_flags+=(--interactive --tty)

exec docker run "${docker_flags[@]}" local-bats /var/www/docker/tests/bats
