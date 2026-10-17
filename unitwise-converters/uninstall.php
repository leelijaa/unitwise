<?php
/**
 * Runs when the plugin is deleted from WP Admin.
 * Removes all converter posts, taxonomy terms, and plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all unitwise_converter posts
$posts = get_posts( [
	'post_type'      => 'unitwise_converter',
	'post_status'    => 'any',
	'numberposts'    => -1,
	'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Remove taxonomy terms
$terms = get_terms( [
	'taxonomy'   => 'unitwise_category',
	'hide_empty' => false,
	'fields'     => 'ids',
] );

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'unitwise_category' );
	}
}

// Remove value pages (native WP pages with _uw_value_page meta)
$value_pages = get_posts( [
	'post_type'   => 'page',
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
	'meta_key'    => '_uw_value_page',
	'meta_value'  => '1',
] );
foreach ( $value_pages as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Remove options and transients
delete_option( 'uw_vp_sections' );
delete_transient( 'uw_value_pages_data' );
delete_transient( 'uw_footer_cat_cols' );
delete_transient( 'uw_footer_recent_posts' );
