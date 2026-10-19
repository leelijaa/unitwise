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

$heading    = $attributes['heading']    ?? 'Browse by Category';
$subheading = $attributes['subheading'] ?? '';

$categories = Unitwise_Registry::get_categories();
if ( empty( $categories ) ) {
	return;
}

// Pre-fetch all terms in one query to avoid N+1 lookups in the loop.
$terms_list   = get_terms( [ 'taxonomy' => 'unitwise_category', 'hide_empty' => false ] );
$terms_by_slug = [];
if ( ! is_wp_error( $terms_list ) ) {
	foreach ( $terms_list as $t ) {
		$terms_by_slug[ $t->slug ] = $t;
	}
}
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'hub-section' ] ); ?> aria-label="<?php esc_attr_e( 'Converter categories', 'unitwise-converters' ); ?>">
	<div class="hub-section__header">
		<h2 class="hub-section__title"><?php echo esc_html( $heading ); ?></h2>
		<?php if ( $subheading ) : ?>
			<p class="hub-section__subtitle"><?php echo esc_html( $subheading ); ?></p>
		<?php endif; ?>
	</div>

	<div class="hub-categories">
		<ul class="category-grid" role="list">
			<?php foreach ( $categories as $slug => $cat ) :
				$term  = $terms_by_slug[ $slug ] ?? null;
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
