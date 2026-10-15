<?php
/**
 * Server-side render for unitwise/converter.
 *
 * $attributes  — block attributes from block.json
 * $content     — inner blocks (unused)
 * $block       — WP_Block instance
 *
 * Reads from/to from block attributes first, then falls back to post meta,
 * then to URL query vars — so it works on the post editor preview AND the
 * live converter page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$from = ! empty( $attributes['from'] )
	? sanitize_key( $attributes['from'] )
	: ( $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_from_unit', true ) ) : '' );

$to = ! empty( $attributes['to'] )
	? sanitize_key( $attributes['to'] )
	: ( $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_to_unit', true ) ) : '' );

// Fallback to URL query vars (e.g. when rendered outside a post context)
if ( ! $from ) {
	$from = sanitize_key( get_query_var( 'unitwise_from' ) );
}
if ( ! $to ) {
	$to = sanitize_key( get_query_var( 'unitwise_to' ) );
}

if ( ! $from || ! $to || ! Unitwise_Registry::unit_exists( $from ) || ! Unitwise_Registry::unit_exists( $to ) ) {
	return;
}

$default_value = isset( $attributes['defaultValue'] ) ? (float) $attributes['defaultValue'] : 1.0;
$engine        = new Unitwise_Engine( get_query_var( 'unitwise_cat' ) ?: Unitwise_Registry::get_unit_category( $from ) );
$result        = $engine->format_result( $engine->convert( $default_value, $from, $to ) );
$from_sym      = Unitwise_Registry::get_symbol( $from );
$to_sym        = Unitwise_Registry::get_symbol( $to );
$from_plural   = Unitwise_Registry::get_label( $from, true );
$to_plural     = Unitwise_Registry::get_label( $to, true );
$formula       = $engine->get_formula( $from, $to );

// Output unitwiseData inline — wp_localize_script is too late here (after wp_head),
// so we emit a plain <script> tag. view.js is footer-loaded so this executes first.
static $localized = false;
if ( ! $localized ) {
	$localized = true;
	$cat_slug = get_query_var( 'unitwise_cat' ) ?: Unitwise_Registry::get_unit_category( $from );
	$uw_data  = wp_json_encode( [
		'factors'  => Unitwise_Registry::get_factors( $cat_slug ),
		'decimals' => 6,
		'from'     => $from,
		'to'       => $to,
	] );
	if ( false !== $uw_data ) {
		echo '<script>window.unitwiseData=' . $uw_data . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
?>
<section
	class="unitwise-converter-block"
	data-from="<?php echo esc_attr( $from ); ?>"
	data-to="<?php echo esc_attr( $to ); ?>"
	aria-label="<?php printf( esc_attr__( '%1$s to %2$s converter', 'unitwise-converters' ), esc_attr( $from_plural ), esc_attr( $to_plural ) ); ?>"
>
	<form class="unitwise-converter-form" method="get" novalidate>
		<div class="unitwise-converter-inputs">
			<div class="unitwise-input-group">
				<label for="unitwise-input-<?php echo esc_attr( $from ); ?>" class="unitwise-label">
					<?php echo esc_html( $from_plural ); ?>
					<span class="unitwise-symbol">(<?php echo esc_html( $from_sym ); ?>)</span>
				</label>
				<input
					id="unitwise-input-<?php echo esc_attr( $from ); ?>"
					class="unitwise-input"
					type="number"
					name="v"
					value="<?php echo esc_attr( $default_value ); ?>"
					step="any"
					inputmode="decimal"
					autocomplete="off"
					aria-label="<?php printf( esc_attr__( 'Value in %s', 'unitwise-converters' ), esc_attr( $from_plural ) ); ?>"
				>
			</div>

			<div class="unitwise-equals" aria-hidden="true">=</div>

			<div class="unitwise-output-group">
				<label class="unitwise-label" for="unitwise-result-display">
					<?php echo esc_html( $to_plural ); ?>
					<span class="unitwise-symbol">(<?php echo esc_html( $to_sym ); ?>)</span>
				</label>
				<output
					id="unitwise-result-display"
					class="unitwise-result"
					for="unitwise-input-<?php echo esc_attr( $from ); ?>"
					aria-live="polite"
					aria-atomic="true"
				><?php echo esc_html( $result ); ?></output>
			</div>
		</div>

		<p class="unitwise-formula">
			<?php
			printf(
				/* translators: %s: formula string */
				esc_html__( 'Formula: %s', 'unitwise-converters' ),
				'<code>' . esc_html( $formula ) . '</code>'
			);
			?>
		</p>

		<noscript>
			<p class="unitwise-noscript-note">
				<?php esc_html_e( 'JavaScript is disabled — the conversion table below still works.', 'unitwise-converters' ); ?>
			</p>
		</noscript>
	</form>
</section>
