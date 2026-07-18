/**
 * Counter block — editor component.
 *
 * Assembles the counter-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas previews the stat grid with inline styles that mirror
 * the PHP CSS generator; the numbers show their final value (the count-up is a
 * front-end concern, view.ts). Nothing is styled by default so the counter
 * inherits the theme until the user picks a value. Item content (icon, number,
 * label) is edited in the Stats panel.
 *
 * @package Flexa\Block
 */

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
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	CounterItemsPanel,
	CounterLayoutPanel,
	CounterIconPanel,
	CounterNumberPanel,
	CounterLabelPanel,
	CounterSeparatorPanel,
	CounterItemBoxPanel,
} from './panels';
import type { CounterAttributes, CounterItem, DeviceKey, EditProps } from '../../types';

/** The active device's alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: CounterAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || 'center';
};

/** Wrapper preview: spacing, background, border, shadow (the whole block). */
const buildWrapperStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** List preview: the column grid + the gap between stats. */
const buildListStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const cols = effective( attributes.columns, device ).value;
	if ( cols ) s.gridTemplateColumns = `repeat(${ parseInt( String( cols ), 10 ) || 1 }, minmax(0, 1fr))`;
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Item preview: alignment, content gap, background, padding, corner radius. */
const buildItemStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
	s.textAlign = align;
	const cg = effective( attributes.contentGap, device );
	if ( cg.value ) s.gap = withUnit( cg.value, cg.unit || 'px' );
	if ( attributes.itemBackground?.light ) s.background = attributes.itemBackground.light;
	const padding = spacingShorthand( rawDevice( attributes.itemPadding, device ) );
	if ( padding ) s.padding = padding;
	const radius = effective( attributes.itemBorderRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	return s;
};

/** Icon preview: square size + colour. */
const buildIconStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	if ( attributes.iconColor?.light ) s.color = attributes.iconColor.light;
	return s;
};

/** Number preview: typography + colour. */
const buildNumberStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.numberTypography, device ) );
	if ( attributes.numberColor?.light ) s.color = attributes.numberColor.light;
	return s;
};

/** Label preview: typography + colour. */
const buildLabelStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.labelTypography, device ) );
	if ( attributes.labelColor?.light ) s.color = attributes.labelColor.light;
	return s;
};

/** Divider preview: width + a top border (thickness / style / colour). */
const buildSeparatorStyle = ( attributes: CounterAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const width = effective( attributes.separatorWidth, device );
	if ( width.value ) s.width = withUnit( width.value, width.unit || 'px' );
	const weight = effective( attributes.separatorWeight, device );
	if ( weight.value ) s.borderTopWidth = withUnit( weight.value, weight.unit || 'px' );
	if ( attributes.separatorStyle ) s.borderTopStyle = attributes.separatorStyle;
	if ( attributes.separatorColor?.light ) s.borderTopColor = attributes.separatorColor.light;
	return s;
};

/**
 * Counter edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< CounterAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, showIcon, iconPosition, showSeparator } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: CounterItem[] = Array.isArray( items ) ? items : [];
	const iconInline = iconPosition === 'inline';

	const listStyle = buildListStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const iconStyle = buildIconStyle( attributes, device );
	const numberStyle = buildNumberStyle( attributes, device );
	const labelStyle = buildLabelStyle( attributes, device );
	const separatorStyle = buildSeparatorStyle( attributes, device );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-counter',
			iconInline && 'flexa-counter--icon-inline',
			blockId && `flexa-counter-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real grid.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="counter" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-counter-inspector">
					<InspectorTabs
						layout={
							<>
								<CounterItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CounterLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CounterIconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<CounterNumberPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CounterLabelPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CounterSeparatorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CounterItemBoxPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<Tag { ...blockProps }>
				<div className="flexa-counter__list" style={ listStyle }>
					{ list.map( ( item, index ) => {
						const num = item.number || {};
						const icon =
							showIcon !== false && item.icon?.markup ? (
								<span
									className="flexa-counter__icon"
									style={ iconStyle }
									// eslint-disable-next-line react/no-danger
									dangerouslySetInnerHTML={ { __html: item.icon.markup } }
								/>
							) : showIcon !== false && item.icon?.source === 'upload' && item.icon.url ? (
								<span className="flexa-counter__icon" style={ iconStyle }>
									<img src={ item.icon.url } alt="" />
								</span>
							) : null;

						const numberEl = (
							<div className="flexa-counter__number" style={ numberStyle }>
								{ num.prefix ? <span className="flexa-counter__affix">{ num.prefix }</span> : null }
								<span className="flexa-counter__value">{ num.value ?? '' }</span>
								{ num.suffix ? <span className="flexa-counter__affix">{ num.suffix }</span> : null }
							</div>
						);

						return (
							<div key={ index } className="flexa-counter__item" style={ itemStyle }>
								{ iconInline ? (
									<div className="flexa-counter__row">
										{ icon }
										{ numberEl }
									</div>
								) : (
									<>
										{ icon }
										{ numberEl }
									</>
								) }
								{ showSeparator && <span className="flexa-counter__separator" style={ separatorStyle } aria-hidden="true" /> }
								<span className="flexa-counter__label" style={ labelStyle }>
									{ item.label ?? '' }
								</span>
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
