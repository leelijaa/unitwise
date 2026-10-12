/**
 * UnitWise converter — live JS hydration.
 *
 * No jQuery. Reads factors from unitwiseData (localized by class-assets.php).
 * Works alongside the server-rendered <output> — if JS is off, the PHP result shows.
 */

( function () {
	'use strict';

	const data = window.unitwiseData;
	if ( ! data || ! data.factors ) return;

	const { factors, decimals } = data;

	/**
	 * Convert value × factors[from] ÷ factors[to].
	 * Mirrors Unitwise_Engine::convert() exactly.
	 */
	function convert( value, from, to ) {
		const f = factors[ from ];
		const t = factors[ to ];
		if ( f === undefined || t === undefined ) return 0;
		return value * f / t;
	}

	/**
	 * Format a float for display — matches Unitwise_Engine::format_result().
	 */
	function formatResult( value, dp ) {
		dp = dp ?? decimals;
		if ( value === 0 ) return '0';
		const abs = Math.abs( value );
		if ( abs < 0.0001 || abs >= 10_000_000 ) {
			return value.toExponential( 6 );
		}
		const str = value.toFixed( dp );
		// Trim trailing zeros after decimal point
		return str.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
	}

	/**
	 * Attach live conversion to a single .unitwise-converter-block element.
	 */
	function initBlock( block ) {
		const from   = block.dataset.from;
		const to     = block.dataset.to;
		const input  = block.querySelector( '.unitwise-input' );
		const output = block.querySelector( '.unitwise-result' );

		if ( ! from || ! to || ! input || ! output ) return;

		function update() {
			const val = parseFloat( input.value );
			if ( isNaN( val ) ) {
				output.textContent = '';
				return;
			}
			output.textContent = formatResult( convert( val, from, to ) );
		}

		input.addEventListener( 'input', update );
		update(); // Sync on page load in case value differs from PHP default
	}

	// Init all converter blocks on the page
	document.querySelectorAll( '.unitwise-converter-block' ).forEach( initBlock );

	// Expose public API for custom integrations
	window.UnitWise = window.UnitWise || {};
	window.UnitWise.convert       = convert;
	window.UnitWise.formatResult  = formatResult;
	window.UnitWise.factors       = factors;
} )();
