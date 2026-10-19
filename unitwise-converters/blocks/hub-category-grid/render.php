<?php
/**
 * Render: unitwise/hub-category-grid — category silo cards.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Unitwise_Registry' ) ) {
	return;
}

$heading    = esc_html( $attributes['heading']    ?? 'Browse by Category' );
$subheading = esc_html( $attributes['subheading'] ?? '' );

$categories = Unitwise_Registry::get_categories();
if ( empty( $categories ) ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'hub-section' ] ); ?> aria-label="<?php esc_attr_e( 'Converter categories', 'unitwise-converters' ); ?>">
	<div class="hub-section__header">
		<h2 class="hub-section__title"><?php echo $heading; ?></h2>
		<?php if ( $subheading ) : ?>
			<p class="hub-section__subtitle"><?php echo $subheading; ?></p>
		<?php endif; ?>
	</div>

	<div class="hub-categories">
		<ul class="category-grid" role="list">
			<?php foreach ( $categories as $slug => $cat ) :
				$term  = get_term_by( 'slug', $slug, 'unitwise_category' );
				$count = $term ? $term->count : 0;
			?>
			<li class="category-card">
				<a href="<?php echo esc_url( home_url( "/{$slug}/" ) ); ?>" class="category-card__link">
					<?php if ( ! empty( $cat['icon'] ) ) : ?>
						<span class="category-card__icon" aria-hidden="true"><?php echo esc_html( $cat['icon'] ); ?></span>
					<?php endif; ?>
					<span class="category-card__name"><?php echo esc_html( $cat['name'] ); ?></span>
					<?php if ( $count ) : ?>
						<span class="category-card__count">
							<?php
							printf(
								/* translators: %d: number of converters */
								esc_html( _n( '%d converter', '%d converters', $count, 'unitwise-converters' ) ),
								(int) $count
							);
							?>
						</span>
					<?php endif; ?>
					<?php if ( ! empty( $cat['description'] ) ) : ?>
						<span class="category-card__desc"><?php echo esc_html( $cat['description'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
