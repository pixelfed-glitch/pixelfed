FROM bats/bats

RUN set -ex \
    && apk add \
        # needed for numfmt in 04-defaults.envsh entrypoint
        coreutils
