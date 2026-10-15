<?php
/**
 * Custom rewrite rules for the silo URL structure.
 *
 * Converter: /{category}/{from}-to-{to}/  e.g. /weight/grams-to-kilograms/
 * Silo:      /{category}/                 e.g. /weight/
 *
 * PLUGIN LAYER: the theme never registers rewrites. Deactivating the theme
 * leaves all silo URLs intact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Rewrites {

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
		add_filter( 'template_include', [ __CLASS__, 'route_template' ] );
		add_filter( 'post_type_link', [ __CLASS__, 'converter_permalink' ], 10, 2 );
		add_action( 'template_redirect', [ __CLASS__, 'handle_legacy_redirects' ] );
	}

	/**
	 * Register one pair of rewrite rules per category.
	 * Called on `init` and also directly from the activation hook.
	 */
	public static function register(): void {
		foreach ( Unitwise_Registry::get_categories() as $cat_slug => $cat ) {
			$escaped = preg_quote( $cat_slug, '#' );

			// Converter page: /weight/grams-to-kilograms/
			add_rewrite_rule(
				'^' . $escaped . '/([a-z_]+)-to-([a-z_]+)/?$',
				'index.php?unitwise_cat=' . $cat_slug . '&unitwise_from=$matches[1]&unitwise_to=$matches[2]',
				'top'
			);

			// Category silo landing: /weight/
			add_rewrite_rule(
				'^' . $escaped . '/?$',
				'index.php?unitwise_cat=' . $cat_slug,
				'top'
			);
		}
	}

	public static function add_query_vars( array $vars ): array {
		$vars[] = 'unitwise_cat';
		$vars[] = 'unitwise_from';
		$vars[] = 'unitwise_to';
		return $vars;
	}

	/**
	 * Intercept the template selection for unitwise routes.
	 */
	public static function route_template( string $template ): string {
		$cat  = get_query_var( 'unitwise_cat' );
		$from = get_query_var( 'unitwise_from' );
		$to   = get_query_var( 'unitwise_to' );

		if ( ! $cat ) {
			return $template;
		}

		if ( $from && $to ) {
			return self::load_converter_template( $template, $cat, $from, $to );
		}

		return self::load_silo_template( $template, $cat );
	}

	private static function load_converter_template(
		string $template,
		string $cat,
		string $from,
		string $to
	): string {
		global $wp_query, $post;

		$query = new WP_Query( [
			'post_type'      => 'unitwise_converter',
			'posts_per_page' => 1,
			'meta_query'     => [
				'relation' => 'AND',
				[ 'key' => 'unitwise_from_unit', 'value' => $from ],
				[ 'key' => 'unitwise_to_unit',   'value' => $to   ],
			],
			'tax_query' => [
				[
					'taxonomy' => 'unitwise_category',
					'field'    => 'slug',
					'terms'    => $cat,
				],
			],
		] );

		if ( $query->have_posts() ) {
			$query->the_post();
			$post              = $query->post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$wp_query          = $query;       // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$wp_query->is_single   = true;
			$wp_query->is_singular = true;
			$wp_query->is_tax      = false;
			$wp_query->is_archive  = false;
			// Carry unitwise vars so title/SEO filters can read them
			$wp_query->set( 'unitwise_cat',  $cat );
			$wp_query->set( 'unitwise_from', $from );
			$wp_query->set( 'unitwise_to',   $to );
			setup_postdata( $post );
		}

		$located = locate_template( [
			'single-unitwise_converter.php',
			'single.php',
			'index.php',
		] );

		return $located ?: $template;
	}

	private static function load_silo_template( string $template, string $cat_slug ): string {
		global $wp_query;

		$term = get_term_by( 'slug', $cat_slug, 'unitwise_category' );

		if ( $term instanceof WP_Term ) {
			$wp_query->queried_object    = $term; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$wp_query->queried_object_id = $term->term_id; // phpcs:ignore
			$wp_query->is_tax            = true; // phpcs:ignore
			$wp_query->is_archive        = true; // phpcs:ignore

			// Load all converters for this category so templates can iterate.
			$posts_query = new WP_Query( [
				'post_type'      => 'unitwise_converter',
				'posts_per_page' => -1,
				'tax_query'      => [
					[
						'taxonomy' => 'unitwise_category',
						'field'    => 'slug',
						'terms'    => $cat_slug,
					],
				],
				'orderby' => 'title',
				'order'   => 'ASC',
			] );

			// Stash on the global query so templates can access them.
			$wp_query->unitwise_converter_posts = $posts_query->posts; // phpcs:ignore
		}

		$located = locate_template( [
			'taxonomy-unitwise_category.php',
			'taxonomy.php',
			'archive.php',
			'index.php',
		] );

		return $located ?: $template;
	}

	/**
	 * Rewrite get_permalink() for unitwise_converter posts to the silo URL.
	 */
	public static function converter_permalink( string $link, WP_Post $post ): string {
		if ( 'unitwise_converter' !== $post->post_type ) {
			return $link;
		}

		$from = get_post_meta( $post->ID, 'unitwise_from_unit', true );
		$to   = get_post_meta( $post->ID, 'unitwise_to_unit',   true );
		$cats = wp_get_post_terms( $post->ID, 'unitwise_category', [ 'fields' => 'slugs' ] );
		if ( is_wp_error( $cats ) ) {
			return $link;
		}
		$cat = $cats[0] ?? 'weight';

		if ( $from && $to ) {
			return home_url( "/{$cat}/{$from}-to-{$to}/" );
		}

		return $link;
	}

	/**
	 * 301-redirect legacy ?p= and taxonomy query-var URLs to their clean equivalents.
	 */
	public static function handle_legacy_redirects(): void {
		// Redirect ?p=123 for converter posts to the silo URL
		if ( ! is_singular() && ! empty( $_GET['p'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$post = get_post( (int) $_GET['p'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $post instanceof WP_Post && 'unitwise_converter' === $post->post_type ) {
				wp_redirect( get_permalink( $post ), 301 );
				exit;
			}
		}

		// Redirect taxonomy query-var URLs (?unitwise_category=weight) to clean silo URLs
		if ( is_tax( 'unitwise_category' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$clean = home_url( '/' . $term->slug . '/' );
				if ( ! str_contains( $_SERVER['REQUEST_URI'] ?? '', '/' . $term->slug . '/' ) ) {
					wp_redirect( $clean, 301 );
					exit;
				}
			}
		}
	}
}
