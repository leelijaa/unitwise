<?php
/**
 * Taxonomy registration — unitwise_category.
 *
 * PLUGIN LAYER: never move taxonomy logic to the theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Taxonomy {

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {
		$labels = [
			'name'              => __( 'Converter Categories', 'unitwise-converters' ),
			'singular_name'     => __( 'Converter Category',  'unitwise-converters' ),
			'search_items'      => __( 'Search Categories',   'unitwise-converters' ),
			'all_items'         => __( 'All Categories',       'unitwise-converters' ),
			'edit_item'         => __( 'Edit Category',        'unitwise-converters' ),
			'update_item'       => __( 'Update Category',      'unitwise-converters' ),
			'add_new_item'      => __( 'Add New Category',     'unitwise-converters' ),
			'new_item_name'     => __( 'New Category Name',    'unitwise-converters' ),
			'menu_name'         => __( 'Categories',           'unitwise-converters' ),
		];

		register_taxonomy( 'unitwise_category', [ 'unitwise_converter' ], [
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => 'unitwise_category',
			// Rewrite handled by Unitwise_Rewrites.
			'rewrite'            => false,
		] );
	}
}
