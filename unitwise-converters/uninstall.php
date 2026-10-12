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

// Remove options
delete_option( 'unitwise_version' );
