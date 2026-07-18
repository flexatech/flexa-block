/**
 * Steps block — editor component.
 *
 * Assembles the steps-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas previews the vertical timeline with inline styles that
 * mirror the PHP CSS generator; step content (marker, title, description) is
 * edited in the Steps panel. Nothing is coloured by default so the timeline
 * inherits the theme until the user picks a value.
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
	StepsItemsPanel,
	StepsMarkerPanel,
	StepsConnectorPanel,
	StepsLayoutPanel,
	StepsTitlePanel,
	StepsDescriptionPanel,
} from './panels';
import type { ColorPair, DeviceKey, EditProps, StepItem, StepsAttributes } from '../../types';

/** Wrapper preview: max width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: StepsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const w = effective( attributes.maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );
	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	const adv = effective( attributes.advancedLayout, device );
	if ( adv.overflow ) s.overflow = adv.overflow;
	return s;
};

/** Item preview: gap between marker and content. */
const buildItemStyle = ( attributes: StepsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.markerGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Content preview: bottom gap to the next step + text alignment. */
const buildContentStyle = ( attributes: StepsAttributes, device: DeviceKey, isLast: boolean ): CssProps => {
	const s: CssProps = {};
	if ( ! isLast ) {
		const gap = effective( attributes.itemGap, device );
		if ( gap.value ) s.paddingBottom = withUnit( gap.value, gap.unit || 'px' );
	}
	const align = attributes.contentAlign?.[ device ] || '';
	if ( align ) s.textAlign = align;
	return s;
};

/** Marker preview: size, background (status accent wins), number/icon colour. */
const buildMarkerStyle = ( attributes: StepsAttributes, device: DeviceKey, status?: string ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.markerSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	const statusColor: Record< string, ColorPair | undefined > = {
		done: attributes.doneColor,
		active: attributes.activeColor,
		upcoming: attributes.upcomingColor,
	};
	const accent = status ? statusColor[ status ]?.light : '';
	if ( accent ) s.background = accent;
	else if ( attributes.markerColor?.light ) s.background = attributes.markerColor.light;
	if ( attributes.markerTextColor?.light ) s.color = attributes.markerTextColor.light;
	return s;
};

/** Connector preview: thickness, style, colour (hidden when the toggle is off). */
const buildConnectorStyle = ( attributes: StepsAttributes, device: DeviceKey ): CssProps => {
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

/** Title preview: typography + colour. */
const buildTitleStyle = ( attributes: StepsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) s.color = attributes.titleColor.light;
	return s;
};

/** Description preview: typography + colour. */
const buildDescStyle = ( attributes: StepsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.descriptionTypography, device ) );
	if ( attributes.descriptionColor?.light ) s.color = attributes.descriptionColor.light;
	return s;
};

/** The marker's inner content for one step (number / icon / image). */
const MarkerInner = ( { item, position, globalType }: { item: StepItem; position: number; globalType: string } ): JSX.Element => {
	const type = item.markerType || globalType;
	if ( type === 'icon' ) {
		const icon = item.icon || {};
		if ( icon.markup ) {
			// eslint-disable-next-line react/no-danger
			return <span dangerouslySetInnerHTML={ { __html: icon.markup } } />;
		}
		if ( icon.source === 'upload' && icon.url ) {
			return <img src={ icon.url } alt="" />;
		}
		return <>{ position }</>;
	}
	if ( type === 'image' ) {
		if ( item.image?.url ) {
			return <img src={ item.image.url } alt="" />;
		}
		return <>{ position }</>;
	}
	return <>{ position }</>;
};

/**
 * Steps edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< StepsAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, markerShape, markerType } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: StepItem[] = Array.isArray( items ) ? items : [];
	const globalType = markerType || 'number';

	const itemStyle = buildItemStyle( attributes, device );
	const titleStyle = buildTitleStyle( attributes, device );
	const descStyle = buildDescStyle( attributes, device );
	const connectorStyle = buildConnectorStyle( attributes, device );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-steps',
			`flexa-steps--marker-${ markerShape || 'circle' }`,
			blockId && `flexa-steps-${ blockId }`,
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
				<div className="flexa-inspector flexa-steps-inspector">
					<InspectorTabs
						layout={
							<>
								<StepsItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<StepsLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<StepsMarkerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<StepsConnectorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<StepsTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<StepsDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-steps__list">
					{ list.map( ( item, index ) => {
						const isLast = index === list.length - 1;
						const status = item.status || '';
						return (
							<div key={ index } className={ cn( 'flexa-steps__item', status && `is-${ status }` ) } style={ itemStyle }>
								<div className="flexa-steps__aside">
									<span className="flexa-steps__marker" style={ buildMarkerStyle( attributes, device, status ) }>
										<MarkerInner item={ item } position={ index + 1 } globalType={ globalType } />
									</span>
									{ ! isLast && <span className="flexa-steps__connector" style={ connectorStyle } aria-hidden="true" /> }
								</div>
								<div className="flexa-steps__content" style={ buildContentStyle( attributes, device, isLast ) }>
									{ ( item.title || '' ) && (
										<span className="flexa-steps__title" style={ titleStyle }>
											{ item.title }
										</span>
									) }
									{ ( item.description || '' ) && (
										<span className="flexa-steps__description" style={ descStyle }>
											{ item.description }
										</span>
									) }
								</div>
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
