/**
 * Text block — text-specific inspector panels.
 *
 * The shared panels (typography / text colours / effects / spacing / background
 * / border / shadow / position / visibility) come from @components; this file
 * covers the one part unique to the text block: content layout (tag, alignment,
 * drop cap) in the Layout tab. Nothing is styled by default — only values the
 * user picks produce CSS, so the text inherits the theme until then.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import { Segmented, useDevice, TEXT_TAG_OPTIONS, TEXT_ALIGN_OPTIONS } from '@components';
import type { PanelProps, TextAttributes } from '../../types';

type TextPanelProps = PanelProps< TextAttributes >;

/**
 * Layout panel — HTML tag, alignment and drop cap.
 */
export const TextContentPanel = ( { attributes, setAttributes }: TextPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { htmlTag, alignment, dropCap } = attributes;

	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Text', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'p' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ TEXT_ALIGN_OPTIONS }
				/>
			</div>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Drop cap', 'flexa-block' ) }
				help={ __( 'Enlarge the first letter of the text.', 'flexa-block' ) }
				checked={ !! dropCap }
				onChange={ ( v: boolean ) => setAttributes( { dropCap: v } ) }
			/>
		</PanelBody>
	);
};
