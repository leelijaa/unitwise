<?php
/**
 * Site footer — CCTV-style multi-column dark layout.
 *
 * Upper row:  Logo+tagline | Social icons + email | Popular Converters | Learn
 * Lower nav:  Auto-columns per unit category | Blog | Site
 * Legal bar:  Copyright + legal nav
 *
 * Social URLs: Appearance → Customize → Footer Social.
 *
 * @package unitwise-theme
 */

defined( 'ABSPATH' ) || exit;

/* ---- Social links --------------------------------------------------------- */
$social_links = [
	'twitter'  => [
		'url'   => get_theme_mod( 'footer_social_twitter', '' ),
		'label' => 'Twitter / X',
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 18" fill="none" aria-hidden="true" focusable="false"><path d="M15.75 0h3.07L11.6 7.62 20 18h-6.17l-4.84-6.29L3.46 18H.39l7.17-8.16L0 0h6.33l4.37 5.75L15.75 0ZM14.67 16.17H16.37L5.4 1.73H3.58L14.67 16.17Z" fill="currentColor"/></svg>',
	],
	'youtube'  => [
		'url'   => get_theme_mod( 'footer_social_youtube', '' ),
		'label' => 'YouTube',
		'icon'  => '<svg width="20" height="15" viewBox="0 0 24 17" fill="none" aria-hidden="true" focusable="false"><path d="M23.5 2.66A3.01 3.01 0 0 0 21.39.54C19.52 0 12 0 12 0S4.48 0 2.61.54A3.01 3.01 0 0 0 .5 2.66C0 4.54 0 8.5 0 8.5s0 3.96.5 5.84a3.01 3.01 0 0 0 2.11 2.12C4.48 17 12 17 12 17s7.52 0 9.39-.54a3.01 3.01 0 0 0 2.11-2.12C24 12.46 24 8.5 24 8.5s0-3.96-.5-5.84ZM9.55 12.14V4.86L15.82 8.5l-6.27 3.64Z" fill="currentColor"/></svg>',
	],
	'linkedin' => [
		'url'   => get_theme_mod( 'footer_social_linkedin', '' ),
		'label' => 'LinkedIn',
		'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.8 0 0 .78 0 1.73v20.54C0 23.22.8 24 1.77 24h20.45C23.2 24 24 23.22 24 22.27V1.73C24 .78 23.2 0 22.22 0Z" fill="currentColor"/></svg>',
	],
	'rss'      => [
		'url'   => get_bloginfo( 'rss2_url' ),
		'label' => 'RSS Feed',
		'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M6.18 15.64a2.18 2.18 0 0 1 0 4.36 2.18 2.18 0 0 1 0-4.36ZM4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44Zm0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93V10.1Z" fill="currentColor"/></svg>',
	],
];

$has_social    = ! empty( array_filter( array_column( $social_links, 'url' ) ) );
$contact_email = get_theme_mod( 'footer_contact_email', '' );

/* ---- Auto-generate converter columns from unitwise_category --------------- */
$footer_cat_cols = [];
if ( class_exists( 'Unitwise_Registry' ) ) {
	$footer_cat_cols = get_transient( 'uw_footer_cat_cols' );
	if ( false === $footer_cat_cols ) {
		$footer_cat_cols = [];
		$terms = get_terms( [
			'taxonomy'   => 'unitwise_category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$converters = get_posts( [
					'post_type'      => 'unitwise_converter',
					'posts_per_page' => 8,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'tax_query'      => [[
						'taxonomy' => 'unitwise_category',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					]],
				] );
				if ( ! empty( $converters ) ) {
					$footer_cat_cols[] = [
						'name'       => $term->name,
						'link'       => home_url( '/' . $term->slug . '/' ),
						'converters' => array_map(
							fn( $p ) => [ 'title' => get_the_title( $p ), 'url' => get_permalink( $p ) ],
							$converters
						),
					];
				}
			}
		}
		set_transient( 'uw_footer_cat_cols', $footer_cat_cols, DAY_IN_SECONDS );
	}
}
?>

<footer class="site-footer" role="contentinfo">
	<div class="container">

		<!-- ================================================================ -->
		<!-- UPPER ROW: Logo · Social · Converters · Learn                    -->
		<!-- ================================================================ -->
		<div class="site-footer__upper">

			<!-- Logo + tagline -->
			<div class="site-footer__upper-col site-footer__upper-col--logo">
				<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="site-footer__logo-icon" aria-hidden="true">UW</span>
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>
				<p class="site-footer__tagline"><?php esc_html_e( 'Free, instant unit conversions. No sign-up. No ads.', 'unitwise-theme' ); ?></p>
			</div>

			<!-- Social -->
			<div class="site-footer__upper-col">
				<h3 class="site-footer__upper-heading"><?php esc_html_e( 'Follow us on', 'unitwise-theme' ); ?></h3>
				<?php if ( $has_social ) : ?>
				<ul class="site-footer__social-list" aria-label="<?php esc_attr_e( 'Social links', 'unitwise-theme' ); ?>">
					<?php foreach ( $social_links as $data ) : ?>
						<?php if ( ! $data['url'] ) { continue; } ?>
						<li>
							<a href="<?php echo esc_url( $data['url'] ); ?>" class="site-footer__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $data['label'] ); ?>">
								<?php echo $data['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVG ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php else : ?>
				<p class="site-footer__contact-line"><?php esc_html_e( 'Add social links in Customizer.', 'unitwise-theme' ); ?></p>
				<?php endif; ?>
				<?php if ( $contact_email ) : ?>
				<a class="site-footer__email-cta" href="<?php echo esc_url( 'mailto:' . antispambot( $contact_email ) ); ?>">
					<?php esc_html_e( 'EMAIL US', 'unitwise-theme' ); ?>
				</a>
				<?php endif; ?>
			</div>

			<!-- Popular Converters -->
			<div class="site-footer__upper-col">
				<h3 class="site-footer__upper-heading"><?php esc_html_e( 'Popular Converters', 'unitwise-theme' ); ?></h3>
				<ul class="site-footer__upper-links">
					<li><a href="<?php echo esc_url( home_url( '/weight/grams-to-kilograms/' ) ); ?>"><?php esc_html_e( 'Grams to Kilograms', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/pounds-to-kilograms/' ) ); ?>"><?php esc_html_e( 'Pounds to Kilograms', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/ounces-to-grams/' ) ); ?>"><?php esc_html_e( 'Ounces to Grams', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/kilograms-to-pounds/' ) ); ?>"><?php esc_html_e( 'Kilograms to Pounds', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/kilograms-to-grams/' ) ); ?>"><?php esc_html_e( 'Kilograms to Grams', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/grams-to-ounces/' ) ); ?>"><?php esc_html_e( 'Grams to Ounces', 'unitwise-theme' ); ?></a></li>
				</ul>
			</div>

			<!-- Learn + Site -->
			<div class="site-footer__upper-col">
				<h3 class="site-footer__upper-heading"><?php esc_html_e( 'Site', 'unitwise-theme' ); ?></h3>
				<ul class="site-footer__upper-links">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/weight/' ) ); ?>"><?php esc_html_e( 'Weight Converters', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'unitwise-theme' ); ?></a></li>
				</ul>
			</div>

		</div><!-- /.site-footer__upper -->

		<hr class="site-footer__divider">

		<!-- ================================================================ -->
		<!-- LOWER NAV: One column per unit category + Blog + Site           -->
		<!-- ================================================================ -->
		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer navigation', 'unitwise-theme' ); ?>">

			<?php foreach ( $footer_cat_cols as $col ) : ?>
			<div class="site-footer__col">
				<h3 class="site-footer__col-heading">
					<a href="<?php echo esc_url( $col['link'] ); ?>"><?php echo esc_html( $col['name'] ); ?></a>
				</h3>
				<ul class="site-footer__col-links">
					<?php foreach ( $col['converters'] as $c ) : ?>
					<li><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endforeach; ?>

			<!-- Blog column -->
			<div class="site-footer__col">
				<h3 class="site-footer__col-heading">
					<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'unitwise-theme' ); ?></a>
				</h3>
				<?php
				$recent = get_transient( 'uw_footer_recent_posts' );
				if ( false === $recent ) {
					$recent = get_posts( [
						'post_type'      => 'post',
						'posts_per_page' => 5,
						'post_status'    => 'publish',
						'no_found_rows'  => true,
					] );
					set_transient( 'uw_footer_recent_posts', $recent, HOUR_IN_SECONDS );
				}
				if ( $recent ) :
				?>
				<ul class="site-footer__col-links">
					<?php foreach ( $recent as $p ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'All articles →', 'unitwise-theme' ); ?></a></li>
				</ul>
				<?php endif; ?>
			</div>

			<!-- About / Site col -->
			<div class="site-footer__col">
				<h3 class="site-footer__col-heading"><?php esc_html_e( 'About', 'unitwise-theme' ); ?></h3>
				<ul class="site-footer__col-links">
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About us', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'unitwise-theme' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>"><?php esc_html_e( 'Sitemap', 'unitwise-theme' ); ?></a></li>
				</ul>
			</div>

		</nav><!-- /.site-footer__nav -->

		<!-- ================================================================ -->
		<!-- LEGAL BAR                                                        -->
		<!-- ================================================================ -->
		<div class="site-footer__legal">
			<?php if ( has_nav_menu( 'legal' ) ) :
				wp_nav_menu( [
					'theme_location'       => 'legal',
					'container'            => 'nav',
					'container_aria_label' => esc_attr__( 'Legal', 'unitwise-theme' ),
					'depth'                => 1,
				] );
			endif; ?>
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>. <?php esc_html_e( 'All conversions are for informational purposes only.', 'unitwise-theme' ); ?></p>
		</div>

	</div><!-- /.container -->
</footer>

<?php wp_footer(); ?>
</body>
</html>
