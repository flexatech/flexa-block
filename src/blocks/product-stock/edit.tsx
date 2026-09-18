/**
 * Product Stock block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for typography / spacing / background / border / shadow /
 * position / visibility / animation.
 *
 * There is no product in the editor, so the canvas previews ONE state at a time
 * and a picker above the canvas chooses which — otherwise the out-of-stock
 * wording and colours could never be seen while they were being set. That
 * choice is component state, not a block attribute: it decides nothing on the
 * front end, where WooCommerce resolves the real availability.
 *
 * Inline styles mirror the PHP CSS generator. Nothing is colored by default so
 * the status inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';

import {
	InspectorTabs,
	TypographyPanel,
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
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyTypography,
	wrapperPreviewStyle,
	type CssProps,
} from '@utils';
import {
	ProductStockSettingsPanel,
	ProductStockTextPanel,
	ProductStockLayoutPanel,
	ProductStockColorsPanel,
	ProductStockBadgePanel,
} from './panels';
import type { DeviceKey, EditProps, ProductStockAttributes } from '../../types';

/** Placeholder quantity shown in the editor (WooCommerce reads the real one on the front end). */
const PREVIEW_QUANTITY = 3;

/** The four states the front end can resolve, in the order the picker lists them. */
const PREVIEW_STATES = [ 'in-stock', 'low-stock', 'on-backorder', 'out-of-stock' ] as const;

type PreviewState = ( typeof PREVIEW_STATES )[ number ];

/** Per-state glyph, matching the paths render.php prints. */
const STATE_ICON: Record< PreviewState, JSX.Element > = {
	'in-stock': <path d="M20 6 9 17l-5-5" />,
	'low-stock': (
		<>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 7v5.5l3.5 2" />
		</>
	),
	'on-backorder': (
		<>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 7v5.5l3.5 2" />
		</>
	),
	'out-of-stock': <path d="M18 6 6 18M6 6l12 12" />,
};

/**
 * Status row preview: typography, gap, the in-stock colour pair and — in badge
 * mode only, matching the generator's gate — the badge padding + radius.
 */
const buildStatusStyle = ( attributes: ProductStockAttributes, device: DeviceKey, state: PreviewState ): CssProps => {
	const s: CssProps = {};

	applyTypography( s, rawDevice( attributes.typography, device ) );

	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );

	const colors = {
		'in-stock': [ attributes.inStockColor, attributes.inStockBackground ],
		'low-stock': [ attributes.lowStockColor, attributes.lowStockBackground ],
		'on-backorder': [ attributes.backorderColor, attributes.backorderBackground ],
		'out-of-stock': [ attributes.outOfStockColor, attributes.outOfStockBackground ],
	}[ state ];

	if ( colors[ 0 ]?.light ) s.color = colors[ 0 ].light;
	if ( colors[ 1 ]?.light ) s.backgroundColor = colors[ 1 ].light;

	if ( 'badge' === attributes.displayType ) {
		const padding = spacingShorthand( effective( attributes.badgePadding, device ) );
		if ( padding ) s.padding = padding;

		const radius = effective( attributes.badgeRadius, device );
		if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	}

	return s;
};

/** Icon preview: size only — the colour comes from the status row via currentColor. */
const buildIconStyle = ( attributes: ProductStockAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/**
 * Product Stock edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductStockAttributes > ): JSX.Element {
	const { displayType, showIcon, showQuantity, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const [ previewState, setPreviewState ] = useState< PreviewState >( 'in-stock' );

	// Low stock is still "in stock" — it reuses the in-stock wording and only
	// differs in colour, exactly as render.php resolves it.
	const stateLabels: Record< PreviewState, string > = {
		'in-stock': __( 'In stock', 'flexa-block' ),
		'low-stock': __( 'Low stock', 'flexa-block' ),
		'on-backorder': __( 'On backorder', 'flexa-block' ),
		'out-of-stock': __( 'Out of stock', 'flexa-block' ),
	};
	const stateText: Record< PreviewState, string | undefined > = {
		'in-stock': attributes.inStockText,
		'low-stock': attributes.inStockText,
		'on-backorder': attributes.backorderText,
		'out-of-stock': attributes.outOfStockText,
	};

	useBlockId( clientId, blockId, setAttributes );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-stock',
			'badge' === displayType && 'flexa-product-stock--badge',
			blockId && `flexa-product-stock-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperPreviewStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real status.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon="visibility"
						label={ __( 'Preview state', 'flexa-block' ) }
						text={ stateLabels[ previewState ] }
						controls={ PREVIEW_STATES.map( ( state ) => ( {
							title: stateLabels[ state ],
							isActive: state === previewState,
							onClick: () => setPreviewState( state ),
						} ) ) }
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<div className="flexa-inspector flexa-product-stock-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductStockSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductStockTextPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductStockLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductStockColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductStockBadgePanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<span
					className={ `flexa-product-stock__status flexa-product-stock__status--${ previewState }` }
					style={ buildStatusStyle( attributes, device, previewState ) }
				>
					{ !! showIcon && (
						<svg
							className="flexa-product-stock__icon"
							style={ buildIconStyle( attributes, device ) }
							viewBox="0 0 24 24"
							xmlns="http://www.w3.org/2000/svg"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							strokeLinecap="round"
							strokeLinejoin="round"
							aria-hidden="true"
							focusable="false"
						>
							{ STATE_ICON[ previewState ] }
						</svg>
					) }
					<span className="flexa-product-stock__text">{ stateText[ previewState ] || '' }</span>
					{ !! showQuantity && 'out-of-stock' !== previewState && (
						<span className="flexa-product-stock__qty">{ `(${ 'low-stock' === previewState ? 1 : PREVIEW_QUANTITY })` }</span>
					) }
				</span>
			</div>
		</>
	);
}
