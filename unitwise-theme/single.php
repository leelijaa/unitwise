<?php
/**
 * Blog post single — full-width professional layout.
 */

get_header();
the_post();

$cats     = get_the_category();
$cat_name = $cats ? esc_html( $cats[0]->name ) : '';
$words    = str_word_count( wp_strip_all_tags( get_the_content() ) );
$read_min = max( 1, (int) ceil( $words / 200 ) );
?>

<main id="main-content" class="site-main single-post-page">
<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>

	<?php
	if ( $cats ) {
		$GLOBALS['uw_crumbs'] = [
			[
				'label' => $cats[0]->name,
				'url'   => get_category_link( $cats[0]->term_id ),
			],
			[
				'label' => get_the_title(),
				'url'   => '',
			],
		];
	} else {
		$blog_url             = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' );
		$GLOBALS['uw_crumbs'] = [
			[ 'label' => __( 'Blog', 'unitwise-theme' ), 'url' => $blog_url ],
			[ 'label' => get_the_title(), 'url' => '' ],
		];
	}
	get_template_part( 'template-parts/breadcrumbs' );
	?>

	<div class="post-hero">
		<div class="container container--narrow">

			<?php if ( $cat_name ) : ?>
				<div class="post-hero__eyebrow">
					<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cat_name ); ?></a>
				</div>
			<?php endif; ?>

			<h1 class="post-hero__title"><?php echo esc_html( get_the_title() ); ?></h1>

			<div class="post-hero__meta">
				<span><?php echo esc_html( get_the_author() ); ?></span>
				<span class="post-hero__meta-sep" aria-hidden="true">·</span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
				<span class="post-hero__meta-sep" aria-hidden="true">·</span>
				<span><?php printf( esc_html__( '%d min read', 'unitwise-theme' ), $read_min ); ?></span>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-hero__thumb">
				<?php the_post_thumbnail( 'large', [ 'loading' => 'eager', 'fetchpriority' => 'high' ] ); ?>
			</div>
			<?php endif; ?>

		</div>
	</div><!-- .post-hero -->

	<div class="post-content-wrap">
		<div class="container container--narrow">
			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<nav class="post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'unitwise-theme' ); ?>">
				<div class="nav-links">
					<?php
					$prev = get_previous_post();
					$next = get_next_post();
					if ( $prev ) :
					?>
					<a href="<?php echo esc_url( get_permalink( $prev ) ); ?>" class="nav-prev" rel="prev">
						<span class="nav-label"><?php esc_html_e( '← Previous', 'unitwise-theme' ); ?></span>
						<?php echo esc_html( get_the_title( $prev ) ); ?>
					</a>
					<?php endif; ?>
					<?php if ( $next ) : ?>
					<a href="<?php echo esc_url( get_permalink( $next ) ); ?>" class="nav-next" rel="next">
						<span class="nav-label"><?php esc_html_e( 'Next →', 'unitwise-theme' ); ?></span>
						<?php echo esc_html( get_the_title( $next ) ); ?>
					</a>
					<?php endif; ?>
				</div>
			</nav>
		</div>
	</div>

</article>
</main>

<?php get_footer(); ?>
