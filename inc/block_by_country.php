<?php
/**
 * Simple Geo-Blocking with IP cache and Cloudflare support.
 * Blocks access for selected countries using ip-api.com (free).
 */

add_action('init', function () {

    // Get visitor IP (Cloudflare-friendly)
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    $ip = trim(explode(',', $ip)[0]); // Handle multiple IPs

    // 🏠 Skip geo-blocking for localhost/private IPs
    if (
        in_array($ip, ['127.0.0.1', '::1'], true) ||
        preg_match('/^(10|172\.(1[6-9]|2[0-9]|3[01])|192\.168)\./', $ip)
    ) {
        // Just skip — no message, no interruption
        return;
    }

    // ⚡ Cache lookup
    $cache_key = 'geo_block_' . md5($ip);
    $country   = get_transient($cache_key);

    if (false === $country) {
        // Free API (no SSL on free tier, so disable verify)
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode", [
            'timeout'   => 3,
            'sslverify' => false,
        ]);

        // Fail gracefully if API unreachable
        if (is_wp_error($response)) {
            return;
        }

        $body    = json_decode(wp_remote_retrieve_body($response), true);
        $country = $body['countryCode'] ?? '';

        // Validate response
        if (empty($country) || strlen($country) !== 2) {
            set_transient($cache_key, 'UNKNOWN', HOUR_IN_SECONDS);
            return;
        }

        // Cache for 24 hours
        set_transient($cache_key, $country, DAY_IN_SECONDS);
    }

    // 🚫 Blocked countries (ISO codes)
    $blocked = ['CN', 'RU', 'SG', 'TW', 'HK'];

    if (in_array($country, $blocked, true)) {
        wp_die(
            '<h1>Access Restricted</h1><p>This website is not available in your region.</p>',
            'Access Restricted',
            ['response' => 403]
        );
    }
}, 1);