<?php
/**
 * Blog post index — professional card grid layout.
 */

get_header();

global $wp_query;
?>

<main id="main-content" class="site-main blog-index">
	<div class="container">

		<header class="blog-index__header">
			<h1 class="blog-index__title"><?php esc_html_e( 'Blog', 'unitwise-theme' ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>

		<ul class="post-grid" role="list" data-max-pages="<?php echo esc_attr( $wp_query->max_num_pages ); ?>">
			<?php while ( have_posts() ) : the_post();
				$cats     = get_the_category();
				$cat_name = $cats ? esc_html( $cats[0]->name ) : '';
				get_template_part( 'template-parts/post-card' );
			endwhile; ?>
		</ul>

		<?php if ( $wp_query->max_num_pages > 1 ) : ?>
		<div class="load-more-wrap">
			<button class="btn btn--load-more" data-page="1">
				<?php esc_html_e( 'Load More', 'unitwise-theme' ); ?>
			</button>
		</div>
		<?php endif; ?>

		<?php else : ?>

		<p class="no-posts"><?php esc_html_e( 'No posts found.', 'unitwise-theme' ); ?></p>

		<?php endif; ?>

	</div><!-- .container -->
</main><!-- .blog-index -->

<?php get_footer(); ?>
