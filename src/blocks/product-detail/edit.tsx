/**
 * Product Details block — editor component.
 *
 * Assembles the product-detail-specific panels (./panels) with the shared
 * inspector panels (@components) for spacing / background / border / shadow /
 * position / visibility. On the front end this is a dynamic WooCommerce block
 * that reads the current product's Description / Additional information / Reviews
 * into tabs; in the editor it previews a static, interactive tab UI with
 * placeholder content. Inline styles mirror the PHP CSS generator, and nothing is
 * styled by default so the tabs inherit the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	PaginationPanel,
	useBlockId,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	type CssProps,
} from '@utils';
import {
	ProductDetailSettingsPanel,
	ProductDetailTabTitlePanel,
	ProductDetailContentPanel,
	ProductDetailAdditionalPanel,
	ProductDetailReviewsPanel,
} from './panels';
import type { DeviceKey, EditProps, ProductDetailAttributes } from '../../types';

/** One previewable tab: its enable flag, label and placeholder content. */
interface PreviewTab {
	key: string;
	enabled: boolean;
	label: string;
	content: string;
}

/** Wrapper preview: spacing, background, border, shadow, overflow. */
const buildWrapperStyle = ( attributes: ProductDetailAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background, border, boxShadow, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, effective( border, device ) );

	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	if ( adv.overflow ) s.overflow = adv.overflow;

	return s;
};

/** Nav preview: column gap between tab titles. */
const buildNavStyle = ( attributes: ProductDetailAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.tabGap, device );
	if ( gap.value ) s.columnGap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Tab-title preview: typography, padding and idle text/background colours. */
const buildTabStyle = ( attributes: ProductDetailAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.tabTitleTypography, device ) );
	const padding = spacingShorthand( rawDevice( attributes.tabTitlePadding, device ) );
	if ( padding ) s.padding = padding;
	if ( attributes.tabTitleColor?.light ) s.color = attributes.tabTitleColor.light;
	if ( attributes.tabTitleBg?.light ) s.backgroundColor = attributes.tabTitleBg.light;
	return s;
};

/** Active-tab preview: active text + background colours. */
const buildActiveStyle = ( attributes: ProductDetailAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.tabActiveColor?.light ) s.color = attributes.tabActiveColor.light;
	if ( attributes.tabActiveBg?.light ) s.backgroundColor = attributes.tabActiveBg.light;
	return s;
};

/** Content preview: typography, text colour and padding. */
const buildContentStyle = ( attributes: ProductDetailAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.contentTypography, device ) );
	if ( attributes.contentColor?.light ) s.color = attributes.contentColor.light;
	const padding = spacingShorthand( rawDevice( attributes.contentPadding, device ) );
	if ( padding ) s.padding = padding;
	return s;
};

/**
 * Product Details edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductDetailAttributes > ): JSX.Element {
	const { className, responsiveVisibility, showDescriptionTab, showAdditionalTab, showReviewsTab } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const [ preview, setPreview ] = useState< number >( 0 );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-detail',
			blockId && `flexa-product-detail-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real tabs.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const tabs: PreviewTab[] = [
		{
			key: 'description',
			enabled: showDescriptionTab !== false,
			label: __( 'Description', 'flexa-block' ),
			content: __( 'The product description shows here on the front end. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'flexa-block' ),
		},
		{
			key: 'additional',
			enabled: showAdditionalTab !== false,
			label: __( 'Additional information', 'flexa-block' ),
			content: __( 'A table of product attributes (weight, dimensions, variations…) shows here on the front end.', 'flexa-block' ),
		},
		{
			key: 'reviews',
			enabled: showReviewsTab !== false,
			label: __( 'Reviews', 'flexa-block' ),
			content: __( 'A summary of the product’s average rating and review count shows here on the front end.', 'flexa-block' ),
		},
	];

	const enabled = tabs.filter( ( t ) => t.enabled );
	const active = Math.min( Math.max( preview, 0 ), Math.max( enabled.length - 1, 0 ) );

	const navStyle = buildNavStyle( attributes, device );
	const tabStyleObj = buildTabStyle( attributes, device );
	const activeStyleObj = buildActiveStyle( attributes );
	const contentStyle = buildContentStyle( attributes, device );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-detail-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductDetailSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductDetailTabTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDetailContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDetailAdditionalPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDetailReviewsPanel attributes={ attributes } setAttributes={ setAttributes } />
							<PaginationPanel
								attributes={ attributes }
								setAttributes={ setAttributes }
								perPage={ attributes.reviewsPerPage ?? 5 }
								onPerPage={ ( v ) => setAttributes( { reviewsPerPage: v } ) }
								perLoad={ attributes.reviewsLoadMore ?? 5 }
								onPerLoad={ ( v ) => setAttributes( { reviewsLoadMore: v } ) }
								typeHelp={ __( 'How the product Reviews tab list is paginated.', 'flexa-block' ) }
							/>
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

			<div { ...blockProps }>
				{ enabled.length > 0 ? (
					<>
						<ul className="flexa-product-detail__nav" role="tablist" style={ navStyle }>
							{ enabled.map( ( tab, index ) => {
								const isActive = index === active;
								return (
									<li key={ tab.key }>
										<button
											type="button"
											className={ cn( 'flexa-product-detail__tab', isActive && 'is-active' ) }
											role="tab"
											aria-selected={ isActive }
											onClick={ () => setPreview( index ) }
											style={ isActive ? { ...tabStyleObj, ...activeStyleObj } : tabStyleObj }
										>
											{ tab.label }
										</button>
									</li>
								);
							} ) }
						</ul>
						<div className="flexa-product-detail__panel is-active">
							<div className="flexa-product-detail__content" style={ contentStyle }>
								<p>{ enabled[ active ]?.content }</p>
							</div>
						</div>
					</>
				) : (
					<p>{ __( 'Enable at least one tab to show the product details.', 'flexa-block' ) }</p>
				) }
			</div>
		</>
	);
}
