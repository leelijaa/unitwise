<?php
/**
 * Unit registry — single source of truth for all units and categories.
 *
 * URL slugs (grams, kilograms, …) are used as array keys so they double as
 * the from/to values in URLs, meta, and JS — no mapping layer needed.
 *
 * PLUGIN LAYER: never move registry logic to the theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Registry {

	/**
	 * Conversion factors relative to the base unit (gram).
	 * Key = URL slug used in /{category}/{from}-to-{to}/ and stored in post meta.
	 */
	private static array $weight_factors = [
		'micrograms' => 1.0e-6,
		'milligrams' => 1.0e-3,
		'grams'      => 1.0,
		'kilograms'  => 1000.0,
		'ounces'     => 28.349523125,
		'pounds'     => 453.59237,
		'stones'     => 6350.29318,
		'tons'       => 1_000_000.0,   // metric tons
		'carats'     => 0.2,
	];

	/** Display labels keyed by URL slug. */
	private static array $labels = [
		'micrograms' => [ 'singular' => 'Microgram',  'plural' => 'Micrograms',  'symbol' => 'μg' ],
		'milligrams' => [ 'singular' => 'Milligram',  'plural' => 'Milligrams',  'symbol' => 'mg' ],
		'grams'      => [ 'singular' => 'Gram',       'plural' => 'Grams',       'symbol' => 'g'  ],
		'kilograms'  => [ 'singular' => 'Kilogram',   'plural' => 'Kilograms',   'symbol' => 'kg' ],
		'ounces'     => [ 'singular' => 'Ounce',      'plural' => 'Ounces',      'symbol' => 'oz' ],
		'pounds'     => [ 'singular' => 'Pound',      'plural' => 'Pounds',      'symbol' => 'lb' ],
		'stones'     => [ 'singular' => 'Stone',      'plural' => 'Stone',       'symbol' => 'st' ],
		'tons'       => [ 'singular' => 'Metric Ton', 'plural' => 'Metric Tons', 'symbol' => 't'  ],
		'carats'     => [ 'singular' => 'Carat',      'plural' => 'Carats',      'symbol' => 'ct' ],
	];

	/** Per-unit description used in the explainer section of each converter page. */
	private static array $descriptions = [
		'micrograms' => 'A microgram (μg) is one millionth of a gram, used in pharmacology and nutrition labeling for trace quantities.',
		'milligrams' => 'A milligram (mg) is one thousandth of a gram, commonly used for medication dosages and small chemical quantities.',
		'grams'      => 'The gram (g) is the base unit of mass in the metric system, equal to one thousandth of a kilogram.',
		'kilograms'  => 'The kilogram (kg) is the SI base unit of mass, defined as exactly 1,000 grams.',
		'ounces'     => 'The ounce (oz) is an imperial unit equal to approximately 28.35 grams, used in the US and UK for food and postal weights.',
		'pounds'     => 'The pound (lb) is an imperial unit equal to 16 ounces or approximately 453.6 grams, widely used in the United States.',
		'stones'     => 'The stone (st) is a British imperial unit equal to 14 pounds or approximately 6.35 kg, used in the UK and Ireland for body weight.',
		'tons'       => 'A metric ton (tonne, t) equals exactly 1,000 kilograms or about 2,204.6 pounds, used in international trade and industry.',
		'carats'     => 'The carat (ct) is the unit of mass for gemstones equal to exactly 0.2 grams or 200 milligrams.',
	];

	/** Category definitions. Add new categories here; no other code changes needed. */
	private static array $categories = [
		'weight' => [
			'name'        => 'Weight & Mass',
			'slug'        => 'weight',
			'description' => 'Convert between grams, kilograms, pounds, ounces, stones, and more.',
			'base_unit'   => 'grams',
			'icon'        => '⚖️',
		],
	];

	// -------------------------------------------------------------------------
	// Factors

	public static function get_factors( string $category = 'weight' ): array {
		return match ( $category ) {
			'weight' => self::$weight_factors,
			default  => [],
		};
	}

	public static function unit_exists( string $slug, string $category = 'weight' ): bool {
		return isset( self::get_factors( $category )[ $slug ] );
	}

	public static function get_unit_category( string $slug ): string {
		foreach ( array_keys( self::$categories ) as $cat_slug ) {
			if ( isset( self::get_factors( $cat_slug )[ $slug ] ) ) {
				return $cat_slug;
			}
		}
		return 'weight';
	}

	public static function get_all_slugs( string $category = 'weight' ): array {
		return array_keys( self::get_factors( $category ) );
	}

	// -------------------------------------------------------------------------
	// Labels

	public static function get_label( string $slug, bool $plural = false ): string {
		if ( ! isset( self::$labels[ $slug ] ) ) {
			return $slug;
		}
		return $plural ? self::$labels[ $slug ]['plural'] : self::$labels[ $slug ]['singular'];
	}

	public static function get_symbol( string $slug ): string {
		return self::$labels[ $slug ]['symbol'] ?? $slug;
	}

	public static function get_description( string $slug ): string {
		return self::$descriptions[ $slug ] ?? '';
	}

	// -------------------------------------------------------------------------
	// Categories

	public static function get_categories(): array {
		return self::$categories;
	}

	public static function get_category( string $slug ): array {
		return self::$categories[ $slug ] ?? [];
	}
}
