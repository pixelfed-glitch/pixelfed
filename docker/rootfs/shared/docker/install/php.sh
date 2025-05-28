#!/usr/bin/env bash
set -ex -o errexit -o nounset -o pipefail

PHP_BASE_TYPE=${PHP_BASE_TYPE:?Missing PHP_BASE_TYPE - must be [fpm] or [apache]}
COMPOSER_VERSION=${COMPOSER_VERSION:?Missing COMPOSER_VERSION}

#############################################
# Setup PHP package source
#############################################

echo
echo "==> Downloading deb.sury.org Keyring"
echo
curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb

echo
echo "==> Installing deb.sury.org Keyring"
echo
dpkg -i /tmp/debsuryorg-archive-keyring.deb

echo
echo "==> Configuring deb.sury.org APT list"
echo
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" >/etc/apt/sources.list.d/php.list

echo
echo "==> Running [apt-get update]"
echo
apt-get update

echo
echo "==> Cleanup"
echo
rm -f /tmp/debsuryorg-archive-keyring.deb

#############################################
# Install PHP (binary) packages
#############################################

declare -a php_extensions=()
readarray -d ' ' -t php_extensions < <(echo -n "${PHP_EXTENSIONS:-}")
readarray -d ' ' -t -O "${#php_extensions[@]}" php_extensions < <(echo -n "${PHP_EXTENSIONS_EXTRA:-}")
readarray -d ' ' -t -O "${#php_extensions[@]}" php_extensions < <(echo -n "${PHP_EXTENSIONS_DATABASE:-}")

php_extensions+=(cli)

if [[ "${PHP_BASE_TYPE}" == "fpm" ]]; then
    php_extensions+=("fpm")
fi

# join the strings and prefix them with [php${version}-], so [gd] turns into [php8.4-gd]
php_extensions_string="${php_extensions[*]/#/php${PHP_VERSION}-}"

if [[ "${PHP_BASE_TYPE}" == "apache" ]]; then
    php_extensions_string+=" libapache2-mod-php${PHP_VERSION}"
fi

echo
echo "==> Installing PHP extensions"
echo

# shellcheck disable=SC2086
apt-get install -y ${php_extensions_string}

#############################################
# Install Composer
#############################################

echo
echo "==> Installing Composer"
echo

curl -o composer-setup.php https://getcomposer.org/installer
php composer-setup.php --version="${COMPOSER_VERSION}" --filename=composer --install-dir=/usr/local/bin
rm composer-setup.php

#############################################
# Install PECL extensions
#############################################

declare -a pecl_extensions=()
readarray -d ' ' -t pecl_extensions < <(echo -n "${PHP_PECL_EXTENSIONS:-}")
readarray -d ' ' -t -O "${#pecl_extensions[@]}" pecl_extensions < <(echo -n "${PHP_PECL_EXTENSIONS_EXTRA:-}")

# install dh-php and PECL extensions if any are configured
if [ ${#pecl_extensions[@]} -gt 0 ]; then
    echo
    echo "==> Installing PECL extensions"
    echo

    apt-get install -y --no-install-recommends dh-php

    # shellcheck disable=SC2086,SC2048
    pecl install ${pecl_extensions[*]}
fi
