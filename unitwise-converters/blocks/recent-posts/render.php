<?php
/**
 * Render: unitwise/recent-posts — recent blog post grid.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$heading    = esc_html( $attributes['heading']    ?? 'From the Blog' );
$subheading = esc_html( $attributes['subheading'] ?? '' );
$count      = max( 1, (int) ( $attributes['count'] ?? 3 ) );

$recent_posts = new WP_Query( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $count,
	'no_found_rows'  => true,
] );

if ( ! $recent_posts->have_posts() ) {
	return;
}

$blog_pid  = (int) get_option( 'page_for_posts' );
$blog_href = $blog_pid ? get_permalink( $blog_pid ) : home_url( '/blog/' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'hub-section' ] ); ?> aria-label="<?php esc_attr_e( 'Latest articles', 'unitwise-converters' ); ?>">
	<div class="hub-section__header">
		<h2 class="hub-section__title"><?php echo $heading; ?></h2>
		<?php if ( $subheading ) : ?>
			<p class="hub-section__subtitle"><?php echo $subheading; ?></p>
		<?php endif; ?>
	</div>

	<ul class="post-grid" role="list">
		<?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post();
			$cats     = get_the_category();
			$cat_name = $cats ? esc_html( $cats[0]->name ) : '';
			$words    = str_word_count( wp_strip_all_tags( get_the_content() ) );
			$read_min = max( 1, (int) ceil( $words / 200 ) );
		?>
		<li class="post-card">
			<div class="post-card__thumb-wrap">
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="post-card__thumb" tabindex="-1" aria-hidden="true">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'unitwise-card', [ 'loading' => 'lazy' ] ); ?>
					<?php else : ?>
						<span class="post-card__thumb-placeholder" aria-hidden="true">
							<?php echo esc_html( strtoupper( substr( get_bloginfo( 'name' ), 0, 2 ) ) ); ?>
						</span>
					<?php endif; ?>
				</a>
				<?php if ( $cat_name && $cats ) : ?>
					<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>" class="post-card__cat">
						<?php echo esc_html( $cat_name ); ?>
					</a>
				<?php endif; ?>
			</div>
			<div class="post-card__body">
				<h3 class="post-card__title">
					<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
				</h3>
				<div class="post-card__meta">
					<span class="post-card__author">
						<span class="post-card__avatar" aria-hidden="true">
							<?php echo esc_html( strtoupper( substr( get_the_author(), 0, 1 ) ) ); ?>
						</span>
						<span><?php echo esc_html( get_the_author() ); ?></span>
					</span>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date() ); ?>
					</time>
				</div>
			</div>
		</li>
		<?php endwhile; wp_reset_postdata(); ?>
	</ul>

	<div class="hub-section__cta">
		<a href="<?php echo esc_url( $blog_href ); ?>" class="btn btn--primary">
			<?php esc_html_e( 'View All Posts', 'unitwise-converters' ); ?>
		</a>
	</div>
</section>
