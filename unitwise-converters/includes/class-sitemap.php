<?php
/**
 * Custom WP Sitemaps provider for unitwise_converter posts.
 *
 * WP core sitemap omits CPTs with exclude_from_search=true.
 * This provider emits canonical /{category}/{from}-to-{to}/ URLs
 * so all converter pages appear in /wp-sitemap.xml.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Sitemap_Provider extends WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'unitwise_converters';
		$this->object_type = 'unitwise_converter';
	}

	public function get_url_list( $page_num, $object_subtype = '' ): array {
		$per_page = $this->get_max_num_urls();
		$offset   = ( $page_num - 1 ) * $per_page;

		$posts = get_posts( [
			'post_type'      => 'unitwise_converter',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		] );

		$urls = [];

		// Silo category hub pages (only on first page of sitemap)
		if ( $page_num === 1 && class_exists( 'Unitwise_Registry' ) ) {
			foreach ( Unitwise_Registry::get_categories() as $slug => $cat ) {
				$urls[] = [ 'loc' => home_url( "/{$slug}/" ) ];
			}
		}

		foreach ( $posts as $post ) {
			$from = get_post_meta( $post->ID, 'unitwise_from_unit', true );
			$to   = get_post_meta( $post->ID, 'unitwise_to_unit',   true );
			$terms = wp_get_post_terms( $post->ID, 'unitwise_category', [ 'fields' => 'slugs' ] );
			$cat  = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '';

			if ( ! $from || ! $to || ! $cat ) {
				continue;
			}

			$urls[] = [
				'loc'     => home_url( "/{$cat}/{$from}-to-{$to}/" ),
				'lastmod' => get_post_modified_time( 'Y-m-d', false, $post ),
			];
		}

		return $urls;
	}

	public function get_max_num_pages( $object_subtype = '' ): int {
		$total = (int) ( wp_count_posts( 'unitwise_converter' )->publish ?? 0 );
		$pages = (int) ceil( $total / $this->get_max_num_urls() );
		// Always emit at least page 1 so silo URLs are included before the seeder runs.
		return max( 1, $pages );
	}
}
