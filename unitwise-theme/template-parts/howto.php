<?php
/**
 * How-to steps template part — visual presentation of conversion steps.
 * JSON-LD HowTo is output by the plugin (class-schema.php).
 *
 * $args['from']    string  unit slug
 * $args['to']      string  unit slug
 * $args['engine']  Unitwise_Engine instance
 */

$from   = $args['from']   ?? '';
$to     = $args['to']     ?? '';
$engine = $args['engine'] ?? null;

if ( ! $from || ! $to || ! $engine || ! class_exists( 'Unitwise_Registry' ) ) {
	return;
}

$from_plural = Unitwise_Registry::get_label( $from, true );
$to_plural   = Unitwise_Registry::get_label( $to,   true );
$formula     = $engine->get_formula( $from, $to );
$example     = $engine->get_worked_example( 1, $from, $to );
?>
<ol class="howto-steps">
	<li class="howto-step">
		<span class="howto-step__num" aria-hidden="true">1</span>
		<div class="howto-step__body">
			<strong><?php esc_html_e( 'Enter the value', 'unitwise-theme' ); ?></strong>
			<p>
				<?php
				printf(
					/* translators: %s: from-unit plural */
					esc_html__( 'Type the number of %s in the input field above.', 'unitwise-theme' ),
					esc_html( $from_plural )
				);
				?>
			</p>
		</div>
	</li>
	<li class="howto-step">
		<span class="howto-step__num" aria-hidden="true">2</span>
		<div class="howto-step__body">
			<strong><?php esc_html_e( 'Apply the formula', 'unitwise-theme' ); ?></strong>
			<p><code><?php echo esc_html( $formula ); ?></code></p>
		</div>
	</li>
	<li class="howto-step">
		<span class="howto-step__num" aria-hidden="true">3</span>
		<div class="howto-step__body">
			<strong><?php esc_html_e( 'Read the result', 'unitwise-theme' ); ?></strong>
			<p>
				<?php
				printf(
					/* translators: 1: example string, 2: to-unit plural */
					esc_html__( 'Example: %1$s. The result is in %2$s.', 'unitwise-theme' ),
					esc_html( $example ),
					esc_html( $to_plural )
				);
				?>
			</p>
		</div>
	</li>
</ol>
