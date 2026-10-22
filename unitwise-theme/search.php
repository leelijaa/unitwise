<?php
/**
 * Search results template.
 */

get_header();
?>

<main id="main-content" class="site-main search-page">
	<div class="container">

		<header class="archive-header">
			<h1 class="archive-title">
				<?php
				printf(
					/* translators: %s: search query wrapped in a span */
					__( 'Search results for: %s', 'unitwise-theme' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<ul class="post-list" role="list">
				<?php while ( have_posts() ) : the_post(); ?>
					<li class="post-card">
						<article class="post-card__inner">
							<h2 class="post-card__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h2>
							<?php if ( has_excerpt() ) : ?>
								<p class="post-card__excerpt"><?php the_excerpt(); ?></p>
							<?php endif; ?>
						</article>
					</li>
				<?php endwhile; ?>
			</ul>
			<nav class="pagination" aria-label="<?php esc_attr_e( 'Search results pagination', 'unitwise-theme' ); ?>">
				<?php the_posts_pagination(); ?>
			</nav>
		<?php else : ?>
			<p><?php esc_html_e( 'No results found. Try a different search term.', 'unitwise-theme' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>

	</div>
</main>

<?php get_footer(); ?>
