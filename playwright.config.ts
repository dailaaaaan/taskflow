import { randomBytes } from 'node:crypto';
import { defineConfig, devices } from '@playwright/test';

const APP_URL = 'http://127.0.0.1:8010';
const SOCKET_PORT = 8095;

/**
 * Real process variables win over .env, so these make the run self-contained.
 * Reverb is a local Pusher-compatible server: the real Pusher driver and
 * pusher-js run end to end with no external network and no secrets.
 */
const environment = {
    APP_ENV: 'e2e',
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_URL,
    // The seed below starts from an empty database: never the development one.
    DB_DATABASE: 'taskflow_test',
    BROADCAST_CONNECTION: 'pusher',
    PUSHER_APP_ID: 'e2e',
    PUSHER_APP_KEY: 'e2e-key',
    PUSHER_APP_SECRET: 'e2e-secret',
    PUSHER_APP_CLUSTER: 'mt1',
    PUSHER_HOST: '127.0.0.1',
    PUSHER_PORT: String(SOCKET_PORT),
    PUSHER_SCHEME: 'http',
    REVERB_APP_ID: 'e2e',
    REVERB_APP_KEY: 'e2e-key',
    REVERB_APP_SECRET: 'e2e-secret',
};

export default defineConfig({
    testDir: 'e2e',
    // One shared database and users, so tests run one at a time.
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: APP_URL,
        testIdAttribute: 'data-test',
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: devices['Desktop Chrome'] }],
    webServer: [
        {
            command: `php artisan reverb:start --host=127.0.0.1 --port=${SOCKET_PORT}`,
            port: SOCKET_PORT,
            env: environment,
        },
        {
            // A cached config would ignore `environment` and hit the wrong
            // database, so it is cleared before the seed. --no-reload keeps
            // `artisan serve` from dropping these variables.
            command: `php artisan config:clear && php artisan migrate:fresh --seed --force && php artisan serve --host=127.0.0.1 --port=8010 --no-reload`,
            url: `${APP_URL}/up`,
            env: environment,
            timeout: 60_000,
        },
    ],
});
