/**
 * Filter: Reset — editor component (child of flexa/post-filter).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, PanelBody, TextControl } from '@wordpress/components';

import { Segmented, VisibilityPanel, useBlockId, useDevice } from '@components';
import { cn } from '@utils';
import { FIELD_WIDTH_OPTIONS } from '@shared/filter-widths';
import { FilterFieldStylePanels, filterFieldCss } from '@shared/filter-field-style';
import type { EditProps, FilterResetAttributes } from '../../types';

/**
 * Filter: Reset edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< FilterResetAttributes > ): JSX.Element {
	const { blockId, text, variant, width } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const fieldCss = filterFieldCss( attributes, blockId || '', device );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-filter-field',
			'flexa-filter-field--reset',
			`flexa-filter-field--w${ width || 'auto' }`,
			blockId && `flexa-filter-field-${ blockId }`
		),
	} );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-filter-reset-inspector">
					<PanelBody title={ __( 'Reset', 'flexa-block' ) } initialOpen={ true }>
						<Notice status="info" isDismissible={ false }>
							{ __( 'On the front end this only appears once a visitor has searched or picked a term — with nothing applied there is nothing to clear. It always shows here so you can style it.', 'flexa-block' ) }
						</Notice>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Text', 'flexa-block' ) }
							value={ text ?? '' }
							onChange={ ( v: string ) => setAttributes( { text: v } ) }
						/>
						<Segmented
							label={ __( 'Style', 'flexa-block' ) }
							value={ variant || 'link' }
							onChange={ ( v ) => setAttributes( { variant: v as FilterResetAttributes[ 'variant' ] } ) }
							options={ [
								{ value: 'link', label: __( 'Link', 'flexa-block' ) },
								{ value: 'button', label: __( 'Button', 'flexa-block' ) },
							] }
						/>
						<Segmented
							label={ __( 'Width', 'flexa-block' ) }
							value={ width || 'auto' }
							onChange={ ( v ) => setAttributes( { width: v as FilterResetAttributes[ 'width' ] } ) }
							options={ FIELD_WIDTH_OPTIONS }
						/>
					</PanelBody>
					<FilterFieldStylePanels attributes={ attributes } setAttributes={ setAttributes } />
					<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ fieldCss && <style>{ fieldCss }</style> }
				<span
					className={ cn(
						'flexa-filter-reset__control',
						'button' === variant ? 'wp-element-button' : 'flexa-filter-reset__control--link'
					) }
				>
					{ text || __( 'Reset', 'flexa-block' ) }
				</span>
			</div>
		</>
	);
}
