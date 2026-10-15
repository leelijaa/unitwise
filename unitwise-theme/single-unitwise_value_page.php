<?php
/**
 * Template: Single UnitWise Value Page
 *
 * Rendered when a native WP `page` has _uw_value_page = '1' meta.
 * Template routing: Unitwise_Value_Pages::route_template() in the plugin.
 *
 * THEME LAYER — presentation only. All data comes from plugin meta + engine.
 */

get_header();

$id    = get_the_ID();
$value = (float) get_post_meta( $id, '_uw_vp_value', true );
$from  = get_post_meta( $id, '_uw_vp_from', true );
$to    = get_post_meta( $id, '_uw_vp_to',   true );
$cat   = get_post_meta( $id, '_uw_vp_cat',  true ) ?: 'weight';

if ( ! $from || ! $to || ! class_exists( 'Unitwise_Engine' ) ) {
	get_footer();
	return;
}

$engine   = new Unitwise_Engine( $cat );
$result   = $engine->format_result( $engine->convert( $value, $from, $to ) );
$formula  = $engine->get_formula( $from, $to );
$plural   = ( 1.0 !== $value );
$from_l   = Unitwise_Registry::get_label( $from, $plural );
$to_l     = Unitwise_Registry::get_label( $to, true );
$from_l_p = Unitwise_Registry::get_label( $from, true );
$to_l_p   = Unitwise_Registry::get_label( $to, true );
$from_sym = Unitwise_Registry::get_symbol( $from );
$to_sym   = Unitwise_Registry::get_symbol( $to );
$v_str    = ( floor( $value ) == $value ) ? (int) $value : $value;

// Parent category / converter links
$cat_info      = Unitwise_Registry::get_category( $cat );
$cat_name      = $cat_info['name'] ?? ucfirst( $cat );
$cat_url       = home_url( "/{$cat}/" );
$converter_url = home_url( "/{$cat}/{$from}-to-{$to}/" );
$reverse_url   = home_url( "/{$cat}/{$to}-to-{$from}/" );

// Context array passed to template sections for placeholder replacement
$section_ctx = [
	'from'   => $from_l,
	'to'     => $to_l_p,
	'value'  => (string) $v_str,
	'result' => $result,
];

// Breadcrumbs (consumed by template-parts/breadcrumbs.php via $GLOBALS['uw_crumbs'])
$GLOBALS['uw_crumbs'] = [
	[ 'label' => $cat_name, 'url' => $cat_url ],
	[ 'label' => "{$from_l_p} to {$to_l_p} Converter", 'url' => $converter_url ],
	[ 'label' => "{$v_str} {$from_l} to {$to_l_p}", 'url' => '' ],
];
?>

<main id="main-content" class="site-main vp-page">
<div class="container">

	<!-- Breadcrumb -->
	<?php get_template_part( 'template-parts/breadcrumbs' ); ?>

	<!-- Hero / Big answer block -->
	<section class="vp-hero" aria-label="<?php esc_attr_e( 'Conversion result', 'unitwise-theme' ); ?>">
		<h1 class="vp-hero__title">
			<?php echo esc_html( "{$v_str} {$from_l} to {$to_l_p}" ); ?>
		</h1>
		<div class="vp-hero__answer">
			<span class="vp-hero__from"><?php echo esc_html( "{$v_str} {$from_l}" ); ?></span>
			<span class="vp-hero__eq" aria-hidden="true">=</span>
			<strong class="vp-hero__to" id="uw-vp-hero-result"><?php echo esc_html( $result ); ?></strong>
			<span class="vp-hero__unit"><?php echo esc_html( $to_l_p ); ?></span>
		</div>
		<p class="vp-hero__sym">
			<?php echo esc_html( "{$v_str} {$from_sym} = {$result} {$to_sym}" ); ?>
		</p>
	</section>

	<?php
	// --- INJECTION POINT: after_hero ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'after_hero', $section_ctx );
	}
	?>

	<!-- Interactive converter (pre-filled with this page's value) -->
	<section class="vp-section" aria-label="<?php esc_attr_e( 'Convert another value', 'unitwise-theme' ); ?>">
		<h2 class="vp-section__title"><?php esc_html_e( 'Convert Another Value', 'unitwise-theme' ); ?></h2>
		<div class="uw-vp-converter">
			<div class="uw-vp-converter__field">
				<label for="uw-vp-input" class="uw-vp-converter__label">
					<?php echo esc_html( "{$from_l_p} ({$from_sym})" ); ?>
				</label>
				<input type="number" id="uw-vp-input"
				       class="uw-vp-converter__input"
				       value="<?php echo esc_attr( (string) $v_str ); ?>"
				       step="any" min="0" autocomplete="off">
			</div>
			<div class="uw-vp-converter__arrow" aria-hidden="true">→</div>
			<div class="uw-vp-converter__field">
				<label for="uw-vp-output" class="uw-vp-converter__label">
					<?php echo esc_html( "{$to_l_p} ({$to_sym})" ); ?>
				</label>
				<output id="uw-vp-output"
				        class="uw-vp-converter__output"
				        for="uw-vp-input"
				        aria-live="polite">
					<?php echo esc_html( $result ); ?>
				</output>
			</div>
		</div>
		<p class="vp-section__meta">
			<?php printf(
				/* translators: 1: from unit plural, 2: to unit plural, 3: converter URL */
				wp_kses(
					__( 'For more options see the full <a href="%3$s">%1$s to %2$s converter</a>.', 'unitwise-theme' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_html( $from_l_p ),
				esc_html( $to_l_p ),
				esc_url( $converter_url )
			); ?>
		</p>
	</section>

	<?php
	// --- INJECTION POINT: after_converter ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'after_converter', $section_ctx );
	}
	?>

	<!-- Formula -->
	<section class="vp-section" aria-label="<?php esc_attr_e( 'Formula', 'unitwise-theme' ); ?>">
		<h2 class="vp-section__title"><?php esc_html_e( 'Formula', 'unitwise-theme' ); ?></h2>
		<div class="vp-formula">
			<code class="vp-formula__code"><?php echo esc_html( $formula ); ?></code>
		</div>
		<p class="vp-section__text">
			<?php printf(
				esc_html__( 'To convert %1$s to %2$s: multiply the %1$s value by the conversion factor. Applying this to %3$s %4$s gives %5$s %6$s.', 'unitwise-theme' ),
				esc_html( $from_l_p ),
				esc_html( $to_l_p ),
				esc_html( (string) $v_str ),
				esc_html( $from_l ),
				esc_html( $result ),
				esc_html( $to_l_p )
			); ?>
		</p>
	</section>

	<?php
	// --- INJECTION POINT: after_formula ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'after_formula', $section_ctx );
	}
	?>

	<!-- Reference table of nearby values -->
	<?php
	$table_values = Unitwise_Engine::default_table_values( $from );

	// Build a slug-keyed URL map from the transient cache to avoid one DB query per table row.
	$vp_url_map = [];
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		foreach ( Unitwise_Value_Pages::all_pages_data() as $vp ) {
			if ( $vp['from'] === $from && $vp['to'] === $to ) {
				$vp_url_map[ (string) $vp['value'] ] = $vp['url'];
			}
		}
	}
	?>
	<section class="vp-section" aria-label="<?php esc_attr_e( 'Conversion table', 'unitwise-theme' ); ?>">
		<h2 class="vp-section__title">
			<?php printf(
				esc_html__( '%1$s to %2$s Conversion Table', 'unitwise-theme' ),
				esc_html( $from_l_p ),
				esc_html( $to_l_p )
			); ?>
		</h2>
		<div class="vp-table-wrap">
			<table class="vp-table">
				<caption class="screen-reader-text">
					<?php printf( esc_html__( '%1$s to %2$s conversion table', 'unitwise-theme' ), esc_html( $from_l_p ), esc_html( $to_l_p ) ); ?>
				</caption>
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html( $from_l_p ); ?></th>
						<th scope="col"><?php echo esc_html( $to_l_p ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $table_values as $tv ) :
						$tv_result  = $engine->format_result( $engine->convert( $tv, $from, $to ) );
						$tv_label   = Unitwise_Registry::get_label( $from, ( 1 != $tv ) );
						$is_current = ( (string) $tv === (string) $value );
						$tv_url     = ! $is_current ? ( $vp_url_map[ (string) $tv ] ?? null ) : null;
					?>
					<tr<?php echo $is_current ? ' class="vp-table__row--current" aria-current="row"' : ''; ?>>
						<td>
							<?php if ( $tv_url ) : ?>
								<a href="<?php echo esc_url( $tv_url ); ?>">
									<?php echo esc_html( "{$tv} {$tv_label}" ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( "{$tv} {$tv_label}" ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( "{$tv_result} {$to_l_p}" ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<?php
	// --- INJECTION POINT: after_table ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'after_table', $section_ctx );
	}
	?>

	<!-- Related converters -->
	<section class="vp-section vp-related" aria-label="<?php esc_attr_e( 'Related converters', 'unitwise-theme' ); ?>">
		<h2 class="vp-section__title"><?php esc_html_e( 'Related Converters', 'unitwise-theme' ); ?></h2>
		<ul class="vp-related__list">
			<li>
				<a href="<?php echo esc_url( $converter_url ); ?>">
					<?php printf(
						esc_html__( '%1$s to %2$s Converter — all values', 'unitwise-theme' ),
						esc_html( $from_l_p ),
						esc_html( $to_l_p )
					); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( $reverse_url ); ?>">
					<?php printf(
						esc_html__( '%1$s to %2$s Converter', 'unitwise-theme' ),
						esc_html( $to_l_p ),
						esc_html( $from_l_p )
					); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( $cat_url ); ?>">
					<?php printf( esc_html__( 'All %s Converters', 'unitwise-theme' ), esc_html( $cat_name ) ); ?>
				</a>
			</li>
		</ul>
	</section>

	<?php
	// --- INJECTION POINT: after_related ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'after_related', $section_ctx );
	}
	?>

	<?php
	// --- INJECTION POINT: before_faq ---
	if ( class_exists( 'Unitwise_Value_Pages' ) ) {
		Unitwise_Value_Pages::render_sections( 'before_faq', $section_ctx );
	}
	?>

	<!-- FAQ -->
	<?php
	$faqs = [
		[
			'q' => sprintf(
				/* translators: 1: to unit plural, 2: value, 3: from unit label */
				__( 'How many %1$s is %2$s %3$s?', 'unitwise-theme' ),
				$to_l_p, $v_str, $from_l
			),
			'a' => sprintf(
				/* translators: 1: value, 2: from unit, 3: result, 4: to unit, 5: formula */
				__( '%1$s %2$s equals %3$s %4$s. Formula: %5$s.', 'unitwise-theme' ),
				$v_str, $from_l, $result, $to_l_p, $formula
			),
		],
		[
			'q' => sprintf(
				/* translators: 1: value, 2: from unit, 3: to unit plural */
				__( 'How do you convert %1$s %2$s to %3$s?', 'unitwise-theme' ),
				$v_str, $from_l, $to_l_p
			),
			'a' => sprintf(
				/* translators: 1: value, 2: from unit, 3: to unit plural, 4: formula, 5: result, 6: to unit plural */
				__( 'To convert %1$s %2$s to %3$s, apply the formula: %4$s. The result is %5$s %6$s.', 'unitwise-theme' ),
				$v_str, $from_l, $to_l_p, $formula, $result, $to_l_p
			),
		],
		[
			'q' => sprintf(
				/* translators: 1: value, 2: from unit, 3: to unit plural */
				__( 'What is %1$s %2$s in %3$s?', 'unitwise-theme' ),
				$v_str, $from_l, $to_l_p
			),
			'a' => sprintf(
				/* translators: 1: value, 2: from unit, 3: result, 4: to unit plural */
				__( '%1$s %2$s is equal to %3$s %4$s.', 'unitwise-theme' ),
				$v_str, $from_l, $result, $to_l_p
			),
		],
	];
	get_template_part( 'template-parts/faq', null, [ 'faqs' => $faqs ] );
	?>

</div><!-- .container -->
</main><!-- .vp-page -->

<script>
/* Inline converter JS — recalculates live as the user types */
(function () {
	'use strict';
	var input   = document.getElementById('uw-vp-input');
	var output  = document.getElementById('uw-vp-output');
	var heroOut = document.getElementById('uw-vp-hero-result');
	var fromKey = <?php echo wp_json_encode( $from ); ?>;
	var toKey   = <?php echo wp_json_encode( $to ); ?>;

	function formatResult( value, dp ) {
		if ( value === 0 ) return '0';
		var abs = Math.abs( value );
		if ( abs < 0.0001 || abs >= 10000000 ) { return value.toExponential( 6 ); }
		var str = value.toFixed( dp );
		return str.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
	}

	function recalc() {
		if ( !window.unitwiseData || !window.unitwiseData.factors ) { return; }
		var v = parseFloat( input.value );
		if ( isNaN( v ) || v < 0 ) {
			output.textContent = '—';
			if ( heroOut ) { heroOut.textContent = '—'; }
			return;
		}
		var f   = window.unitwiseData.factors;
		var dec = window.unitwiseData.decimals || 6;
		var res = v * f[fromKey] / f[toKey];
		output.textContent = formatResult( res, dec );
		if ( heroOut ) { heroOut.textContent = formatResult( res, dec ); }
	}

	if ( input ) { input.addEventListener( 'input', recalc ); }
}());
</script>

<?php get_footer(); ?>
