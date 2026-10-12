<?php
/**
 * Converter detail page template.
 *
 * Renders all 9 content sections from the build spec in order:
 *   1. Breadcrumb
 *   2. H1
 *   3. Live converter tool (block)
 *   4. Formula
 *   5. Worked example
 *   6. Conversion table
 *   7. Short explainer
 *   8. FAQ
 *   9. Related converters
 *
 * All heavy logic lives in the plugin (Unitwise_Engine, Unitwise_Registry).
 * This file is presentation only.
 */

get_header();

$post_id = get_the_ID();
$from    = $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_from_unit', true ) ) : '';
$to      = $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_to_unit',   true ) ) : '';

if ( ! $from ) {
	$from = sanitize_key( get_query_var( 'unitwise_from' ) );
}
if ( ! $to ) {
	$to = sanitize_key( get_query_var( 'unitwise_to' ) );
}

$cat     = sanitize_key( get_query_var( 'unitwise_cat' ) ) ?: 'weight';
$valid   = $from && $to && class_exists( 'Unitwise_Registry' )
	&& Unitwise_Registry::unit_exists( $from )
	&& Unitwise_Registry::unit_exists( $to );

if ( $valid ) {
	$engine      = new Unitwise_Engine( $cat );
	$from_plural = Unitwise_Registry::get_label( $from, true );
	$to_plural   = Unitwise_Registry::get_label( $to,   true );
	$from_sym    = Unitwise_Registry::get_symbol( $from );
	$to_sym      = Unitwise_Registry::get_symbol( $to );
	$formula     = $engine->get_formula( $from, $to );
	$example     = $engine->get_worked_example( 1, $from, $to );
	$cat_info    = Unitwise_Registry::get_category( $cat );
	$table_vals  = Unitwise_Engine::default_table_values( $from );
	$table_rows  = $engine->get_table_rows( $from, $to, $table_vals );

	// Explainer from meta override, or generated from registry descriptions
	$intro_override = $post_id ? get_post_meta( $post_id, 'unitwise_intro_override', true ) : '';
	$from_desc      = Unitwise_Registry::get_description( $from );
	$to_desc        = Unitwise_Registry::get_description( $to );

	// FAQ: meta override or schema defaults
	$faq_override = $post_id ? get_post_meta( $post_id, 'unitwise_faq_override', true ) : [];
	if ( empty( $faq_override ) ) {
		$faq_override = [
			[
				'q' => "How many {$to_plural} are in 1 " . Unitwise_Registry::get_label( $from ) . '?',
				'a' => '1 ' . Unitwise_Registry::get_label( $from ) . ' = ' . $engine->format_result( $engine->convert( 1, $from, $to ) ) . " {$to_plural}.",
			],
			[
				'q' => "What is the formula to convert {$from_plural} to {$to_plural}?",
				'a' => "The formula is: {$formula}.",
			],
			[
				'q' => 'Is this converter accurate?',
				'a' => 'Yes — it uses exact international conversion factors.',
			],
		];
	}

	// Related converters (siblings in same category, plus reverse)
	$siblings_query = new WP_Query( [
		'post_type'      => 'unitwise_converter',
		'posts_per_page' => 10,
		'post__not_in'   => $post_id ? [ $post_id ] : [],
		'meta_query'     => [
			[ 'key' => 'unitwise_from_unit', 'value' => $from ],
		],
		'tax_query' => [
			[
				'taxonomy' => 'unitwise_category',
				'field'    => 'slug',
				'terms'    => $cat,
			],
		],
	] );
}
?>

<main id="main-content" class="site-main converter-page">

	<?php /* 1. Breadcrumb bar — pass crumbs explicitly to avoid $wp_query override issues */ ?>
	<?php
	if ( $valid ) {
		$cat_data = Unitwise_Registry::get_category( $cat );
		$GLOBALS['uw_crumbs'] = [
			[
				'label' => $cat_data['name'] ?? ucfirst( $cat ),
				'url'   => home_url( "/{$cat}/" ),
			],
			[
				'label' => $from_plural . ' to ' . $to_plural,
				'url'   => '',
			],
		];
	}
	get_template_part( 'template-parts/breadcrumbs' );
	?>

	<div class="container">

		<?php if ( $valid ) : ?>

			<article class="converter-article">

				<?php /* 2. H1 */ ?>
				<header class="converter-article__header">
					<h1 class="converter-article__title">
						<?php printf( esc_html__( '%1$s to %2$s Converter', 'unitwise-theme' ), esc_html( $from_plural ), esc_html( $to_plural ) ); ?>
					</h1>
				</header>

				<?php /* 3. Live converter tool */ ?>
				<section class="converter-section" aria-label="<?php esc_attr_e( 'Converter tool', 'unitwise-theme' ); ?>">
					<?php echo do_blocks( '<!-- wp:unitwise/converter /-->' ); ?>
				</section>

				<?php /* 4. Formula */ ?>
				<section class="converter-section" aria-label="<?php esc_attr_e( 'Formula', 'unitwise-theme' ); ?>">
					<h2><?php esc_html_e( 'Formula', 'unitwise-theme' ); ?></h2>
					<div class="unitwise-formula-block">
						<code><?php echo esc_html( $formula ); ?></code>
					</div>
				</section>

				<?php /* 5. Worked example */ ?>
				<section class="converter-section" aria-label="<?php esc_attr_e( 'Worked example', 'unitwise-theme' ); ?>">
					<h2><?php esc_html_e( 'Example', 'unitwise-theme' ); ?></h2>
					<div class="unitwise-worked-example">
						<code><?php echo esc_html( $example ); ?></code>
					</div>
				</section>

				<?php /* 6. Conversion table */ ?>
				<section class="converter-section" aria-label="<?php printf( esc_attr__( '%1$s to %2$s conversion table', 'unitwise-theme' ), esc_attr( $from_plural ), esc_attr( $to_plural ) ); ?>">
					<h2>
						<?php
						printf(
							/* translators: 1: from unit, 2: to unit */
							esc_html__( '%1$s to %2$s Conversion Table', 'unitwise-theme' ),
							esc_html( $from_plural ),
							esc_html( $to_plural )
						);
						?>
					</h2>
					<?php echo do_blocks( '<!-- wp:unitwise/conversion-table /-->' ); ?>
				</section>

				<?php /* 7. Explainer */ ?>
				<section class="converter-section unitwise-explainer-section" aria-label="<?php esc_attr_e( 'About these units', 'unitwise-theme' ); ?>">
					<h2><?php esc_html_e( 'About These Units', 'unitwise-theme' ); ?></h2>

					<?php if ( $intro_override ) : ?>
						<div class="unitwise-explainer"><?php echo wp_kses_post( $intro_override ); ?></div>
					<?php else : ?>
						<?php if ( $from_desc ) : ?>
							<p class="unitwise-explainer"><strong><?php echo esc_html( Unitwise_Registry::get_label( $from ) ); ?>:</strong> <?php echo esc_html( $from_desc ); ?></p>
						<?php endif; ?>
						<?php if ( $to_desc ) : ?>
							<p class="unitwise-explainer"><strong><?php echo esc_html( Unitwise_Registry::get_label( $to ) ); ?>:</strong> <?php echo esc_html( $to_desc ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</section>

				<?php /* 8. FAQ */ ?>
				<?php if ( ! empty( $faq_override ) ) : ?>
					<section class="converter-section" aria-label="<?php esc_attr_e( 'Frequently asked questions', 'unitwise-theme' ); ?>">
						<h2><?php esc_html_e( 'Frequently Asked Questions', 'unitwise-theme' ); ?></h2>
						<?php get_template_part( 'template-parts/faq', null, [ 'faqs' => $faq_override ] ); ?>
					</section>
				<?php endif; ?>

				<?php /* 9. Related converters */ ?>
				<section class="converter-section">
					<?php get_template_part( 'template-parts/converter-related', null, [
						'from'           => $from,
						'to'             => $to,
						'cat'            => $cat,
						'siblings_query' => $siblings_query ?? null,
					] ); ?>
				</section>

			</article>

		<?php else : ?>
			<p><?php esc_html_e( 'Converter not found. The plugin may not be active.', 'unitwise-theme' ); ?></p>
		<?php endif; ?>

	</div>
</main>

<?php get_footer(); ?>
