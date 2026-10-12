/**
 * Gutenberg editor view for unitwise/converter.
 *
 * Uses ServerSideRender for an accurate preview — the same render.php
 * that produces the front-end output.
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { from, to, defaultValue } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Converter Settings', 'unitwise-converters' ) }>
					<TextControl
						label={ __( 'From unit slug', 'unitwise-converters' ) }
						help={ __( 'e.g. grams — leave blank to use post meta', 'unitwise-converters' ) }
						value={ from }
						onChange={ ( val ) => setAttributes( { from: val } ) }
					/>
					<TextControl
						label={ __( 'To unit slug', 'unitwise-converters' ) }
						help={ __( 'e.g. kilograms — leave blank to use post meta', 'unitwise-converters' ) }
						value={ to }
						onChange={ ( val ) => setAttributes( { to: val } ) }
					/>
					<RangeControl
						label={ __( 'Default input value', 'unitwise-converters' ) }
						value={ defaultValue }
						onChange={ ( val ) => setAttributes( { defaultValue: val } ) }
						min={ 0.001 }
						max={ 10000 }
						step={ 1 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="unitwise/converter"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
