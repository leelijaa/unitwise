<?php
/**
 * CPT registration — unitwise_converter.
 *
 * PLUGIN LAYER: never move CPT logic to the theme. Deactivating the theme
 * must leave all converter posts, URLs, and metadata intact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Post_Type {

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {
		$labels = [
			'name'                  => __( 'Converters',               'unitwise-converters' ),
			'singular_name'         => __( 'Converter',                'unitwise-converters' ),
			'add_new_item'          => __( 'Add New Converter',        'unitwise-converters' ),
			'edit_item'             => __( 'Edit Converter',           'unitwise-converters' ),
			'new_item'              => __( 'New Converter',            'unitwise-converters' ),
			'view_item'             => __( 'View Converter',           'unitwise-converters' ),
			'search_items'          => __( 'Search Converters',        'unitwise-converters' ),
			'not_found'             => __( 'No converters found.',     'unitwise-converters' ),
			'not_found_in_trash'    => __( 'No converters in Trash.',  'unitwise-converters' ),
			'all_items'             => __( 'All Converters',           'unitwise-converters' ),
			'menu_name'             => __( 'Converters',               'unitwise-converters' ),
		];

		register_post_type( 'unitwise_converter', [
			'labels'             => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => [ 'title', 'editor', 'custom-fields' ],
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-randomize',
			// Rewrite handled by Unitwise_Rewrites — do not set rewrite here.
			'rewrite'            => false,
		] );
	}
}
