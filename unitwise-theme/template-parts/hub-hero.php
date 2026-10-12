<?php
/**
 * Hub homepage hero — grid-bg + radial glow + gradient headline.
 * Theetasoft-inspired design.
 */
$converter_count = wp_count_posts( 'unitwise_converter' )->publish ?? 41;
$category_count  = wp_count_terms( [ 'taxonomy' => 'unitwise_category', 'hide_empty' => false ] ) ?: 1;
?>
<section class="hub-hero" aria-label="<?php esc_attr_e( 'Site hero', 'unitwise-theme' ); ?>">
	<div class="hub-hero__bg-grid" aria-hidden="true"></div>
	<div class="hub-hero__bg-glow" aria-hidden="true"></div>

	<div class="container">
		<div class="animate-rise">
			<span class="hub-hero__badge">
				<span class="hub-hero__badge-dot" aria-hidden="true"></span>
				<?php esc_html_e( 'Free &amp; No Sign-up Required', 'unitwise-theme' ); ?>
			</span>
		</div>

		<h1 class="hub-hero__title animate-rise-2">
			<?php esc_html_e( 'Free Online', 'unitwise-theme' ); ?>
			<span class="accent"><?php esc_html_e( 'Unit Converters', 'unitwise-theme' ); ?></span>
		</h1>

		<p class="hub-hero__subtitle animate-rise-3">
			<?php esc_html_e( 'Instant, accurate conversions for weight, mass, and more. Beautifully simple, completely free.', 'unitwise-theme' ); ?>
		</p>

		<div class="hub-hero__stats animate-rise-3">
			<div class="hub-stat">
				<span class="hub-stat__num"><?php echo absint( $converter_count ); ?>+</span>
				<span class="hub-stat__label"><?php esc_html_e( 'Converters', 'unitwise-theme' ); ?></span>
			</div>
			<div class="hub-stat">
				<span class="hub-stat__num"><?php echo absint( $category_count ); ?></span>
				<span class="hub-stat__label"><?php esc_html_e( 'Categories', 'unitwise-theme' ); ?></span>
			</div>
			<div class="hub-stat">
				<span class="hub-stat__num">100%</span>
				<span class="hub-stat__label"><?php esc_html_e( 'Free forever', 'unitwise-theme' ); ?></span>
			</div>
		</div>
	</div>
</section>
