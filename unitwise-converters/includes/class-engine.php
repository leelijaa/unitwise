<?php
/**
 * Conversion engine.
 *
 * One convert() function drives all conversions. Adding a new unit = one row
 * in Unitwise_Registry::$weight_factors. No new logic needed here.
 *
 * PLUGIN LAYER.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Engine {

	private array $factors;
	private string $category;

	public function __construct( string $category = 'weight' ) {
		$this->category = $category;
		$this->factors  = Unitwise_Registry::get_factors( $category );
	}

	// -------------------------------------------------------------------------
	// Core

	/**
	 * Convert a value from one unit slug to another.
	 *
	 * Formula: result = value × factor[from] ÷ factor[to]
	 */
	public function convert( float $value, string $from, string $to ): float {
		if ( ! isset( $this->factors[ $from ], $this->factors[ $to ] ) ) {
			return 0.0;
		}
		return $value * $this->factors[ $from ] / $this->factors[ $to ];
	}

	// -------------------------------------------------------------------------
	// Display helpers

	/**
	 * Format a float for display.
	 *
	 * Values with absolute magnitude < 0.0001 or ≥ 10,000,000 are rendered
	 * in scientific notation to avoid misleading rounding.
	 */
	public function format_result( float $value, int $decimals = 6 ): string {
		if ( $value === 0.0 ) {
			return '0';
		}

		$abs = abs( $value );

		if ( $abs < 0.0001 || $abs >= 10_000_000 ) {
			// scientific: e.g. 1.23456789e-7 → trim trailing zeros
			$sci = sprintf( '%.10e', $value );
			[ $mantissa, $exp ] = explode( 'e', $sci );
			$mantissa = rtrim( rtrim( $mantissa, '0' ), '.' );
			return $mantissa . 'e' . $exp;
		}

		return rtrim( rtrim( number_format( $value, $decimals, '.', ',' ), '0' ), '.' );
	}

	/**
	 * Build a conversion table: common values in $from → converted to $to.
	 *
	 * @return array[] Each element: [ 'from' => string, 'to' => string ]
	 */
	public function get_table_rows( string $from, string $to, array $values ): array {
		return array_map( fn( $v ) => [
			'from' => $this->format_result( (float) $v, 4 ),
			'to'   => $this->format_result( $this->convert( (float) $v, $from, $to ), 6 ),
		], $values );
	}

	/**
	 * Human-readable formula. e.g. "kg = g ÷ 1,000"
	 */
	public function get_formula( string $from, string $to ): string {
		$from_f  = $this->factors[ $from ] ?? 1.0;
		$to_f    = $this->factors[ $to ]   ?? 1.0;
		$from_sym = Unitwise_Registry::get_symbol( $from );
		$to_sym   = Unitwise_Registry::get_symbol( $to );
		$ratio    = $to_f / $from_f;

		if ( $ratio >= 1 ) {
			return sprintf( '%s = %s ÷ %s', $to_sym, $from_sym, $this->format_result( $ratio, 8 ) );
		}
		return sprintf( '%s = %s × %s', $to_sym, $from_sym, $this->format_result( 1 / $ratio, 8 ) );
	}

	/**
	 * Worked example string. e.g. "15 g ÷ 1,000 = 0.015 kg"
	 */
	public function get_worked_example( float $value, string $from, string $to ): string {
		$result   = $this->convert( $value, $from, $to );
		$from_sym = Unitwise_Registry::get_symbol( $from );
		$to_sym   = Unitwise_Registry::get_symbol( $to );
		$from_f   = $this->factors[ $from ] ?? 1.0;
		$to_f     = $this->factors[ $to ]   ?? 1.0;
		$ratio    = $to_f / $from_f;

		if ( $ratio >= 1 ) {
			$op  = '÷';
			$num = $this->format_result( $ratio, 6 );
		} else {
			$op  = '×';
			$num = $this->format_result( 1 / $ratio, 6 );
		}

		return sprintf(
			'%s %s %s %s = %s %s',
			$this->format_result( $value, 4 ),
			$from_sym,
			$op,
			$num,
			$this->format_result( $result, 6 ),
			$to_sym
		);
	}

	/**
	 * Pick sensible table input values based on the scale of the from-unit.
	 */
	public static function default_table_values( string $from_slug, string $category = 'weight' ): array {
		$factors = Unitwise_Registry::get_factors( $category );
		$f       = $factors[ $from_slug ] ?? 1.0;

		if ( $f <= 1.0e-4 ) {
			// microgram / nanogram range
			return [ 1, 10, 100, 500, 1000, 5000, 10000, 50000, 100000 ];
		}
		if ( $f <= 1.0 ) {
			// milligram / gram range
			return [ 1, 2, 5, 10, 25, 50, 100, 250, 500, 1000 ];
		}
		if ( $f <= 1000.0 ) {
			// kilogram / pound / ounce range
			return [ 0.1, 0.25, 0.5, 1, 2, 5, 10, 25, 50, 100 ];
		}
		// stone / ton range
		return [ 0.1, 0.25, 0.5, 1, 2, 5, 10, 25, 50, 100 ];
	}

	// -------------------------------------------------------------------------
	// Public helpers (used by schema / templates)

	public function get_factors(): array {
		return $this->factors;
	}
}
