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

	public function get_url_list( int $page_num, string $object_subtype = '' ): array {
		$per_page = $this->get_max_num_urls();
		$offset   = ( $page_num - 1 ) * $per_page;

		$posts = get_posts( [
			'post_type'      => 'unitwise_converter',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'no_found_rows'  => false,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		] );

		$urls = [];
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

	public function get_max_num_pages( string $object_subtype = '' ): int {
		$total = wp_count_posts( 'unitwise_converter' )->publish ?? 0;
		return (int) ceil( $total / $this->get_max_num_urls() );
	}
}
