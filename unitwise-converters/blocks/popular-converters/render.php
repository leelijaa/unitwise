<?php
/**
 * Server-side render for unitwise/popular-converters.
 *
 * Queries the CPT for published converters in the given category and
 * renders a linked grid usable on hub and silo landing pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category = isset( $attributes['category'] ) ? sanitize_key( $attributes['category'] ) : 'weight';
$limit    = isset( $attributes['limit'] )    ? absint( $attributes['limit'] )           : 12;
$title    = isset( $attributes['title'] )    ? sanitize_text_field( $attributes['title'] ) : '';

$query = new WP_Query( [
	'post_type'           => 'unitwise_converter',
	'post_status'         => 'publish',
	'posts_per_page'      => $limit,
	'orderby'             => 'title',
	'order'               => 'ASC',
	'no_found_rows'       => true,
	'update_post_term_cache' => false,
	'tax_query'           => [
		[
			'taxonomy' => 'unitwise_category',
			'field'    => 'slug',
			'terms'    => $category,
		],
	],
] );

if ( ! $query->have_posts() ) {
	return;
}
?>
<div class="unitwise-popular-converters">
	<?php if ( $title ) : ?>
		<h2 class="unitwise-popular-title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<ul class="unitwise-popular-grid" role="list">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<?php
			$post_id   = get_the_ID();
			$from      = get_post_meta( $post_id, 'unitwise_from_unit', true );
			$to        = get_post_meta( $post_id, 'unitwise_to_unit',   true );
			$from_sym  = $from ? Unitwise_Registry::get_symbol( $from ) : '';
			$to_sym    = $to   ? Unitwise_Registry::get_symbol( $to )   : '';
			$permalink = get_permalink();
			?>
			<li class="unitwise-popular-item">
				<a href="<?php echo esc_url( $permalink ); ?>" class="unitwise-popular-link">
					<span class="unitwise-popular-symbols" aria-hidden="true">
						<?php echo esc_html( $from_sym ); ?> → <?php echo esc_html( $to_sym ); ?>
					</span>
					<span class="unitwise-popular-label"><?php the_title(); ?></span>
				</a>
			</li>
		<?php endwhile; wp_reset_postdata(); ?>
	</ul>
</div>
