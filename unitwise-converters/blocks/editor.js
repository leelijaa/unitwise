/**
 * UnitWise block editor UI — no build step.
 *
 * Uses wp.* globals (enqueued as dependencies in class-blocks.php).
 * All hub homepage blocks are dynamic (save returns null; render.php owns the
 * front-end markup). Text fields use inline RichText where sensible; fully
 * dynamic blocks (category grid, recent posts) use a ServerSideRender preview
 * with sidebar controls.
 */
( function ( wp ) {
	var el                = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps     = wp.blockEditor.useBlockProps;
	var RichText          = wp.blockEditor.RichText;
	var c   = wp.components;
	var SSR = wp.serverSideRender;
	var __  = wp.i18n.__;

	// SSR wrapper — pointer-events:none so click selects the block, not its children.
	function preview( name, attributes ) {
		return el( 'div', { style: { pointerEvents: 'none' } },
			el( SSR, { block: name, attributes: attributes } )
		);
	}

	function setter( props, key ) {
		return function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); };
	}

	// ── Hub Hero ─────────────────────────────────────────────────────────────────

	registerBlockType( 'unitwise/hub-hero', {
		apiVersion: 3,
		attributes: {
			badge:      { type: 'string', default: 'Free & No Sign-up Required' },
			headline:   { type: 'string', default: 'Free Online' },
			accentText: { type: 'string', default: 'Unit Converters' },
			lead:       { type: 'string', default: 'Instant, accurate conversions for weight, mass, and more. Beautifully simple, completely free.' },
		},
		edit: function ( props ) {
			var a   = props.attributes;
			var set = setter.bind( null, props );
			return el( 'div', useBlockProps( { className: 'uw-hero-editor' } ),
				el( InspectorControls, {},
					el( c.PanelBody, { title: __( 'Hero', 'unitwise-converters' ), initialOpen: true },
						el( c.TextControl, { label: __( 'Badge text', 'unitwise-converters' ), value: a.badge, onChange: set( 'badge' ) } )
					)
				),
				el( 'div', { className: 'uw-hero-editor__inner' },
					el( RichText, {
						tagName: 'span', className: 'uw-hero-editor__badge',
						value: a.badge, onChange: set( 'badge' ),
						placeholder: __( 'Badge text…', 'unitwise-converters' ), allowedFormats: [],
					} ),
					el( 'div', { className: 'uw-hero-editor__title-row' },
						el( RichText, {
							tagName: 'h1', className: 'uw-hero-editor__headline',
							value: a.headline, onChange: set( 'headline' ),
							placeholder: __( 'Headline…', 'unitwise-converters' ), allowedFormats: [],
						} ),
						el( RichText, {
							tagName: 'span', className: 'uw-hero-editor__accent',
							value: a.accentText, onChange: set( 'accentText' ),
							placeholder: __( 'Accent text…', 'unitwise-converters' ), allowedFormats: [],
						} )
					),
					el( RichText, {
						tagName: 'p', className: 'uw-hero-editor__lead',
						value: a.lead, onChange: set( 'lead' ),
						placeholder: __( 'Lead paragraph…', 'unitwise-converters' ),
					} ),
					el( 'div', { className: 'uw-hero-editor__stats' },
						el( 'span', { className: 'uw-hero-editor__stat' }, __( '41+ Converters', 'unitwise-converters' ) ),
						el( 'span', { className: 'uw-hero-editor__stat' }, __( '1 Category', 'unitwise-converters' ) ),
						el( 'span', { className: 'uw-hero-editor__stat' }, __( '100% Free', 'unitwise-converters' ) )
					),
					el( 'p', { className: 'uw-hero-editor__note' }, __( '↑ Stats are live on the front end', 'unitwise-converters' ) )
				)
			);
		},
		save: function () { return null; },
	} );

	// ── Hub Category Grid ────────────────────────────────────────────────────────

	registerBlockType( 'unitwise/hub-category-grid', {
		apiVersion: 3,
		attributes: {
			heading:    { type: 'string', default: 'Browse by Category' },
			subheading: { type: 'string', default: 'Pick a measurement type to see all available converters.' },
		},
		edit: function ( props ) {
			var a   = props.attributes;
			var set = setter.bind( null, props );
			return el( 'div', useBlockProps(),
				el( InspectorControls, {},
					el( c.PanelBody, { title: __( 'Category grid', 'unitwise-converters' ), initialOpen: true },
						el( c.TextControl, { label: __( 'Heading', 'unitwise-converters' ), value: a.heading, onChange: set( 'heading' ) } ),
						el( c.TextareaControl, { label: __( 'Subheading', 'unitwise-converters' ), value: a.subheading, onChange: set( 'subheading' ), rows: 2 } )
					)
				),
				preview( 'unitwise/hub-category-grid', a )
			);
		},
		save: function () { return null; },
	} );

	// ── Hub Newsletter ───────────────────────────────────────────────────────────

	registerBlockType( 'unitwise/hub-newsletter', {
		apiVersion: 3,
		attributes: {
			heading: { type: 'string', default: 'Stay in the Loop' },
			subtext: { type: 'string', default: 'Get notified when we add new converters, guides, and tools. No spam, unsubscribe any time.' },
		},
		edit: function ( props ) {
			var a   = props.attributes;
			var set = setter.bind( null, props );
			return el( 'div', useBlockProps( { className: 'uw-newsletter-editor' } ),
				el( 'div', { style: { background: '#f0f6ff', border: '1px dashed #0073aa', borderRadius: 8, padding: 20 } },
					el( RichText, {
						tagName: 'h2', className: 'uw-newsletter-editor__heading',
						value: a.heading, onChange: set( 'heading' ),
						placeholder: __( 'Newsletter heading…', 'unitwise-converters' ), allowedFormats: [],
					} ),
					el( RichText, {
						tagName: 'p', className: 'uw-newsletter-editor__sub',
						value: a.subtext, onChange: set( 'subtext' ),
						placeholder: __( 'Subtext…', 'unitwise-converters' ),
					} ),
					el( 'div', { style: { display: 'flex', gap: 8, marginTop: 12 } },
						el( 'span', { style: { flex: 1, background: '#fff', border: '1px solid #ccc', borderRadius: 4, padding: '8px 12px', fontSize: 13, color: '#aaa' } },
							__( 'you@example.com', 'unitwise-converters' ) ),
						el( 'span', { style: { background: '#0073aa', color: '#fff', borderRadius: 4, padding: '8px 16px', fontSize: 13 } },
							__( 'Subscribe', 'unitwise-converters' ) )
					)
				)
			);
		},
		save: function () { return null; },
	} );

	// ── Recent Posts ─────────────────────────────────────────────────────────────

	registerBlockType( 'unitwise/recent-posts', {
		apiVersion: 3,
		attributes: {
			heading:    { type: 'string',  default: 'From the Blog' },
			subheading: { type: 'string',  default: 'Tips, guides, and insights about unit conversion.' },
			count:      { type: 'integer', default: 3 },
		},
		edit: function ( props ) {
			var a   = props.attributes;
			var set = setter.bind( null, props );
			return el( 'div', useBlockProps(),
				el( InspectorControls, {},
					el( c.PanelBody, { title: __( 'Recent posts', 'unitwise-converters' ), initialOpen: true },
						el( c.TextControl,    { label: __( 'Heading', 'unitwise-converters' ),    value: a.heading,    onChange: set( 'heading' ) } ),
						el( c.TextareaControl, { label: __( 'Subheading', 'unitwise-converters' ), value: a.subheading, onChange: set( 'subheading' ), rows: 2 } ),
						el( c.RangeControl,   { label: __( 'Number of posts', 'unitwise-converters' ), value: a.count, min: 1, max: 6, onChange: set( 'count' ) } )
					)
				),
				preview( 'unitwise/recent-posts', a )
			);
		},
		save: function () { return null; },
	} );

} )( window.wp );
