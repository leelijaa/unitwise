/**
 * Gutenberg editor view for unitwise/converter — plain JS, no build step.
 *
 * Uses wp globals instead of ES module imports so this file works without
 * running @wordpress/scripts build.  edit.asset.php declares the dependencies.
 */
( function () {
	'use strict';

	var el               = wp.element.createElement;
	var Fragment         = wp.element.Fragment;
	var __               = wp.i18n.__;
	var useBlockProps    = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody        = wp.components.PanelBody;
	var TextControl      = wp.components.TextControl;
	var RangeControl     = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;

	function Edit( props ) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;
		var from          = attributes.from;
		var to            = attributes.to;
		var defaultValue  = attributes.defaultValue;
		var blockProps    = useBlockProps();

		return el( Fragment, null,
			el( InspectorControls, null,
				el( PanelBody, { title: __( 'Converter Settings', 'unitwise-converters' ) },
					el( TextControl, {
						label:    __( 'From unit slug', 'unitwise-converters' ),
						help:     __( 'e.g. grams — leave blank to use post meta', 'unitwise-converters' ),
						value:    from,
						onChange: function ( val ) { setAttributes( { from: val } ); },
					} ),
					el( TextControl, {
						label:    __( 'To unit slug', 'unitwise-converters' ),
						help:     __( 'e.g. kilograms — leave blank to use post meta', 'unitwise-converters' ),
						value:    to,
						onChange: function ( val ) { setAttributes( { to: val } ); },
					} ),
					el( RangeControl, {
						label:    __( 'Default input value', 'unitwise-converters' ),
						value:    defaultValue,
						onChange: function ( val ) { setAttributes( { defaultValue: val } ); },
						min:      0.001,
						max:      10000,
						step:     0.1,
					} )
				)
			),
			el( 'div', blockProps,
				el( ServerSideRender, {
					block:      'unitwise/converter',
					attributes: attributes,
				} )
			)
		);
	}

	wp.blocks.registerBlockType( 'unitwise/converter', { edit: Edit } );
} )();
