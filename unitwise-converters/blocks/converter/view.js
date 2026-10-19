/**
 * UnitWise converter — live JS hydration.
 *
 * Reads factors from each block's data-factors attribute so multiple blocks
 * with different categories on one page each work independently.
 * Falls back to window.unitwiseData (set by class-assets.php) when the
 * attribute is absent.
 */

( function () {
	'use strict';

	/**
	 * Format a float for display — mirrors Unitwise_Engine::format_result().
	 */
	function formatResult( value, decimals ) {
		if ( value === 0 ) return '0';
		const abs = Math.abs( value );
		if ( abs < 0.0001 || abs >= 10_000_000 ) {
			return value.toExponential( 6 );
		}
		const str = value.toFixed( decimals );
		return str.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
	}

	/**
	 * Attach live conversion to a single .unitwise-converter-block element.
	 */
	function initBlock( block ) {
		const from     = block.dataset.from;
		const to       = block.dataset.to;
		const decimals = parseInt( block.dataset.decimals || '6', 10 );
		const input    = block.querySelector( '.unitwise-input' );
		const output   = block.querySelector( '.unitwise-result' );

		if ( ! from || ! to || ! input || ! output ) return;

		// Per-block factors (preferred) → global fallback
		let factors;
		try {
			factors = JSON.parse( block.dataset.factors || '{}' );
		} catch ( e ) {
			factors = {};
		}
		if ( ! factors[ from ] && window.unitwiseData && window.unitwiseData.factors ) {
			factors = window.unitwiseData.factors;
		}

		function update() {
			const val = parseFloat( input.value );
			if ( isNaN( val ) ) {
				output.textContent = '';
				return;
			}
			const f = factors[ from ];
			const t = factors[ to ];
			if ( f === undefined || t === undefined ) return;
			output.textContent = formatResult( val * f / t, decimals );
		}

		input.addEventListener( 'input', update );
		update(); // Sync on load in case value differs from PHP default

		// Prevent form submission creating indexable duplicate URLs
		const form = block.querySelector( '.unitwise-converter-form' );
		if ( form ) {
			form.addEventListener( 'submit', function ( e ) { e.preventDefault(); } );
		}
	}

	// Init all converter blocks on the page
	document.querySelectorAll( '.unitwise-converter-block' ).forEach( initBlock );

	// Expose public API for custom integrations
	window.UnitWise = window.UnitWise || {};
	window.UnitWise.formatResult = formatResult;
} )();
