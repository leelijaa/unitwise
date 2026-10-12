<?php
/**
 * Script and style enqueuing.
 *
 * Converter JS/CSS loads only on converter pages — no global overhead.
 * Factors are passed via wp_localize_script so JS has no separate API call.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Assets {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
	}

	public static function enqueue_frontend(): void {
		if ( ! get_query_var( 'unitwise_cat' ) ) {
			return;
		}

		wp_enqueue_style(
			'unitwise-converter',
			UNITWISE_URL . 'assets/css/converter.css',
			[],
			UNITWISE_VERSION
		);
	}
}
