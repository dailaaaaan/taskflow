# syntax=docker/dockerfile:1

# FrankenPHP is PHP and its web server in one process, so the image needs no
# supervisor to keep nginx and PHP-FPM together. See docs/adr/0011.
FROM dunglas/frankenphp:1-php8.5-bookworm AS base
RUN install-php-extensions bcmath intl opcache pdo_pgsql
WORKDIR /app

FROM node:24-bookworm-slim AS node

# Wayfinder runs artisan during the Vite build, so assets need PHP and vendor.
FROM base AS build
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && install-php-extensions @composer

# Dependencies first: their layers survive a change in the application code.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
# Vite reads this at build time. Without it the page titles would say "Laravel".
ENV VITE_APP_NAME=TaskFlow
RUN composer dump-autoload --optimize --no-dev \
    && composer check-platform-reqs --no-dev \
    && npm run build \
    && rm -rf node_modules resources/js

FROM base AS production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/taskflow.ini"
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --from=build /app /app

# Render's port is above 1024, so the server needs no root and no capabilities.
RUN useradd --uid 1000 --no-create-home taskflow \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R taskflow:taskflow storage bootstrap/cache /config/caddy /data/caddy
USER taskflow

CMD ["sh", "/app/docker/start.sh"]
