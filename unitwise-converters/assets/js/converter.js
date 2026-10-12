/**
 * UnitWise — global converter script.
 *
 * Loaded only on converter pages (class-assets.php gates the enqueue).
 * Factors come from unitwiseData (wp_localize_script).
 * No jQuery. Deferred loading — runs after DOM is ready.
 *
 * Also bootstraps the swap button (reverse conversion) if present.
 */

( function () {
	'use strict';

	const d = window.unitwiseData;
	if ( ! d || ! d.factors ) return;

	const { factors, decimals } = d;

	function convert( value, from, to ) {
		const f = factors[ from ];
		const t = factors[ to ];
		if ( f === undefined || t === undefined ) return 0;
		return value * f / t;
	}

	function formatResult( value, dp ) {
		dp = dp !== undefined ? dp : decimals;
		if ( value === 0 ) return '0';
		const abs = Math.abs( value );
		if ( abs < 0.0001 || abs >= 10_000_000 ) {
			return value.toExponential( 6 );
		}
		return value.toFixed( dp ).replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
	}

	function initBlock( block ) {
		const from   = block.dataset.from;
		const to     = block.dataset.to;
		const input  = block.querySelector( '.unitwise-input' );
		const output = block.querySelector( '.unitwise-result' );

		if ( ! from || ! to || ! input || ! output ) return;

		function update() {
			const val = parseFloat( input.value );
			output.textContent = isNaN( val ) ? '' : formatResult( convert( val, from, to ) );
		}

		input.addEventListener( 'input', update );
		update();
	}

	document.querySelectorAll( '.unitwise-converter-block' ).forEach( initBlock );

	window.UnitWise = { convert, formatResult, factors };
} )();
