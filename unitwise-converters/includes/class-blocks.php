<?php
/**
 * Gutenberg block registration.
 *
 * All blocks are server-rendered (render.php) for SEO-crawlable HTML.
 * view.js provides interactive hydration without jQuery.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Blocks {

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
	}

	public static function register_blocks(): void {
		$blocks = [ 'converter', 'conversion-table', 'popular-converters' ];

		foreach ( $blocks as $block ) {
			$path = UNITWISE_DIR . 'blocks/' . $block;
			if ( is_dir( $path ) ) {
				register_block_type( $path );
			}
		}
	}
}
