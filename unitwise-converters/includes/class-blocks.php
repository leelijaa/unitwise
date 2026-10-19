<?php
/**
 * Gutenberg block registration.
 *
 * All blocks are server-rendered (render.php) for SEO-crawlable HTML.
 * A shared no-build editor.js provides the edit UI for the hub homepage blocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Blocks {

	public static function init(): void {
		add_action( 'init',              [ __CLASS__, 'register_editor_script' ] );
		add_action( 'init',              [ __CLASS__, 'register_blocks' ], 20 );
		add_filter( 'block_categories_all', [ __CLASS__, 'add_block_category' ], 5 );
	}

	public static function register_editor_script(): void {
		$path    = UNITWISE_DIR . 'blocks/editor.js';
		$version = file_exists( $path ) ? (string) filemtime( $path ) : UNITWISE_VERSION;

		wp_register_script(
			'unitwise-blocks-editor',
			UNITWISE_URL . 'blocks/editor.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ],
			$version,
			true
		);
	}

	public static function register_blocks(): void {
		$blocks = [
			'converter',
			'conversion-table',
			'popular-converters',
			'hub-hero',
			'hub-category-grid',
			'hub-newsletter',
			'recent-posts',
		];

		foreach ( $blocks as $block ) {
			$path = UNITWISE_DIR . 'blocks/' . $block;
			if ( is_dir( $path ) ) {
				register_block_type( $path );
			}
		}
	}

	public static function add_block_category( array $categories ): array {
		array_unshift(
			$categories,
			[
				'slug'  => 'unitwise',
				'title' => __( 'UnitWise', 'unitwise-converters' ),
				'icon'  => null,
			]
		);
		return $categories;
	}
}
