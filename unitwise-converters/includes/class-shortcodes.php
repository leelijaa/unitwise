<?php
/**
 * Shortcode fallbacks for classic-editor compatibility.
 *
 * [unitwise_converter]                       — auto-reads from/to from current post meta
 * [unitwise_converter from="grams" to="kilograms"]
 * [unitwise_table from="grams" to="kilograms"]
 * [unitwise_popular category="weight" limit="12"]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Shortcodes {

	public static function init(): void {
		add_shortcode( 'unitwise_converter', [ __CLASS__, 'render_converter' ] );
		add_shortcode( 'unitwise_table',     [ __CLASS__, 'render_table'     ] );
		add_shortcode( 'unitwise_popular',   [ __CLASS__, 'render_popular'   ] );
	}

	public static function render_converter( array $atts ): string {
		$post_id = get_the_ID();
		$atts    = shortcode_atts( [
			'from'          => $post_id ? get_post_meta( $post_id, 'unitwise_from_unit', true ) : 'grams',
			'to'            => $post_id ? get_post_meta( $post_id, 'unitwise_to_unit',   true ) : 'kilograms',
			'default_value' => 1,
		], $atts, 'unitwise_converter' );

		// Expose to render.php via local variables
		$from          = sanitize_key( $atts['from'] );
		$to            = sanitize_key( $atts['to'] );
		$default_value = (float) $atts['default_value'];
		$attributes    = [ 'from' => $from, 'to' => $to, 'defaultValue' => $default_value ];

		ob_start();
		include UNITWISE_DIR . 'blocks/converter/render.php';
		return ob_get_clean();
	}

	public static function render_table( array $atts ): string {
		$post_id = get_the_ID();
		$atts    = shortcode_atts( [
			'from' => $post_id ? get_post_meta( $post_id, 'unitwise_from_unit', true ) : 'grams',
			'to'   => $post_id ? get_post_meta( $post_id, 'unitwise_to_unit',   true ) : 'kilograms',
		], $atts, 'unitwise_table' );

		$from = sanitize_key( $atts['from'] );
		$to   = sanitize_key( $atts['to'] );

		ob_start();
		include UNITWISE_DIR . 'blocks/conversion-table/render.php';
		return ob_get_clean();
	}

	public static function render_popular( array $atts ): string {
		$atts = shortcode_atts( [
			'category' => 'weight',
			'limit'    => 12,
		], $atts, 'unitwise_popular' );

		$category = sanitize_key( $atts['category'] );
		$limit    = absint( $atts['limit'] );

		ob_start();
		include UNITWISE_DIR . 'blocks/popular-converters/render.php';
		return ob_get_clean();
	}
}
