/**
 * Product Name block — block-specific inspector panels.
 *
 * The shared panels (typography / text colours / effects / spacing / background
 * / border / shadow / position / visibility) come from @components; this file
 * covers the one part unique to the product-name block: content layout (tag,
 * alignment) plus the optional link to the product page. There is no user-entered
 * text — the title is read from the current product on the front end.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import { Segmented, useDevice, CONTENT_ALIGN_OPTIONS } from '@components';
import type { PanelProps, ProductNameAttributes } from '../../types';

type ProductNamePanelProps = PanelProps< ProductNameAttributes >;

/** HTML tag choices for the product title (headings + inline). */
const TAG_OPTIONS = [
	{ value: 'h1', label: 'H1' },
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
	{ value: 'h4', label: 'H4' },
	{ value: 'h5', label: 'H5' },
	{ value: 'h6', label: 'H6' },
	{ value: 'p', label: 'P' },
	{ value: 'span', label: 'Span' },
];

/**
 * Layout panel — HTML tag, alignment and link-to-product.
 */
export const ProductNameContentPanel = ( { attributes, setAttributes }: ProductNamePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { htmlTag, alignment, linkToProduct, linkTarget } = attributes;

	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Product Name', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'h2' }
				options={ TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Link to product', 'flexa-block' ) }
				checked={ !! linkToProduct }
				onChange={ ( v: boolean ) => setAttributes( { linkToProduct: v } ) }
			/>

			{ linkToProduct && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Open in new tab', 'flexa-block' ) }
					checked={ !! linkTarget }
					onChange={ ( v: boolean ) => setAttributes( { linkTarget: v } ) }
				/>
			) }
		</PanelBody>
	);
};
