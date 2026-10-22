<?php
/**
 * Generic archive fallback template.
 */

get_header();
?>

<main id="main-content" class="site-main archive-page">
	<div class="container">

		<header class="archive-header">
			<h1 class="archive-title"><?php echo esc_html( get_the_archive_title() ); ?></h1>
			<?php the_archive_description( '<p class="archive-description">', '</p>' ); ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<ul class="post-list" role="list">
				<?php while ( have_posts() ) : the_post(); ?>
					<li class="post-card">
						<article class="post-card__inner">
							<h2 class="post-card__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h2>
							<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<?php if ( has_excerpt() ) : ?>
								<p class="post-card__excerpt"><?php the_excerpt(); ?></p>
							<?php endif; ?>
						</article>
					</li>
				<?php endwhile; ?>
			</ul>
			<nav class="pagination" aria-label="<?php esc_attr_e( 'Archive pagination', 'unitwise-theme' ); ?>">
				<?php the_posts_pagination(); ?>
			</nav>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts found.', 'unitwise-theme' ); ?></p>
		<?php endif; ?>

	</div>
</main>

<?php get_footer(); ?>
