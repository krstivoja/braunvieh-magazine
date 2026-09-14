<?php
/**
 * Header/footer data from the main site.
 *
 * The main site shares its header/footer links as JSON
 * (BRAUNVIEH_MAIN_URL/wp-json/braunvieh/v1/header-footer). The magazine fetches it
 * hourly via WP-Cron and keeps the last good copy in an option, so visitors never
 * wait for the main site and a main-site outage never breaks the magazine header.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch the header/footer data from the main site and store it.
 *
 * Only a complete, schema-matching answer replaces the stored copy; anything else
 * is logged and ignored, so the last good links stay in place.
 *
 * @return bool True when fresh data was stored.
 */
function braunvieh_magazine_header_footer_refresh() {
	$lang = (string) BRAUNVIEH_HEADER_FOOTER_LANG;
	$url  = untrailingslashit( (string) BRAUNVIEH_MAIN_URL ) . '/wp-json/braunvieh/v1/header-footer?lang=' . rawurlencode( $lang );

	$response = wp_remote_get( $url, array(
		'timeout'     => 3,
		'redirection' => 2,
	) );

	if ( is_wp_error( $response ) ) {
		return braunvieh_magazine_header_footer_fail( $url, $response->get_error_message() );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return braunvieh_magazine_header_footer_fail( $url, 'HTTP ' . $code );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return braunvieh_magazine_header_footer_fail( $url, 'invalid JSON' );
	}

	$error = braunvieh_magazine_header_footer_validate( $data, $lang );
	if ( '' !== $error ) {
		return braunvieh_magazine_header_footer_fail( $url, $error );
	}

	$previous = get_option( 'braunvieh_magazine_header_footer', null );

	// Autoload off: only the header/footer render reads these, not every request.
	update_option( 'braunvieh_magazine_header_footer', $data, false );
	update_option( 'braunvieh_magazine_header_footer_fetched_at', time(), false );

	// Cached pages still carry the old header — clear LiteSpeed so the change shows.
	if ( $previous !== $data ) {
		do_action( 'litespeed_purge_all' );
	}

	return true;
}

/**
 * Check a decoded answer against schema version 1.
 *
 * @param array  $data Decoded JSON.
 * @param string $lang Requested language.
 * @return string Empty when valid, otherwise the reason.
 */
function braunvieh_magazine_header_footer_validate( $data, $lang ) {
	if ( ! isset( $data['schema_version'] ) || 1 !== $data['schema_version'] ) {
		return 'unsupported schema_version';
	}
	if ( ! isset( $data['lang'] ) || $lang !== $data['lang'] ) {
		return 'lang mismatch';
	}
	if ( ! array_key_exists( 'logo', $data ) || ! is_string( $data['logo'] ) ) {
		return 'missing or invalid key: logo';
	}
	foreach ( array( 'top_links', 'external_links', 'social_links', 'menu_social_links', 'menu', 'footer' ) as $key ) {
		if ( ! array_key_exists( $key, $data ) || ! is_array( $data[ $key ] ) ) {
			return 'missing or invalid key: ' . $key;
		}
	}
	return '';
}

/**
 * Log a failed refresh (one line) and report failure. The stored copy is kept.
 *
 * @param string $url    Requested URL.
 * @param string $reason Why it failed.
 * @return bool Always false.
 */
function braunvieh_magazine_header_footer_fail( $url, $reason ) {
	error_log( 'braunvieh-magazine: header-footer refresh failed (' . $url . '): ' . $reason ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	return false;
}

/**
 * The stored header/footer data (schema version 1), or an empty array.
 *
 * @return array
 */
function braunvieh_magazine_header_footer() {
	$data = get_option( 'braunvieh_magazine_header_footer' );

	// First run (cron has not fetched yet): fetch once inline. The lock keeps
	// concurrent first requests — or a down main site — from each waiting on it.
	if ( ! is_array( $data ) && ! get_transient( 'braunvieh_magazine_header_footer_lock' ) ) {
		set_transient( 'braunvieh_magazine_header_footer_lock', 1, MINUTE_IN_SECONDS );
		braunvieh_magazine_header_footer_refresh();
		$data = get_option( 'braunvieh_magazine_header_footer' );
	}

	return is_array( $data ) ? $data : array();
}

// Hourly background refresh.
add_action( 'braunvieh_magazine_header_footer_refresh', 'braunvieh_magazine_header_footer_refresh' );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'braunvieh_magazine_header_footer_refresh' ) ) {
		wp_schedule_event( time(), 'hourly', 'braunvieh_magazine_header_footer_refresh' );
	}
} );

// The event belongs to this theme — do not leave it running under another one.
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'braunvieh_magazine_header_footer_refresh' );
} );
