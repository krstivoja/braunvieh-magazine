<?php
/**
 * Cookie banner translations for English
 */

add_filter('nsc_bar_filter_json_config_string_with_js', function($json_string) {
    // English override (TranslatePress sets the WP locale per language).
    if (strncmp(get_locale(), 'en', 2) === 0) {
        // Decode, modify, re-encode
        $config = json_decode($json_string, true);
        if ($config) {
            // Button texts
            $config['content']['dismiss'] = 'Got it';
            $config['content']['deny'] = 'Decline';
            $config['content']['allow'] = 'Accept';
            $config['content']['savesettings'] = 'Save';
            $config['content']['link'] = 'Learn more';
            $config['content']['href'] = home_url('/en/privacy-policy/');

            // Banner message
            $config['content']['message'] = 'This website uses cookies to ensure you get the best experience on our website.';

            // Cookie types labels
            if (isset($config['cookietypes']) && is_array($config['cookietypes'])) {
                foreach ($config['cookietypes'] as &$type) {
                    switch ($type['cookie_suffix'] ?? '') {
                        case 'tech':
                            $type['label'] = 'Technical';
                            break;
                        case 'marketing':
                            $type['label'] = 'Marketing';
                            break;
                    }
                }
            }

            $json_string = json_encode($config, JSON_UNESCAPED_UNICODE);
        }
    }

    return $json_string;
});
