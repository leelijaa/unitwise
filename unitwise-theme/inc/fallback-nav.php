<?php
/**
 * Fallback navigation — shown when no menu is assigned to 'primary'.
 * Renders links to known silo URLs from the registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function unitwise_fallback_nav(): void {
	if ( ! class_exists( 'Unitwise_Registry' ) ) {
		return;
	}

	$categories = Unitwise_Registry::get_categories();
	if ( empty( $categories ) ) {
		return;
	}

	echo '<ul class="nav-menu unitwise-fallback-nav">';
	foreach ( $categories as $slug => $cat ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( home_url( "/{$slug}/" ) ),
			esc_html( $cat['name'] )
		);
	}
	echo '</ul>';
}
