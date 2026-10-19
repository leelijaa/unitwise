<?php
/**
 * UnitWise Theme — functions.php
 *
 * PRESENTATION LAYER ONLY.
 * No CPT, taxonomy, rewrite, schema, or conversion logic lives here.
 * Deactivating this theme leaves all converter posts and URLs intact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/fallback-nav.php';

// -------------------------------------------------------------------------
// Theme setup

add_action( 'after_setup_theme', 'unitwise_theme_setup' );

function unitwise_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'unitwise-card', 400, 280, true );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	] );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );

	register_nav_menus( [
		'primary' => __( 'Primary Navigation', 'unitwise-theme' ),
		'footer'  => __( 'Footer Navigation',  'unitwise-theme' ),
		'legal'   => __( 'Legal (footer bar)', 'unitwise-theme' ),
	] );

	load_theme_textdomain( 'unitwise-theme', get_template_directory() . '/languages' );
}

// -------------------------------------------------------------------------
// Google Fonts preconnect hints (reduces font load latency)

add_action( 'wp_head', 'unitwise_theme_preconnect', 1 );

function unitwise_theme_preconnect(): void {
	// Only emit preconnect hints when the font stylesheet is actually enqueued
	if ( ! wp_style_is( 'unitwise-fonts', 'enqueued' ) ) {
		return;
	}
	echo "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
	echo "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
}

// -------------------------------------------------------------------------
// Async Google Fonts — prevent render-blocking

add_filter( 'style_loader_tag', 'unitwise_theme_async_fonts', 10, 2 );

function unitwise_theme_async_fonts( string $tag, string $handle ): string {
	if ( 'unitwise-fonts' !== $handle ) {
		return $tag;
	}
	$async = str_replace( "rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
	return $async . "<noscript>{$tag}</noscript>\n";
}

// -------------------------------------------------------------------------
// Crawl budget — noindex / redirect thin archive pages

add_action( 'wp_head',          'unitwise_theme_thin_archive_noindex', 1 );
add_action( 'template_redirect', 'unitwise_theme_redirect_attachments' );

function unitwise_theme_thin_archive_noindex(): void {
	if ( is_date() || is_tag() || ( is_category() && ! is_tax( 'unitwise_category' ) )
		|| ( is_paged() && ( is_home() || is_archive() ) )
	) {
		echo "<meta name=\"robots\" content=\"noindex, follow\">\n";
	}
}

function unitwise_theme_redirect_attachments(): void {
	if ( is_attachment() ) {
		$parent = wp_get_post_parent_id( get_the_ID() );
		$target = ( $parent && 'publish' === get_post_status( $parent ) )
			? get_permalink( $parent )
			: home_url( '/' );
		wp_safe_redirect( esc_url_raw( $target ), 301 );
		exit;
	}
}

// -------------------------------------------------------------------------
// Content width

if ( ! isset( $content_width ) ) {
	$content_width = 720;
}

// -------------------------------------------------------------------------
// Enqueue

add_action( 'wp_enqueue_scripts', 'unitwise_theme_enqueue' );

function unitwise_theme_enqueue(): void {
	// Google Fonts — Inter
	wp_enqueue_style(
		'unitwise-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'unitwise-theme',
		get_template_directory_uri() . '/assets/css/main.css',
		[ 'unitwise-fonts' ],
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'unitwise-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		[],
		wp_get_theme()->get( 'Version' ),
		[ 'strategy' => 'defer', 'in_footer' => true ]
	);

	if ( is_home() ) {
		wp_localize_script( 'unitwise-theme', 'uwBlog', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'uw_load_more' ),
		] );
	}
}

// -------------------------------------------------------------------------
// Sidebars

add_action( 'widgets_init', 'unitwise_theme_widgets' );

function unitwise_theme_widgets(): void {
	register_sidebar( [
		'name'          => __( 'Blog Sidebar', 'unitwise-theme' ),
		'id'            => 'blog-sidebar',
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	] );
}

// -------------------------------------------------------------------------
// SEO helpers — used by header.php
// The plugin outputs JSON-LD; the theme handles <title>, <meta>, OG tags.

add_action( 'wp_head', 'unitwise_theme_seo_tags', 1 );

function unitwise_theme_seo_tags(): void {
	// Skip if Rank Math or Yoast is active
	if (
		defined( 'RANK_MATH_VERSION' ) ||
		defined( 'WPSEO_VERSION' )
	) {
		return;
	}

	// Value pages: SEO handled by Unitwise_Value_Pages::output_seo() in the plugin
	if ( is_singular( 'page' ) ) {
		$vp_id = get_the_ID();
		if ( $vp_id && '1' === get_post_meta( $vp_id, '_uw_value_page', true ) ) {
			return;
		}
	}

	$cat  = get_query_var( 'unitwise_cat' );
	$from = get_query_var( 'unitwise_from' );
	$to   = get_query_var( 'unitwise_to' );

	// Noindex for non-content pages
	if ( is_404() ) {
		echo "<meta name=\"robots\" content=\"noindex, nofollow\">\n";
		return;
	}
	if ( is_search() ) {
		echo "<meta name=\"robots\" content=\"noindex, follow\">\n";
		return;
	}

	// Build title and description
	$og_type = 'website';

	if ( $cat && $from && $to && class_exists( 'Unitwise_Registry' ) ) {
		$from_p   = Unitwise_Registry::get_label( $from, true );
		$to_p     = Unitwise_Registry::get_label( $to,   true );
		$title    = "{$from_p} to {$to_p} Converter | " . get_bloginfo( 'name' );
		$desc     = "Free online {$from_p} to {$to_p} converter. Instant results, formula, worked example, and conversion table.";
		$url      = home_url( "/{$cat}/{$from}-to-{$to}/" );

	} elseif ( $cat ) {
		$cat_info = class_exists( 'Unitwise_Registry' ) ? Unitwise_Registry::get_category( $cat ) : [];
		$cat_name = $cat_info['name'] ?? ucfirst( $cat );
		$title    = "{$cat_name} Converters | " . get_bloginfo( 'name' );
		$desc     = "Free online {$cat_name} unit converters. Convert between all common units instantly.";
		$url      = home_url( "/{$cat}/" );

	} elseif ( is_singular( 'post' ) ) {
		$og_type = 'article';
		$title   = get_the_title() . ' | ' . get_bloginfo( 'name' );
	} elseif ( is_singular() ) {
		$title   = get_the_title() . ' | ' . get_bloginfo( 'name' );
		$desc    = has_excerpt()
			? wp_strip_all_tags( get_the_excerpt() )
			: wp_trim_words( wp_strip_all_tags( apply_filters( 'the_content', get_the_content() ) ), 20 );
		$url     = get_permalink();

	} elseif ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$title          = ( $page_for_posts ? get_the_title( $page_for_posts ) . ' | ' : 'Blog | ' ) . get_bloginfo( 'name' );
		$desc           = 'Unit conversion tips, guides, and resources from ' . get_bloginfo( 'name' ) . '.';
		$paged          = (int) get_query_var( 'paged', 1 );
		$url            = $paged > 1 ? get_pagenum_link( $paged ) : ( $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/blog/' ) );

	} elseif ( is_front_page() ) {
		$title = get_bloginfo( 'name' ) . ' — Free Online Unit Converters';
		$desc  = 'Fast, accurate, free unit converters. Convert weight, length, temperature, volume and more.';
		$url   = home_url( '/' );

	} else {
		return;
	}

	$title = esc_attr( wp_strip_all_tags( $title ) );
	$desc  = esc_attr( wp_strip_all_tags( $desc ) );
	$url   = esc_url( $url );
	$site  = esc_attr( get_bloginfo( 'name' ) );

	// og:image — featured image for singular, fallback to theme default
	$og_image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$og_image = get_the_post_thumbnail_url( null, 'large' );
	}
	if ( ! $og_image ) {
		$fallback = get_template_directory() . '/assets/images/og-default.jpg';
		$og_image = get_theme_mod(
			'unitwise_og_image',
			file_exists( $fallback ) ? get_template_directory_uri() . '/assets/images/og-default.jpg' : ''
		);
	}

	echo "<meta name=\"description\" content=\"{$desc}\">\n";
	echo "<link rel=\"canonical\" href=\"{$url}\">\n";
	echo "<meta property=\"og:title\" content=\"{$title}\">\n";
	echo "<meta property=\"og:description\" content=\"{$desc}\">\n";
	echo "<meta property=\"og:url\" content=\"{$url}\">\n";
	echo "<meta property=\"og:type\" content=\"" . esc_attr( $og_type ) . "\">\n";
	echo "<meta property=\"og:site_name\" content=\"{$site}\">\n";
	echo "<meta property=\"og:locale\" content=\"" . esc_attr( str_replace( '-', '_', get_locale() ) ) . "\">\n";
	$og_alt = '';
	if ( $og_image ) {
		echo "<meta property=\"og:image\" content=\"" . esc_url( $og_image ) . "\">\n";
		echo "<meta property=\"og:image:width\" content=\"1200\">\n";
		echo "<meta property=\"og:image:height\" content=\"630\">\n";
		$og_alt = esc_attr( wp_strip_all_tags( get_bloginfo( 'name' ) ) );
		echo "<meta property=\"og:image:alt\" content=\"{$og_alt}\">\n";
	}
	if ( 'article' === $og_type ) {
		echo "<meta property=\"article:published_time\" content=\"" . esc_attr( get_the_date( 'c' ) ) . "\">\n";
		echo "<meta property=\"article:modified_time\" content=\"" . esc_attr( get_the_modified_date( 'c' ) ) . "\">\n";
		$author_name = get_the_author_meta( 'display_name' );
		if ( $author_name ) {
			echo "<meta property=\"article:author\" content=\"" . esc_attr( $author_name ) . "\">\n";
		}
	}
	$twitter_url = get_theme_mod( 'footer_social_twitter', '' );
	$tw_card     = 'summary' . ( $og_image ? '_large_image' : '' );
	echo "<meta name=\"twitter:card\" content=\"" . esc_attr( $tw_card ) . "\">\n";
	if ( $twitter_url ) {
		preg_match( '#(?:twitter|x)\.com/([^/?\s]+)#i', $twitter_url, $m );
		$tw_site = isset( $m[1] ) ? '@' . $m[1] : $twitter_url;
		echo "<meta name=\"twitter:site\" content=\"" . esc_attr( $tw_site ) . "\">\n";
	}
	echo "<meta name=\"twitter:title\" content=\"{$title}\">\n";
	echo "<meta name=\"twitter:description\" content=\"{$desc}\">\n";
	if ( $og_image ) {
		echo "<meta name=\"twitter:image\" content=\"" . esc_url( $og_image ) . "\">\n";
		echo "<meta name=\"twitter:image:alt\" content=\"{$og_alt}\">\n";
	}
}

// -------------------------------------------------------------------------
// AJAX — Load More blog posts

add_action( 'wp_ajax_nopriv_uw_load_more_posts', 'unitwise_load_more_posts' );
add_action( 'wp_ajax_uw_load_more_posts',        'unitwise_load_more_posts' );

function unitwise_load_more_posts(): void {
	check_ajax_referer( 'uw_load_more', 'nonce' );

	$page  = max( 1, (int) ( $_POST['page'] ?? 1 ) );
	$query = new WP_Query( [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => (int) get_option( 'posts_per_page', 6 ),
		'paged'          => $page,
	] );

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		$cats     = get_the_category();
		$cat_name = $cats ? esc_html( $cats[0]->name ) : '';
		get_template_part( 'template-parts/post-card', null, [ 'cats' => $cats, 'cat_name' => $cat_name ] );
	}
	wp_reset_postdata();
	$html = ob_get_clean();

	wp_send_json_success( [
		'html'    => $html,
		'hasMore' => $page < $query->max_num_pages,
	] );
}

// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Disable author archives — redirect to homepage (crawl budget)

add_action( 'template_redirect', 'unitwise_theme_disable_author_archives' );

function unitwise_theme_disable_author_archives(): void {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}

// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Customizer — default OG image setting

add_action( 'customize_register', 'unitwise_theme_customizer' );

function unitwise_theme_customizer( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_setting( 'unitwise_og_image', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'unitwise_og_image', [
		'label'   => __( 'Default OG Image (1200×630 px)', 'unitwise-theme' ),
		'section' => 'title_tagline',
	] ) );

	$wp_customize->add_setting( 'unitwise_logo_url', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw' ] );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'unitwise_logo_url', [
		'label'   => __( 'Site Logo (for JSON-LD Organization schema)', 'unitwise-theme' ),
		'section' => 'title_tagline',
	] ) );

	// Social links section
	$wp_customize->add_section( 'unitwise_social', [
		'title'    => __( 'Social Links', 'unitwise-theme' ),
		'priority' => 120,
	] );

	$social_fields = [
		'footer_social_twitter'  => __( 'Twitter / X URL', 'unitwise-theme' ),
		'footer_social_youtube'  => __( 'YouTube URL', 'unitwise-theme' ),
		'footer_social_linkedin' => __( 'LinkedIn URL', 'unitwise-theme' ),
		'footer_contact_email'   => __( 'Contact Email', 'unitwise-theme' ),
	];

	foreach ( $social_fields as $key => $label ) {
		$sanitize = ( 'footer_contact_email' === $key ) ? 'sanitize_email' : 'esc_url_raw';
		$wp_customize->add_setting( $key, [ 'default' => '', 'sanitize_callback' => $sanitize ] );
		$wp_customize->add_control( $key, [
			'label'   => $label,
			'section' => 'unitwise_social',
			'type'    => 'url',
		] );
	}
}

// -------------------------------------------------------------------------
// Bust footer converter column cache when converters are saved/deleted

add_action( 'save_post_unitwise_converter',   'unitwise_theme_bust_footer_cache' );
add_action( 'delete_post_unitwise_converter', 'unitwise_theme_bust_footer_cache' );
add_action( 'save_post',   'unitwise_theme_bust_footer_recent' );
add_action( 'delete_post', 'unitwise_theme_bust_footer_recent' );

function unitwise_theme_bust_footer_cache(): void {
	delete_transient( 'uw_footer_cat_cols' );
}

function unitwise_theme_bust_footer_recent(): void {
	delete_transient( 'uw_footer_recent_posts' );
}

// -------------------------------------------------------------------------
// robots.txt — add sitemap directive

add_filter( 'robots_txt', 'unitwise_theme_robots_txt', 10, 2 );

function unitwise_theme_robots_txt( string $output, bool $public ): string {
	if ( $public ) {
		$output .= "\nDisallow: /?s=\n";
		$output .= "Disallow: /?p=\n";
		$output .= "Disallow: /?v=\n";
		$output .= "Disallow: /?post_type=unitwise_converter\n";
		$output .= "Disallow: /?author=\n";
		$output .= "Disallow: /feed/\n";
		$output .= "Disallow: /*/feed/\n";
		$output .= "Disallow: /?unitwise_cat=\n";
		$output .= "Disallow: /?unitwise_from=\n";
		$output .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	}
	return $output;
}

// -------------------------------------------------------------------------

add_filter( 'document_title_parts', 'unitwise_theme_document_title' );

function unitwise_theme_document_title( array $title ): array {
	$cat  = get_query_var( 'unitwise_cat' );
	$from = get_query_var( 'unitwise_from' );
	$to   = get_query_var( 'unitwise_to' );

	if ( $cat && $from && $to && class_exists( 'Unitwise_Registry' ) ) {
		$title['title'] = Unitwise_Registry::get_label( $from, true )
			. ' to '
			. Unitwise_Registry::get_label( $to, true )
			. ' Converter';
	} elseif ( $cat && class_exists( 'Unitwise_Registry' ) ) {
		$cat_info       = Unitwise_Registry::get_category( $cat );
		$title['title'] = ( $cat_info['name'] ?? ucfirst( $cat ) ) . ' Converters';
	} elseif ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$title['title'] = $page_for_posts ? get_the_title( $page_for_posts ) : __( 'Blog', 'unitwise-theme' );
	} elseif ( is_front_page() ) {
		$title['title'] = get_bloginfo( 'name' ) . ' — Free Online Unit Converters';
		unset( $title['tagline'] );
	}

	return $title;
}
