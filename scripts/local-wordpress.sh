#!/usr/bin/env bash
# Local Niloo theme preview (SQLite, no MySQL password needed)
set -euo pipefail

LOCAL="${LOCAL_WP_ROOT:-$HOME/Downloads/nilooweb-local}"
THEME_SRC="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${LOCAL_WP_PORT:-8888}"

if [[ ! -f "$LOCAL/wp-settings.php" ]]; then
  echo "First run: bootstrapping WordPress in $LOCAL ..."
  mkdir -p "$LOCAL"
  cd "$LOCAL"
  curl -fsSL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1
  curl -fsSL -o /tmp/sqlite-di.zip "https://downloads.wordpress.org/plugin/sqlite-database-integration.2.2.6.zip"
  unzip -q -o /tmp/sqlite-di.zip -d wp-content/plugins/
  cp -f wp-content/plugins/sqlite-database-integration/db.copy wp-content/db.php
  if [[ ! -f wp-cli.phar ]]; then
    curl -fsSL -o wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
  fi
fi

ln -sfn "$THEME_SRC" "$LOCAL/wp-content/themes/niloo-web"

cd "$LOCAL"
if ! php wp-cli.phar core is-installed 2>/dev/null; then
  [[ -f wp-config.php ]] || cp wp-config-sample.php wp-config.php
  php wp-cli.phar config set WP_DEBUG true --raw --type=constant
  php wp-cli.phar config set WP_ENVIRONMENT_TYPE local --type=constant
  php wp-cli.phar config shuffle-salts
  php wp-cli.phar core install \
    --url="http://127.0.0.1:$PORT" \
    --title="آکادمی نیلو — لوکال" \
    --admin_user="admin" \
    --admin_password="NilooLocal2026!" \
    --admin_email="admin@niloo.local" \
    --skip-email
  php wp-cli.phar theme activate niloo-web
  php wp-cli.phar rewrite structure '/%postname%/' --hard
fi

export PHP_CLI_SERVER_WORKERS=4
echo ""
echo "Niloo local: http://127.0.0.1:$PORT"
echo "Admin:       http://127.0.0.1:$PORT/wp-admin  (admin / NilooLocal2026!)"
echo "Theme path:  $THEME_SRC (symlinked)"
echo ""
exec php wp-cli.phar server --host=127.0.0.1 --port="$PORT"