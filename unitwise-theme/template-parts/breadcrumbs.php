<?php
/**
 * Breadcrumb bar — Theetasoft-style full-width bar with border-bottom.
 *
 * Crumbs are set by the calling template via $GLOBALS['uw_crumbs'] (an array
 * of ['label'=>'', 'url'=>''] items after the implicit Home item).
 * This avoids relying on $wp_query which route_template replaces.
 *
 * Falls back to auto-detection for blog posts and archives.
 *
 * JSON-LD BreadcrumbList included inline.
 */

// Rank Math override
if ( defined( 'RANK_MATH_VERSION' ) && function_exists( 'rank_math_the_breadcrumbs' ) ) {
	echo '<div class="breadcrumb-bar"><nav aria-label="' . esc_attr__( 'Breadcrumb', 'unitwise-theme' ) . '"><div class="container">';
	rank_math_the_breadcrumbs();
	echo '</div></nav></div>';
	return;
}

// Build crumbs: always start with Home
$crumbs   = [];
$crumbs[] = [
	'label' => __( 'Home', 'unitwise-theme' ),
	'url'   => home_url( '/' ),
	'home'  => true,
];

// Use explicitly passed crumbs (set by calling template)
$passed = $GLOBALS['uw_crumbs'] ?? null;
if ( is_array( $passed ) && ! empty( $passed ) ) {
	foreach ( $passed as $c ) {
		$crumbs[] = [
			'label' => $c['label'] ?? '',
			'url'   => $c['url']   ?? '',
		];
	}
} else {
	// Auto-detect for blog posts and archives (these use the normal $wp_query)
	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$crumbs[] = [
				'label' => $cats[0]->name,
				'url'   => get_category_link( $cats[0]->term_id ),
			];
		} else {
			$blog_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' );
			$crumbs[] = [ 'label' => __( 'Blog', 'unitwise-theme' ), 'url' => $blog_url ];
		}
		$crumbs[] = [ 'label' => get_the_title(), 'url' => '' ];
	} elseif ( is_home() || is_category() ) {
		$blog_page_id = (int) get_option( 'page_for_posts' );
		$blog_label   = $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'unitwise-theme' );
		$crumbs[] = [ 'label' => $blog_label, 'url' => '' ];
	} elseif ( is_search() ) {
		$crumbs[] = [ 'label' => sprintf( __( 'Search: %s', 'unitwise-theme' ), get_search_query() ), 'url' => '' ];
	} elseif ( is_404() ) {
		$crumbs[] = [ 'label' => __( 'Page not found', 'unitwise-theme' ), 'url' => '' ];
	}
}

if ( count( $crumbs ) < 2 ) {
	return;
}

// JSON-LD — skip when class-schema.php already outputs the full @graph:
// converter/silo pages (have unitwise_cat query var) and value pages (have _uw_value_page meta).
$unitwise_cat   = get_query_var( 'unitwise_cat' );
$is_value_page  = is_singular( 'page' ) && get_the_ID() && '1' === get_post_meta( get_the_ID(), '_uw_value_page', true );
$has_own_schema = ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'WPSEO_VERSION' )
	&& ( $unitwise_cat || $is_value_page || is_singular( 'post' ) || is_home() );

$ld_items = [];
foreach ( $crumbs as $pos => $crumb ) {
	$ld_item = [
		'@type'    => 'ListItem',
		'position' => $pos + 1,
		'name'     => $crumb['label'],
	];
	if ( ! empty( $crumb['url'] ) ) {
		$ld_item['item'] = [ '@id' => $crumb['url'] ];
	}
	$ld_items[] = $ld_item;
}
$ld = [
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => $ld_items,
];

// Home SVG icon (Lucide Home style)
$home_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
?>
<div class="breadcrumb-bar">
	<nav class="container" aria-label="<?php esc_attr_e( 'Breadcrumb', 'unitwise-theme' ); ?>">
		<ol class="breadcrumb-bar__list">
			<?php foreach ( $crumbs as $pos => $crumb ) :
				$is_last  = ( $pos === count( $crumbs ) - 1 );
				$is_home  = ! empty( $crumb['home'] );
			?>
			<li class="breadcrumb-bar__item">
				<?php if ( $pos > 0 ) : ?>
					<span class="breadcrumb-bar__sep" aria-hidden="true">&#8250;</span>
				<?php endif; ?>

				<?php if ( ! empty( $crumb['url'] ) && ! $is_last ) : ?>
					<a class="breadcrumb-bar__link<?php echo $is_home ? ' breadcrumb-bar__link--home' : ''; ?>"
					   href="<?php echo esc_url( $crumb['url'] ); ?>">
						<?php if ( $is_home ) : ?>
							<?php echo $home_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="screen-reader-text"><?php echo esc_html( $crumb['label'] ); ?></span>
						<?php else : ?>
							<span><?php echo esc_html( $crumb['label'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php else : ?>
					<?php if ( $is_home ) : ?>
						<span class="breadcrumb-bar__link breadcrumb-bar__link--home breadcrumb-bar__current" aria-current="page">
							<?php echo $home_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="screen-reader-text"><?php echo esc_html( $crumb['label'] ); ?></span>
						</span>
					<?php else : ?>
						<span class="breadcrumb-bar__current" aria-current="page">
							<?php echo esc_html( $crumb['label'] ); ?>
						</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ol>
	</nav>
</div>
<?php if ( ! $has_own_schema ) : ?>
<script type="application/ld+json"><?php echo wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
<?php endif; ?>
<?php
// Clean up the global after rendering
unset( $GLOBALS['uw_crumbs'] );
