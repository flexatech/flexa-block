/**
 * Tab block — editor component (child of flexa/tabs).
 *
 * Each tab edits its label + icon (in the inspector), an optional line of default
 * text, and any blocks dropped into its InnerBlocks area below. The parent Tabs
 * block builds the nav from these labels and shows one tab at a time.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps, InnerBlocks, RichText } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

import { IconPicker, useBlockId } from '@components';
import { cn } from '@utils';
import type { EditProps, IconValue, TabAttributes } from '../../types';

/**
 * Tab edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TabAttributes > ): JSX.Element {
	const { label, icon, text, blockId } = attributes;

	useBlockId( clientId, blockId, setAttributes );

	const blockProps = useBlockProps( {
		className: cn( 'flexa-tab', blockId && `flexa-tab-${ blockId }` ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-tab__blocks' },
		{ renderAppender: InnerBlocks.ButtonBlockAppender }
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-tab-inspector">
					<PanelBody title={ __( 'Tab', 'flexa-block' ) } initialOpen={ true }>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Tab label', 'flexa-block' ) }
							value={ label ?? '' }
							onChange={ ( v: string ) => setAttributes( { label: v } ) }
						/>
						<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ icon || {} } onChange={ ( v: IconValue ) => setAttributes( { icon: v } ) } />
					</PanelBody>
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="p"
					className="flexa-tab__text"
					value={ text ?? '' }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
					onChange={ ( v: string ) => setAttributes( { text: v } ) }
					placeholder={ __( 'Optional intro text for this tab…', 'flexa-block' ) }
				/>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
