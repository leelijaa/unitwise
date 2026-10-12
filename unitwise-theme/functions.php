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

	$cat  = get_query_var( 'unitwise_cat' );
	$from = get_query_var( 'unitwise_from' );
	$to   = get_query_var( 'unitwise_to' );

	// Noindex for non-content pages
	if ( is_404() || is_search() ) {
		echo "<meta name=\"robots\" content=\"noindex, nofollow\">\n";
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

	} elseif ( is_singular() ) {
		$og_type = 'article';
		$title   = get_the_title() . ' | ' . get_bloginfo( 'name' );
		$desc    = has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : wp_trim_words( get_the_content(), 20 );
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

	$title = esc_html( wp_strip_all_tags( $title ) );
	$desc  = esc_attr( wp_strip_all_tags( $desc ) );
	$url   = esc_url( $url );
	$site  = esc_attr( get_bloginfo( 'name' ) );

	// og:image — featured image for singular, fallback to theme default
	$og_image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$og_image = get_the_post_thumbnail_url( null, 'large' );
	}
	if ( ! $og_image ) {
		$og_image = get_theme_mod( 'unitwise_og_image', get_template_directory_uri() . '/assets/images/og-default.jpg' );
	}

	echo "<meta name=\"description\" content=\"{$desc}\">\n";
	echo "<link rel=\"canonical\" href=\"{$url}\">\n";
	echo "<meta property=\"og:title\" content=\"{$title}\">\n";
	echo "<meta property=\"og:description\" content=\"{$desc}\">\n";
	echo "<meta property=\"og:url\" content=\"{$url}\">\n";
	echo "<meta property=\"og:type\" content=\"" . esc_attr( $og_type ) . "\">\n";
	echo "<meta property=\"og:site_name\" content=\"{$site}\">\n";
	if ( $og_image ) {
		echo "<meta property=\"og:image\" content=\"" . esc_url( $og_image ) . "\">\n";
	}
	echo "<meta name=\"twitter:card\" content=\"summary" . ( $og_image ? '_large_image' : '' ) . "\">\n";
	echo "<meta name=\"twitter:title\" content=\"{$title}\">\n";
	echo "<meta name=\"twitter:description\" content=\"{$desc}\">\n";
	if ( $og_image ) {
		echo "<meta name=\"twitter:image\" content=\"" . esc_url( $og_image ) . "\">\n";
	}
}

// -------------------------------------------------------------------------
// Template helpers

/**
 * Generate the page <title> for <head> (used if no SEO plugin is active).
 * Hooked before wp_head so the title-tag feature picks it up.
 */
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
		include get_template_directory() . '/template-parts/post-card.php';
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
		wp_redirect( home_url( '/' ), 301 );
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
}

// -------------------------------------------------------------------------
// robots.txt — add sitemap directive

add_filter( 'robots_txt', 'unitwise_theme_robots_txt', 10, 2 );

function unitwise_theme_robots_txt( string $output, bool $public ): string {
	if ( $public ) {
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
	}

	return $title;
}
