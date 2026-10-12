<?php
/**
 * Hub homepage — category silo cards.
 * Links down to each /{category}/ landing page.
 */

if ( ! class_exists( 'Unitwise_Registry' ) ) {
	return;
}

$categories = Unitwise_Registry::get_categories();
if ( empty( $categories ) ) {
	return;
}
?>
<section class="hub-categories" aria-label="<?php esc_attr_e( 'Converter categories', 'unitwise-theme' ); ?>">
	<h2 class="hub-section-title"><?php esc_html_e( 'Browse by Category', 'unitwise-theme' ); ?></h2>

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
							esc_html( _n( '%d converter', '%d converters', $count, 'unitwise-theme' ) ),
							(int) $count
						);
						?>
					</span>
				<?php endif; ?>
				<?php if ( $cat['description'] ) : ?>
					<span class="category-card__desc"><?php echo esc_html( $cat['description'] ); ?></span>
				<?php endif; ?>
			</a>
		</li>
		<?php endforeach; ?>
	</ul>
</section>
