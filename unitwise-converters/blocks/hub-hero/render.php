<?php
/**
 * Render: unitwise/hub-hero — full-width homepage hero section.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$badge       = esc_html( $attributes['badge']      ?? 'Free & No Sign-up Required' );
$headline    = esc_html( $attributes['headline']   ?? 'Free Online' );
$accent_text = esc_html( $attributes['accentText'] ?? 'Unit Converters' );
$lead        = esc_html( $attributes['lead']       ?? '' );

$converter_count = wp_count_posts( 'unitwise_converter' )->publish ?? 41;
$count_result    = wp_count_terms( [ 'taxonomy' => 'unitwise_category', 'hide_empty' => false ] );
$category_count  = ( is_wp_error( $count_result ) || ! $count_result ) ? 1 : (int) $count_result;
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'hub-hero' ] ); ?> aria-label="<?php esc_attr_e( 'Site hero', 'unitwise-converters' ); ?>">
	<div class="hub-hero__bg-grid" aria-hidden="true"></div>
	<div class="hub-hero__bg-glow" aria-hidden="true"></div>

	<div class="container">
		<div class="animate-rise">
			<span class="hub-hero__badge">
				<span class="hub-hero__badge-dot" aria-hidden="true"></span>
				<?php echo $badge; ?>
			</span>
		</div>

		<h1 class="hub-hero__title animate-rise-2">
			<?php echo $headline; ?>
			<?php if ( $accent_text ) : ?>
				<span class="accent"><?php echo $accent_text; ?></span>
			<?php endif; ?>
		</h1>

		<?php if ( $lead ) : ?>
			<p class="hub-hero__subtitle animate-rise-3"><?php echo $lead; ?></p>
		<?php endif; ?>

		<div class="hub-hero__stats animate-rise-3">
			<div class="hub-stat">
				<span class="hub-stat__num"><?php echo absint( $converter_count ); ?>+</span>
				<span class="hub-stat__label"><?php esc_html_e( 'Converters', 'unitwise-converters' ); ?></span>
			</div>
			<div class="hub-stat">
				<span class="hub-stat__num"><?php echo absint( $category_count ); ?></span>
				<span class="hub-stat__label"><?php esc_html_e( 'Categories', 'unitwise-converters' ); ?></span>
			</div>
			<div class="hub-stat">
				<span class="hub-stat__num">100%</span>
				<span class="hub-stat__label"><?php esc_html_e( 'Free forever', 'unitwise-converters' ); ?></span>
			</div>
		</div>
	</div>
</section>
