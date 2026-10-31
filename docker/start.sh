#!/bin/sh
# Runs on every start. Render's free plan restarts the instance after 15 idle
# minutes and offers no shell or one-off jobs, so this is the only place for
# these steps. A failed step stops the deploy instead of serving a broken app.
set -e

# Render knows the public address of the service before anyone configures it.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Not in the image: these caches depend on variables that only exist here.
php artisan optimize

php artisan migrate --force

# Puts the shared demo workspace back to its original state.
if [ "$DEMO_ENABLED" = "true" ]; then
    php artisan db:seed --class=Database\\Seeders\\DemoSeeder --force
fi

exec frankenphp run --config /etc/frankenphp/Caddyfile
