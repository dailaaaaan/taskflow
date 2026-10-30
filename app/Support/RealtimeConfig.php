<?php

namespace App\Support;

final class RealtimeConfig
{
    /**
     * Connection settings for Laravel Echo, sent as a page prop so one build
     * works in every environment. Null turns live updates off in the browser.
     * The app key is public by design; the secret never leaves the server.
     *
     * @return array<string, mixed>|null
     */
    public static function forBrowser(): ?array
    {
        $key = config('broadcasting.connections.pusher.key');

        if (config('broadcasting.default') !== 'pusher' || ! is_string($key) || $key === '') {
            return null;
        }

        $options = config()->array('broadcasting.connections.pusher.options');
        $host = is_string($options['host'] ?? null) ? $options['host'] : '';

        return [
            'key' => $key,
            'cluster' => $options['cluster'] ?? null,
            // pusher-js derives the socket host from the cluster, so a host is
            // only sent for a Pusher-compatible server such as the E2E one.
            'host' => str_ends_with($host, '.pusher.com') ? null : $host,
            'port' => is_numeric($options['port'] ?? null) ? (int) $options['port'] : 443,
            'scheme' => $options['scheme'] ?? 'https',
        ];
    }
}
