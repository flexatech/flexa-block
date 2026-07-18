/**
 * Timeline block — editor component.
 *
 * Assembles the timeline-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas previews the timeline with inline styles that mirror
 * the PHP CSS generator; entry content (icon, date, title, description) is edited
 * in the Timeline panel. Nothing is coloured by default so the timeline inherits
 * the theme until the user picks a value.
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
	type CssProps,
} from '@utils';
import {
	TimelineItemsPanel,
	TimelineLayoutPanel,
	TimelineImagePanel,
	TimelineMarkerPanel,
	TimelineConnectorPanel,
	TimelineDatePanel,
	TimelineTitlePanel,
	TimelineDescriptionPanel,
} from './panels';
import type { DeviceKey, EditProps, TimelineItem, TimelineAttributes } from '../../types';

/** Wrapper preview: max width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const w = effective( attributes.maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );
	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, attributes.background );
	const adv = effective( attributes.advancedLayout, device );
	if ( adv.overflow ) s.overflow = adv.overflow;
	return s;
};

/** Item preview: gap between marker and content. */
const buildItemStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.markerGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Content preview: bottom gap to the next event + text alignment. */
const buildContentStyle = ( attributes: TimelineAttributes, device: DeviceKey, isLast: boolean ): CssProps => {
	const s: CssProps = {};
	if ( ! isLast ) {
		const gap = effective( attributes.itemGap, device );
		if ( gap.value ) s.paddingBottom = withUnit( gap.value, gap.unit || 'px' );
	}
	const align = attributes.contentAlign?.[ device ] || '';
	if ( align ) s.textAlign = align;
	return s;
};

/** Card preview: per-entry inner padding + border + shadow (excludes the inter-event gap). */
const buildCardStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const pad = spacingShorthand( effective( attributes.cardPadding, device ) );
	if ( pad ) s.padding = pad;
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** Image preview: chosen display width + horizontal alignment (via auto margins). */
const buildImageStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const w = effective( attributes.imageWidth, device );
	if ( w.value ) s.width = withUnit( w.value, w.unit || '%' );
	if ( attributes.imageAlign === 'center' ) {
		s.marginLeft = 'auto';
		s.marginRight = 'auto';
	} else if ( attributes.imageAlign === 'right' ) {
		s.marginLeft = 'auto';
		s.marginRight = '0';
	}
	return s;
};

/** Marker preview: size, background, icon colour. */
const buildMarkerStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.markerSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	if ( attributes.markerColor?.light ) s.background = attributes.markerColor.light;
	if ( attributes.markerIconColor?.light ) s.color = attributes.markerIconColor.light;
	return s;
};

/** Connector preview: thickness, style, colour (hidden when the toggle is off). */
const buildConnectorStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.connectorShow === false ) {
		s.display = 'none';
		return s;
	}
	const width = effective( attributes.connectorWidth, device );
	if ( width.value ) s.borderLeftWidth = withUnit( width.value, width.unit || 'px' );
	if ( attributes.connectorStyle ) s.borderLeftStyle = attributes.connectorStyle;
	if ( attributes.connectorColor?.light ) s.borderLeftColor = attributes.connectorColor.light;
	return s;
};

/** Date preview: typography + colour. */
const buildDateStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.dateTypography, device ) );
	if ( attributes.dateColor?.light ) s.color = attributes.dateColor.light;
	return s;
};

/** Title preview: typography + colour. */
const buildTitleStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) s.color = attributes.titleColor.light;
	return s;
};

/** Description preview: typography + colour. */
const buildDescStyle = ( attributes: TimelineAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.descriptionTypography, device ) );
	if ( attributes.descriptionColor?.light ) s.color = attributes.descriptionColor.light;
	return s;
};

/** The marker's inner content for one entry (icon markup / uploaded image, else empty). */
const MarkerInner = ( { item }: { item: TimelineItem } ): JSX.Element | null => {
	const icon = item.icon || {};
	if ( icon.markup ) {
		// `display:contents` removes this wrapper from the box tree so the inline SVG
		// sizes against the marker (60% rule in style.scss), matching the front end
		// where render.php injects the SVG directly into the marker.
		// eslint-disable-next-line react/no-danger
		return <span style={ { display: 'contents' } } dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img src={ icon.url } alt="" />;
	}
	return null;
};

/**
 * Timeline edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TimelineAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, markerShape, timelineLayout, datePosition, imagePosition } = attributes;
	const imgPos = imagePosition || 'top';
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: TimelineItem[] = Array.isArray( items ) ? items : [];

	const itemStyle = buildItemStyle( attributes, device );
	const dateStyle = buildDateStyle( attributes, device );
	const titleStyle = buildTitleStyle( attributes, device );
	const descStyle = buildDescStyle( attributes, device );
	const connectorStyle = buildConnectorStyle( attributes, device );
	const markerStyle = buildMarkerStyle( attributes, device );
	const imageStyle = buildImageStyle( attributes, device );
	const cardStyle = buildCardStyle( attributes, device );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-timeline',
			`flexa-timeline--${ timelineLayout || 'alternate' }`,
			`flexa-timeline--marker-${ markerShape || 'circle' }`,
			`flexa-timeline--date-${ datePosition || 'above' }`,
			blockId && `flexa-timeline-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real list.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="faq" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-timeline-inspector">
					<InspectorTabs
						layout={
							<>
								<TimelineItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineImagePanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TimelineMarkerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineConnectorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineDatePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TimelineDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-timeline__list">
					{ list.map( ( item, index ) => {
						const isLast = index === list.length - 1;
						const side = ( timelineLayout || 'alternate' ) === 'alternate'
							? ( index % 2 === 0 ? 'left' : 'right' )
							: ( timelineLayout || 'alternate' );
						return (
							<div key={ index } className={ cn( 'flexa-timeline__item', `is-${ side }` ) } style={ itemStyle }>
								<div className="flexa-timeline__aside">
									<span className="flexa-timeline__marker" style={ markerStyle }>
										<MarkerInner item={ item } />
									</span>
									{ ! isLast && <span className="flexa-timeline__connector" style={ connectorStyle } aria-hidden="true" /> }
								</div>
								<div className="flexa-timeline__content" style={ buildContentStyle( attributes, device, isLast ) }>
									<div className="flexa-timeline__card" style={ cardStyle }>
										{ item.image?.url && imgPos === 'top' && (
											<img className="flexa-timeline__image flexa-timeline__image--top" src={ item.image.url } alt={ item.image.alt || '' } style={ imageStyle } />
										) }
										{ ( item.date || '' ) && (
											<span className="flexa-timeline__date" style={ dateStyle }>
												{ item.date }
											</span>
										) }
										{ ( item.title || '' ) && (
											<span className="flexa-timeline__title" style={ titleStyle }>
												{ item.title }
											</span>
										) }
										{ ( item.description || '' ) && (
											<span className="flexa-timeline__description" style={ descStyle }>
												{ item.description }
											</span>
										) }
										{ item.image?.url && imgPos === 'bottom' && (
											<img className="flexa-timeline__image flexa-timeline__image--bottom" src={ item.image.url } alt={ item.image.alt || '' } style={ imageStyle } />
										) }
									</div>
								</div>
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
