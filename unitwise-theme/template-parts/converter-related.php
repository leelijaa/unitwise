<?php
/**
 * Related converters — silo cross-linking section.
 *
 * Shows:
 *   1. Reverse conversion (to → from)
 *   2. Same-source converters (from → any)
 *   3. Same-category siblings
 */

$from           = $args['from']           ?? '';
$to             = $args['to']             ?? '';
$cat            = $args['cat']            ?? 'weight';
$siblings_query = $args['siblings_query'] ?? null;

if ( ! $from || ! $to || ! class_exists( 'Unitwise_Registry' ) ) {
	return;
}

$from_plural = Unitwise_Registry::get_label( $from, true );
$to_plural   = Unitwise_Registry::get_label( $to,   true );

// --- Reverse link ---
$reverse_url = home_url( "/{$cat}/{$to}-to-{$from}/" );
$reverse_lbl = Unitwise_Registry::get_label( $to, true ) . ' to ' . Unitwise_Registry::get_label( $from, true );

// --- Same-source siblings ---
$same_source = new WP_Query( [
	'post_type'           => 'unitwise_converter',
	'posts_per_page'      => 8,
	'no_found_rows'       => true,
	'update_post_meta_cache' => false,
	'meta_query'          => [
		'relation' => 'AND',
		[ 'key' => 'unitwise_from_unit', 'value' => $from ],
		[ 'key' => 'unitwise_to_unit',   'value' => $to, 'compare' => '!=' ],
	],
	'tax_query' => [
		[
			'taxonomy' => 'unitwise_category',
			'field'    => 'slug',
			'terms'    => $cat,
		],
	],
] );

$all_siblings = $siblings_query ? $siblings_query->posts : [];
?>

<aside class="unitwise-related-converters" aria-label="<?php esc_attr_e( 'Related converters', 'unitwise-theme' ); ?>">

	<h2 class="unitwise-related-heading"><?php esc_html_e( 'Related Converters', 'unitwise-theme' ); ?></h2>

	<section>
		<h3 class="unitwise-related-title"><?php esc_html_e( 'Reverse Conversion', 'unitwise-theme' ); ?></h3>
		<ul class="unitwise-related-list">
			<li>
				<a href="<?php echo esc_url( $reverse_url ); ?>">
					<?php echo esc_html( $reverse_lbl ) . ' Converter'; ?>
				</a>
			</li>
		</ul>
	</section>

	<?php if ( $same_source->have_posts() ) : ?>
	<section>
		<h3 class="unitwise-related-title">
			<?php printf( esc_html__( 'Convert From %s', 'unitwise-theme' ), esc_html( $from_plural ) ); ?>
		</h3>
		<ul class="unitwise-related-list">
			<?php while ( $same_source->have_posts() ) : $same_source->the_post(); ?>
				<li>
					<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ul>
	</section>
	<?php endif; ?>

	<?php if ( ! empty( $all_siblings ) ) : ?>
	<section>
		<h3 class="unitwise-related-title">
			<?php
			$cat_info = class_exists( 'Unitwise_Registry' ) ? Unitwise_Registry::get_category( $cat ) : [];
			$cat_name = $cat_info['name'] ?? ucfirst( $cat );
			printf( esc_html__( 'More %s Converters', 'unitwise-theme' ), esc_html( $cat_name ) );
			?>
		</h3>
		<ul class="unitwise-related-list">
			<?php foreach ( array_slice( $all_siblings, 0, 8 ) as $sibling ) : ?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $sibling ) ); ?>">
						<?php echo esc_html( $sibling->post_title ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php endif; ?>

</aside>
