<?php
/**
 * JSON-LD schema output.
 *
 * Outputs BreadcrumbList, SoftwareApplication, HowTo, and FAQPage via wp_head.
 * Each schema node is only emitted when the page has the relevant content.
 *
 * PLUGIN LAYER: schema follows the data, not the theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Schema {

	public static function init(): void {
		add_action( 'wp_head', [ __CLASS__, 'output' ], 5 );
	}

	public static function output(): void {
		if ( defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$cat  = get_query_var( 'unitwise_cat' );
		$from = get_query_var( 'unitwise_from' );
		$to   = get_query_var( 'unitwise_to' );

		// Blog posts
		if ( is_singular( 'post' ) ) {
			self::blog_post_schema();
			return;
		}

		// Value pages (native WP pages with _uw_value_page meta)
		if ( is_singular( 'page' ) ) {
			$vid = get_the_ID();
			if ( $vid && '1' === get_post_meta( $vid, '_uw_value_page', true ) ) {
				self::value_page_schema( $vid );
				return;
			}
		}

		if ( $cat && $from && $to ) {
			self::converter_schema( $cat, $from, $to );
			return;
		}

		if ( $cat ) {
			self::silo_schema( $cat );
			return;
		}

		if ( is_front_page() ) {
			self::home_schema();
			return;
		}

		if ( is_home() ) {
			self::blog_index_schema();
		}
	}

	// -------------------------------------------------------------------------
	// Converter page

	private static function converter_schema( string $cat, string $from, string $to ): void {
		$cat_info    = Unitwise_Registry::get_category( $cat );
		$from_plural = Unitwise_Registry::get_label( $from, true );
		$to_plural   = Unitwise_Registry::get_label( $to,   true );
		$converter_url = home_url( "/{$cat}/{$from}-to-{$to}/" );

		$engine  = new Unitwise_Engine( $cat );
		$formula = $engine->get_formula( $from, $to );
		$example = $engine->get_worked_example( 1, $from, $to );

		$graph   = [];

		// WebPage
		$graph[] = [
			'@type'      => 'WebPage',
			'@id'        => $converter_url . '#webpage',
			'url'        => $converter_url,
			'name'       => "{$from_plural} to {$to_plural} Converter",
			'inLanguage' => get_bloginfo( 'language' ),
			'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
		];

		// BreadcrumbList
		$graph[] = self::breadcrumb( [
			[ 'name' => __( 'Home', 'unitwise-converters' ), 'url' => home_url( '/' ) ],
			[ 'name' => $cat_info['name'] ?? ucfirst( $cat ), 'url' => home_url( "/{$cat}/" ) ],
			[ 'name' => "{$from_plural} to {$to_plural}", 'url' => $converter_url ],
		] );

		// WebApplication
		$graph[] = [
			'@type'               => 'WebApplication',
			'@id'                 => $converter_url . '#webapp',
			'name'                => "{$from_plural} to {$to_plural} Converter",
			'applicationCategory' => 'UtilityApplication',
			'operatingSystem'     => 'Web',
			'url'                 => $converter_url,
			'offers'              => [ '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ],
		];

		// HowTo
		$graph[] = [
			'@type' => 'HowTo',
			'name'  => sprintf(
				/* translators: 1: from-unit plural, 2: to-unit plural */
				__( 'How to Convert %1$s to %2$s', 'unitwise-converters' ),
				$from_plural,
				$to_plural
			),
			'step' => [
				[
					'@type' => 'HowToStep',
					'name'  => __( 'Enter the value', 'unitwise-converters' ),
					'text'  => sprintf(
						/* translators: %s: from-unit plural */
						__( 'Type the number of %s you want to convert into the input field.', 'unitwise-converters' ),
						$from_plural
					),
				],
				[
					'@type' => 'HowToStep',
					'name'  => __( 'Apply the formula', 'unitwise-converters' ),
					'text'  => sprintf(
						/* translators: %s: formula string */
						__( 'Use the formula: %s', 'unitwise-converters' ),
						$formula
					),
				],
				[
					'@type' => 'HowToStep',
					'name'  => __( 'Read the result', 'unitwise-converters' ),
					'text'  => sprintf(
						/* translators: %s: worked example */
						__( 'The result appears instantly. Example: %s', 'unitwise-converters' ),
						$example
					),
				],
			],
		];

		// FAQPage
		$faqs = self::get_faqs( $from, $to, $cat, $engine );
		if ( ! empty( $faqs ) ) {
			$graph[] = [
				'@type'      => 'FAQPage',
				'mainEntity' => array_map( fn( $item ) => [
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $item['q'] ),
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $item['a'] ),
					],
				], $faqs ),
			];
		}

		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// -------------------------------------------------------------------------
	// Value page (specific-value converter pages)

	private static function value_page_schema( int $id ): void {
		$value = (float) get_post_meta( $id, '_uw_vp_value', true );
		$from  = sanitize_key( get_post_meta( $id, '_uw_vp_from', true ) );
		$to    = sanitize_key( get_post_meta( $id, '_uw_vp_to',   true ) );
		$cat   = sanitize_key( get_post_meta( $id, '_uw_vp_cat',  true ) ?: 'weight' );

		if ( ! $from || ! $to ) {
			return;
		}

		$engine  = new Unitwise_Engine( $cat );
		$result  = $engine->format_result( $engine->convert( $value, $from, $to ) );
		$plural  = ( 1.0 !== $value );
		$from_l   = Unitwise_Registry::get_label( $from, $plural );
		$from_l_p = Unitwise_Registry::get_label( $from, true );
		$to_l     = Unitwise_Registry::get_label( $to,   true );
		$formula = $engine->get_formula( $from, $to );
		$v_str   = ( floor( $value ) == $value ) ? (int) $value : $value;
		$url     = (string) get_permalink( $id );

		$cat_info      = Unitwise_Registry::get_category( $cat );
		$cat_name      = $cat_info['name'] ?? ucfirst( $cat );
		$cat_url       = home_url( "/{$cat}/" );
		$converter_url = home_url( "/{$cat}/{$from}-to-{$to}/" );

		$graph = [];

		// WebPage
		$graph[] = [
			'@type'      => 'WebPage',
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => "{$v_str} {$from_l} to {$to_l}",
			'inLanguage' => get_bloginfo( 'language' ),
			'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
		];

		// BreadcrumbList: Home → Category → Converter → Value page
		$graph[] = self::breadcrumb( [
			[ 'name' => __( 'Home', 'unitwise-converters' ), 'url' => home_url( '/' ) ],
			[ 'name' => $cat_name, 'url' => $cat_url ],
			[ 'name' => "{$from_l_p} to {$to_l} Converter", 'url' => $converter_url ],
			[ 'name' => "{$v_str} {$from_l} to {$to_l}", 'url' => $url ],
		] );

		// FAQPage: 3 value-specific Q&As
		$graph[] = [
			'@type'      => 'FAQPage',
			'mainEntity' => [
				[
					'@type'          => 'Question',
					'name'           => "How many {$to_l} is {$v_str} {$from_l}?",
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => "{$v_str} {$from_l} equals {$result} {$to_l}. Formula: {$formula}.",
					],
				],
				[
					'@type'          => 'Question',
					'name'           => "How do you convert {$v_str} {$from_l} to {$to_l}?",
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => "To convert {$v_str} {$from_l} to {$to_l}, apply the formula: {$formula}. The result is {$result} {$to_l}.",
					],
				],
				[
					'@type'          => 'Question',
					'name'           => "What is {$v_str} {$from_l} in {$to_l}?",
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => "{$v_str} {$from_l} is equal to {$result} {$to_l}.",
					],
				],
			],
		];

		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// -------------------------------------------------------------------------
	// Silo landing

	private static function silo_schema( string $cat_slug ): void {
		$cat      = Unitwise_Registry::get_category( $cat_slug );
		$cat_name = $cat['name'] ?? ucfirst( $cat_slug );
		$silo_url = home_url( "/{$cat_slug}/" );
		$graph    = [
			[
				'@type'      => 'CollectionPage',
				'@id'        => $silo_url . '#collection',
				'url'        => $silo_url,
				'name'       => $cat_name . ' Converters',
				'inLanguage' => get_bloginfo( 'language' ),
				'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
			],
			self::breadcrumb( [
				[ 'name' => __( 'Home', 'unitwise-converters' ), 'url' => home_url( '/' ) ],
				[ 'name' => $cat_name, 'url' => $silo_url ],
			] ),
			[
				'@type'      => 'FAQPage',
				'mainEntity' => [
					[
						'@type'          => 'Question',
						'name'           => sprintf( 'How do I use a %s converter?', strtolower( $cat_name ) ),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => 'Select a converter from the list, enter your value, and the result appears instantly.',
						],
					],
					[
						'@type'          => 'Question',
						'name'           => 'Are the conversions accurate?',
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => 'Yes — all converters use exact internationally standardised conversion factors with full floating-point precision.',
						],
					],
					[
						'@type'          => 'Question',
						'name'           => 'Do the converters work without JavaScript?',
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => 'Yes — each converter page includes a static conversion table and worked example that display without JavaScript.',
						],
					],
				],
			],
		];
		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// -------------------------------------------------------------------------
	// Homepage

	private static function home_schema(): void {
		$logo_url = get_theme_mod( 'unitwise_logo_url', '' );

		$org_node = [
			'@type'  => 'Organization',
			'@id'    => home_url( '/#organization' ),
			'name'   => get_bloginfo( 'name' ),
			'url'    => home_url( '/' ),
			'sameAs' => array_values( array_filter( [
				get_theme_mod( 'footer_social_twitter',  '' ),
				get_theme_mod( 'footer_social_youtube',  '' ),
				get_theme_mod( 'footer_social_linkedin', '' ),
			] ) ),
		];
		if ( $logo_url ) {
			$org_node['logo'] = [ '@type' => 'ImageObject', 'url' => $logo_url ];
		}

		$graph = [
			[
				'@type'      => 'WebPage',
				'@id'        => home_url( '/#webpage' ),
				'url'        => home_url( '/' ),
				'name'       => get_bloginfo( 'name' ),
				'inLanguage' => get_bloginfo( 'language' ),
				'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
			],
			[
				'@type' => 'WebSite',
				'@id'   => home_url( '/#website' ),
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			],
			$org_node,
		];
		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// Blog index (CollectionPage)

	private static function blog_index_schema(): void {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$url   = $page_for_posts ? (string) get_permalink( $page_for_posts ) : home_url( '/blog/' );
		$title = $page_for_posts ? get_the_title( $page_for_posts ) : ( get_bloginfo( 'name' ) . ' Blog' );
		$graph = [
			[
				'@type'      => 'CollectionPage',
				'@id'        => $url . '#blog',
				'name'       => $title,
				'url'        => $url,
				'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
				'inLanguage' => get_bloginfo( 'language' ),
			],
			self::breadcrumb( [
				[ 'name' => __( 'Home', 'unitwise-converters' ), 'url' => home_url( '/' ) ],
				[ 'name' => $title, 'url' => $url ],
			] ),
		];
		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// Blog post (Article)

	private static function blog_post_schema(): void {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}
		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( ! $author_id ) {
			return; // Orphaned post — no valid author; skip schema to avoid invalid @id fragment.
		}
		$url         = (string) get_permalink( $post_id );
		$title       = wp_strip_all_tags( get_the_title( $post_id ) );
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$published   = get_the_date( 'c', $post_id );
		$modified    = get_the_modified_date( 'c', $post_id );

		$article = [
			'@type'             => 'Article',
			'@id'               => $url . '#article',
			'url'               => $url,
			'headline'          => $title,
			'datePublished'     => $published,
			'dateModified'      => $modified,
			'mainEntityOfPage'  => [ '@id' => $url . '#webpage' ],
			'isPartOf'          => [ '@id' => home_url( '/#website' ) ],
			'author'            => [
				'@type' => 'Person',
				'@id'   => get_author_posts_url( $author_id ) . '#author',
				'name'  => $author_name,
				'url'   => get_author_posts_url( $author_id ),
			],
			'publisher'         => [ '@id' => home_url( '/#organization' ) ],
			'inLanguage'        => get_bloginfo( 'language' ),
		];
		if ( has_post_thumbnail( $post_id ) ) {
			$article['image'] = [ '@type' => 'ImageObject', 'url' => get_the_post_thumbnail_url( $post_id, 'large' ) ];
		}

		// BreadcrumbList: Home → Category (or Blog) → Post
		$cats = get_the_category( $post_id );
		$bc   = [ [ 'name' => __( 'Home', 'unitwise-converters' ), 'url' => home_url( '/' ) ] ];
		if ( $cats ) {
			$bc[] = [ 'name' => $cats[0]->name, 'url' => (string) get_category_link( $cats[0]->term_id ) ];
		} else {
			$blog_pid = (int) get_option( 'page_for_posts' );
			$bc[] = [
				'name' => $blog_pid ? get_the_title( $blog_pid ) : __( 'Blog', 'unitwise-converters' ),
				'url'  => $blog_pid ? (string) get_permalink( $blog_pid ) : home_url( '/blog/' ),
			];
		}
		$bc[] = [ 'name' => $title, 'url' => $url ];

		$org = [
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		];

		$graph = [
			[
				'@type'      => 'WebPage',
				'@id'        => $url . '#webpage',
				'url'        => $url,
				'name'       => $title,
				'inLanguage' => get_bloginfo( 'language' ),
				'isPartOf'   => [ '@id' => home_url( '/#website' ) ],
			],
			$article,
			self::breadcrumb( $bc ),
			$org,
		];

		self::print( [ '@context' => 'https://schema.org', '@graph' => $graph ] );
	}

	// -------------------------------------------------------------------------
	// Helpers

	private static function breadcrumb( array $items ): array {
		$list = [];
		foreach ( $items as $pos => $item ) {
			$list[] = [
				'@type'    => 'ListItem',
				'position' => $pos + 1,
				'name'     => $item['name'],
				'item'     => [ '@id' => $item['url'] ],
			];
		}
		return [ '@type' => 'BreadcrumbList', 'itemListElement' => $list ];
	}

	private static function get_faqs(
		string $from,
		string $to,
		string $cat,
		Unitwise_Engine $engine
	): array {
		// Use post meta overrides if available
		$post_id = get_the_ID();
		if ( $post_id ) {
			$override = get_post_meta( $post_id, 'unitwise_faq_override', true );
			if ( ! empty( $override ) && is_array( $override ) ) {
				return $override;
			}
		}

		$from_s  = Unitwise_Registry::get_label( $from );
		$to_s    = Unitwise_Registry::get_label( $to );
		$from_p  = Unitwise_Registry::get_label( $from, true );
		$to_p    = Unitwise_Registry::get_label( $to,   true );
		$formula = $engine->get_formula( $from, $to );
		$one_val = $engine->format_result( $engine->convert( 1, $from, $to ) );
		$example = $engine->get_worked_example( 1, $from, $to );

		return [
			[
				'q' => "How many {$to_p} are in 1 {$from_s}?",
				'a' => "1 {$from_s} equals {$one_val} {$to_p}. Formula: {$formula}.",
			],
			[
				'q' => "What is the formula to convert {$from_p} to {$to_p}?",
				'a' => "The formula is: {$formula}. Example: {$example}.",
			],
			[
				'q' => "Is this {$from_s} to {$to_s} converter accurate?",
				'a' => "Yes — it uses internationally standardised conversion factors for full floating-point precision.",
			],
			[
				'q' => "Can I convert {$from_p} to {$to_p} without a calculator?",
				'a' => "Use the formula {$formula}. For a quick check: {$example}.",
			],
		];
	}

	private static function print( array $data ): void {
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG );
		if ( false === $json ) {
			return;
		}
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}
}
