#!/bin/sh
#
# Functional tests for EXT:photoswipe.
#
# Run inside the php container:
#   docker compose exec php /var/www/html/cms/packages/photoswipe/Build/runFunctionalTests.sh
#
# The suite creates its own databases "<name>_ft1" and therefore needs a user
# with CREATE/DROP DATABASE privileges. Defaults to root via DB1_ROOT_PASSWORD;
# override through the typo3Database* environment variables.
set -e

EXT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$EXT_DIR"

# The test instance expects the extension below typo3conf/ext/<key>.
mkdir -p .Build/public/typo3conf/ext
if [ ! -e .Build/public/typo3conf/ext/photoswipe ]; then
    ln -s "$EXT_DIR" .Build/public/typo3conf/ext/photoswipe
fi

export typo3DatabaseDriver="${typo3DatabaseDriver:-pdo_mysql}"
export typo3DatabaseHost="${typo3DatabaseHost:-${DB1_HOST:-mysql1}}"
export typo3DatabasePort="${typo3DatabasePort:-${DB1_PORT:-3306}}"
export typo3DatabaseName="${typo3DatabaseName:-${DB1_DATABASE:-dbproject1}}"
export typo3DatabaseUsername="${typo3DatabaseUsername:-root}"
export typo3DatabasePassword="${typo3DatabasePassword:-${DB1_ROOT_PASSWORD:-}}"

exec .Build/bin/phpunit -c Build/FunctionalTests.xml "$@"
