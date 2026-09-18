/**
 * Product Meta block — editor component.
 *
 * A container of flexa/product-field rows. Inserting it lays out the three
 * fields a product meta list usually carries; from there the rows are ordinary
 * blocks — drag them, delete them, duplicate them, add a fourth.
 *
 * The canvas mirrors what the CSS generator emits for the LIST (row gap, label
 * column, divider, and the label / value defaults) through a scoped <style>,
 * because those land on the rows via a descendant selector and cannot be
 * expressed as inline styles on the container. Each row mirrors its own
 * overrides itself.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	useBlockId,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import { cn, visibilityClasses, effective, applyTypography, wrapperPreviewStyle } from '@utils';
import type { CssProps } from '@utils';
import {
	ProductMetaLayoutPanel,
	ProductMetaLabelStylePanel,
	ProductMetaValueStylePanel,
	ProductMetaDividerPanel,
} from './panels';
import type { DeviceKey, EditProps, LengthValue, ProductMetaAttributes } from '../../types';

/** Only product fields belong in the list. */
const ALLOWED_BLOCKS = [ 'flexa/product-field' ];

/** What a fresh list holds — the three fields a meta list usually carries. */
const TEMPLATE: Array< [ string, Record< string, unknown > ] > = [
	[ 'flexa/product-field', { field: 'sku', label: 'SKU:' } ],
	[ 'flexa/product-field', { field: 'categories', label: 'Categories:' } ],
	[ 'flexa/product-field', { field: 'tags', label: 'Tags:' } ],
];

/** A responsive length as a CSS string, or '' when the user left it alone. */
const lengthValue = ( value: LengthValue | undefined ): string => ( value?.value ? `${ value.value }${ value.unit || 'px' }` : '' );

/** Wrapper preview: the shared foundation plus the gap between rows. */
const buildWrapperStyle = ( attributes: ProductMetaAttributes, device: DeviceKey ): CssProps => {
	const s = wrapperPreviewStyle( attributes, device );
	const gap = lengthValue( effective( attributes.rowGap, device ) );
	if ( gap ) s.rowGap = gap;
	return s;
};

/** One `prop: value;` line, or '' when there is nothing to declare. */
const decl = ( prop: string, value?: string ): string => ( value ? `${ prop }:${ value };` : '' );

/** CSS declarations as a block body, from a style object. */
const body = ( style: CssProps ): string =>
	Object.entries( style )
		.map( ( [ key, value ] ) => `${ key.replace( /[A-Z]/g, ( c ) => '-' + c.toLowerCase() ) }:${ value };` )
		.join( '' );

/**
 * Mirror the descendant rules the generator emits for the rows. Inline styles
 * cannot reach a child block, so the canvas needs a scoped stylesheet — the
 * same shape the front end gets, minus the dark-mode branch.
 */
const buildRowCss = ( attributes: ProductMetaAttributes, device: DeviceKey, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}

	const wrap = `.flexa-product-meta-${ blockId }`;
	const rules: string[] = [];

	const labelGap = lengthValue( effective( attributes.labelGap, device ) );
	if ( labelGap ) {
		rules.push( `${ wrap } .flexa-product-field{column-gap:${ labelGap };}` );
	}

	const labelStyle: CssProps = {};
	applyTypography( labelStyle, effective( attributes.labelTypography, device ) );
	if ( attributes.labelColor?.light ) labelStyle.color = attributes.labelColor.light;
	const labelWidth = lengthValue( effective( attributes.labelWidth, device ) );
	if ( labelWidth ) labelStyle.flex = `0 0 ${ labelWidth }`;
	if ( Object.keys( labelStyle ).length ) {
		rules.push( `${ wrap } .flexa-product-field__label{${ body( labelStyle ) }}` );
	}

	const valueStyle: CssProps = {};
	applyTypography( valueStyle, effective( attributes.valueTypography, device ) );
	if ( attributes.valueColor?.light ) valueStyle.color = attributes.valueColor.light;
	if ( Object.keys( valueStyle ).length ) {
		const declarations = body( valueStyle );
		rules.push( `${ wrap } .flexa-product-field__value{${ declarations }}` );
		rules.push( `${ wrap } .flexa-product-field__term{${ declarations }}` );
	}

	if ( attributes.showDivider ) {
		const width = lengthValue( effective( attributes.dividerWidth, device ) );
		const divider =
			'border-top-style:solid;' + decl( 'border-top-width', width ) + decl( 'border-top-color', attributes.dividerColor?.light );
		rules.push( `${ wrap } > .flexa-product-field + .flexa-product-field{${ divider }}` );
	}

	return rules.join( '' );
};

/**
 * Product Meta edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductMetaAttributes > ): JSX.Element {
	const { className, responsiveVisibility, showDivider } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-meta',
			showDivider && 'flexa-product-meta--divided',
			blockId && `flexa-product-meta-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateLock: false,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real rows.
	if ( attributes.isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const rowCss = buildRowCss( attributes, device, blockId );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-meta-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductMetaLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductMetaLabelStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductMetaValueStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductMetaDividerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<PositionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			{ rowCss && <style>{ rowCss }</style> }
			<div { ...innerBlocksProps } />
		</>
	);
}
