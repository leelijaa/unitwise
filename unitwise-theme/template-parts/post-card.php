<?php
/**
 * Post card partial — used by home.php and the AJAX load-more handler.
 * Expects: current post in the loop, $cats and $cat_name set by the caller.
 */
?>
<li class="post-card">
	<div class="post-card__thumb-wrap">
		<a href="<?php the_permalink(); ?>" class="post-card__thumb" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
			<?php else : ?>
				<span class="post-card__thumb-placeholder" aria-hidden="true"><?php echo esc_html( strtoupper( substr( get_bloginfo( 'name' ), 0, 2 ) ) ); ?></span>
			<?php endif; ?>
		</a>
		<?php if ( ! empty( $cat_name ) && ! empty( $cats ) ) : ?>
			<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>" class="post-card__cat"><?php echo esc_html( $cat_name ); ?></a>
		<?php endif; ?>
	</div>
	<div class="post-card__body">
		<h2 class="post-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>
		<div class="post-card__meta">
			<span class="post-card__author">
				<span class="post-card__avatar" aria-hidden="true"><?php echo esc_html( strtoupper( substr( get_the_author(), 0, 1 ) ) ); ?></span>
				<span><?php echo esc_html( get_the_author() ); ?></span>
			</span>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>
	</div>
</li>
