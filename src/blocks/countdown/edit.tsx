/**
 * Countdown block — editor component.
 *
 * Assembles the countdown-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders a live-ticking preview whose inline styles
 * mirror the PHP CSS generator; nothing is styled by default so the countdown
 * inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { getSettings } from '@wordpress/date';
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
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import { cn, effective, withUnit, spacingShorthand, applyTypography, applyBackgroundPreview, applyBorderPreview, boxShadowPreview, CONTENT_ALIGN_TO_FLEX } from '@utils';
import {
	CountdownPanel,
	LabelsPanel,
	CountdownSettingsPanel,
	DigitStylePanel,
	LabelStylePanel,
	ItemBoxPanel,
} from './panels';
import { UNIT_KEYS, remainingParts, pad, separatorChar, targetEpoch, type UnitKey } from './shared';
import type { CountdownAttributes, DeviceKey, EditProps } from '../../types';

type CssProps = Record< string, string >;

/** Resolve the UTC offset (hours) — the block's timezone, or the site default. */
const resolveOffsetHours = ( timezone?: string ): number => {
	if ( timezone !== undefined && timezone !== '' ) {
		return parseFloat( timezone ) || 0;
	}
	const off = ( getSettings() as { timezone?: { offset?: number | string } } ).timezone?.offset;
	return typeof off === 'number' ? off : parseFloat( String( off ?? '0' ) ) || 0;
};

/** Map an alignment keyword to a flex `align-items` value for the wrapper.
 *  Reuses the shared content-align map, adding countdown's `justify` case. */
const ALIGN_ITEMS: Record< string, string > = { ...CONTENT_ALIGN_TO_FLEX, justify: 'stretch' };

/** Wrapper preview: alignment, sizing, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: CountdownAttributes, device: DeviceKey ): CssProps => {
	const { alignment, maxWidth, size, spacing, background, border, boxShadow, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const align = alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	if ( align ) {
		s.alignItems = ALIGN_ITEMS[ align ] || 'center';
		s.textAlign = align === 'justify' ? 'center' : align;
	}

	const w = effective( maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );
	const sz = effective( size, device );
	if ( sz.minHeight?.value ) s.minHeight = withUnit( sz.minHeight.value, sz.minHeight.unit || 'px' );

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	if ( adv.overflow ) s.overflow = adv.overflow;
	if ( adv.position ) s.position = adv.position;
	if ( adv.zIndex ) s.zIndex = adv.zIndex;

	return s;
};

/** Timer row preview: gap between units + justify when alignment is justify. */
const buildTimerStyle = ( attributes: CountdownAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.itemGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	if ( ( attributes.alignment?.[ device ] || 'center' ) === 'justify' ) {
		s.justifyContent = 'space-between';
		s.width = '100%';
	}
	return s;
};

/** Unit-box preview: background, padding, radius. */
const buildItemStyle = ( attributes: CountdownAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.itemBackground?.light ) s.background = attributes.itemBackground.light;
	const padding = spacingShorthand( effective( attributes.itemPadding, device ) );
	if ( padding ) s.padding = padding;
	const radius = effective( attributes.itemBorderRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	return s;
};

/** Digit preview: typography + colour. */
const buildDigitStyle = ( attributes: CountdownAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.digitTypography, device ) );
	if ( attributes.digitColor?.light ) s.color = attributes.digitColor.light;
	return s;
};

/** Label preview: typography + colour. */
const buildLabelStyle = ( attributes: CountdownAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.labelTypography, device ) );
	if ( attributes.labelColor?.light ) s.color = attributes.labelColor.light;
	return s;
};

/**
 * Countdown edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< CountdownAttributes > ): JSX.Element {
	const { endDate, separator, labels, className, responsiveVisibility, htmlTag } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	// Re-tick every second so the editor preview counts down live.
	const [ now, setNow ] = useState( () => Date.now() );
	useEffect( () => {
		const id = setInterval( () => setNow( Date.now() ), 1000 );
		return () => clearInterval( id );
	}, [] );

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const shows: Record< UnitKey, boolean > = {
		days: attributes.showDays !== false,
		hours: attributes.showHours !== false,
		minutes: attributes.showMinutes !== false,
		seconds: attributes.showSeconds !== false,
	};
	const activeUnits = UNIT_KEYS.filter( ( u ) => shows[ u ] );
	const targetMs = targetEpoch( endDate, resolveOffsetHours( attributes.timezone ) );
	const parts = remainingParts( targetMs !== null ? targetMs - now : 0, shows );
	const showLabels = labels?.show !== false;
	const labelsBelow = ( labels?.position || 'below' ) !== 'above';
	const sepChar = separatorChar( separator );

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const timerStyle = buildTimerStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const digitStyle = buildDigitStyle( attributes, device );
	const labelStyle = buildLabelStyle( attributes, device );

	const sepStyle: CssProps = {};
	if ( attributes.separatorColor?.light ) sepStyle.color = attributes.separatorColor.light;
	const sepSize = effective( attributes.separatorFontSize, device );
	if ( sepSize.value ) sepStyle.fontSize = withUnit( sepSize.value, sepSize.unit || 'px' );

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-countdown',
			blockId && `flexa-countdown-${ blockId }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: wrapperStyle,
	} );

	const labelText = ( u: UnitKey ): string => labels?.[ u ] ?? '';

	// Inserter hover-preview → faint skeleton mock-up instead of the live timer.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="countdown" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-countdown-inspector">
					<InspectorTabs
						layout={
							<>
								<CountdownPanel attributes={ attributes } setAttributes={ setAttributes } />
								<LabelsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<CountdownSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<DigitStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<LabelStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ItemBoxPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-countdown__timer" style={ timerStyle }>
					{ activeUnits.map( ( unit, index ) => {
						const digit = (
							<span className="flexa-countdown__digit" style={ digitStyle } data-unit={ unit }>
								{ pad( parts[ unit ] ?? 0 ) }
							</span>
						);
						const label = showLabels && (
							<span className="flexa-countdown__label" style={ labelStyle }>
								{ labelText( unit ) }
							</span>
						);
						return (
							<div key={ unit } className="flexa-countdown__group">
								<div className={ `flexa-countdown__unit flexa-countdown__unit--${ unit }` } style={ itemStyle }>
									{ ! labelsBelow && label }
									{ digit }
									{ labelsBelow && label }
								</div>
								{ sepChar && index < activeUnits.length - 1 && (
									<span className="flexa-countdown__separator" style={ sepStyle } aria-hidden="true">
										{ sepChar }
									</span>
								) }
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
