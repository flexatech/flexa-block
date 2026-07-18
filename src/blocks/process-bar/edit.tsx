/**
 * Progress Bar block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. The canvas previews the bar with inline styles that
 * mirror the PHP CSS generator; the fill shows its final value (the on-scroll
 * fill + count-up is a front-end concern, view.ts). Nothing is styled by default
 * beyond a sensible fill so the bar reads as complete on insert.
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
	ProcessBarValuePanel,
	ProcessBarTitlePanel,
	ProcessBarBarPanel,
	ProcessBarColorsPanel,
	ProcessBarTextPanel,
} from './panels';
import type { ProcessBarAttributes, DeviceKey, EditProps } from '../../types';

/** SVG ring geometry — shared with render.php / view.ts (kept in sync by value). */
const RING_R = 42;
const CIRC = 2 * Math.PI * RING_R; // full circle circumference.
const SEMI = Math.PI * RING_R; // half-circle arc length.
/** The top semicircle arc path (left baseline → right baseline over the top). */
const SEMI_PATH = `M ${ 50 - RING_R } 50 A ${ RING_R } ${ RING_R } 0 0 1 ${ 50 + RING_R } 50`;

/** Clamp the progress to a 0…1 fraction from the value + its scale. */
const fractionOf = ( attributes: ProcessBarAttributes ): number => {
	const value = typeof attributes.value === 'number' ? attributes.value : 0;
	const total = attributes.valueType === 'absolute' ? ( typeof attributes.max === 'number' ? attributes.max : 100 ) : 100;
	if ( ! total ) {
		return 0;
	}
	return Math.max( 0, Math.min( 1, value / total ) );
};

/** The active device's alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: ProcessBarAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || 'left';
};

/** Wrapper preview: alignment, max width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: ProcessBarAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'flex-start';
	s.textAlign = align;
	const mw = effective( attributes.maxWidth, device );
	if ( mw.value ) s.maxWidth = withUnit( mw.value, mw.unit || 'px' );
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

/** Stripe overlay for a striped fill (mirrors the generator). */
const stripeImage = (): string =>
	'repeating-linear-gradient(45deg, rgba(255,255,255,0.25) 0, rgba(255,255,255,0.25) 8px, transparent 8px, transparent 16px)';

/** The line track + fill preview styles. */
const buildLine = ( attributes: ProcessBarAttributes, device: DeviceKey, fraction: number ): { track: CssProps; fill: CssProps } => {
	const track: CssProps = {};
	const fill: CssProps = {};
	const height = effective( attributes.barHeight, device );
	if ( height.value ) track.height = withUnit( height.value, height.unit || 'px' );
	const radius = attributes.barRadius || {};
	if ( radius.value ) {
		const r = withUnit( radius.value, radius.unit || 'px' );
		track.borderRadius = r;
		fill.borderRadius = r;
	}
	track.background = attributes.trackColor?.light || 'rgba(0,0,0,0.08)';
	fill.background = attributes.fillColor?.light || 'currentColor';
	fill.width = `${ fraction * 100 }%`;
	if ( attributes.fillStyle === 'striped' || attributes.fillStyle === 'striped-animated' ) {
		fill.backgroundImage = stripeImage();
	}
	return { track, fill };
};

/** The circle / semi ring preview styles (SVG element + the two strokes). */
const buildRing = ( attributes: ProcessBarAttributes, device: DeviceKey ): { svg: CssProps; track: CssProps; fill: CssProps } => {
	const svg: CssProps = {};
	const size = effective( attributes.circleSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		svg.width = v;
		svg.height = v;
	}
	const sw = effective( attributes.strokeWidth, device );
	const strokeWidth = sw.value ? String( parseFloat( String( sw.value ) ) ) : '12';
	const track: CssProps = { stroke: attributes.trackColor?.light || 'rgba(0,0,0,0.08)', strokeWidth };
	const fill: CssProps = { stroke: attributes.fillColor?.light || 'currentColor', strokeWidth, strokeLinecap: attributes.lineCap || 'round' };
	return { svg, track, fill };
};

/** The counter readout text: "75%" (percent) or "75 / 100" (absolute). */
const counterText = ( attributes: ProcessBarAttributes ): JSX.Element => {
	const value = typeof attributes.value === 'number' ? attributes.value : 0;
	if ( attributes.valueType === 'absolute' ) {
		const max = typeof attributes.max === 'number' ? attributes.max : 100;
		return (
			<>
				<span className="flexa-process-bar__counter-value">{ value }</span>
				<span className="flexa-process-bar__counter-divider">{ ` ${ attributes.dividerChar ?? '/' } ` }</span>
				<span className="flexa-process-bar__counter-max">{ max }</span>
			</>
		);
	}
	return (
		<>
			<span className="flexa-process-bar__counter-value">{ value }</span>
			<span className="flexa-process-bar__counter-suffix">%</span>
		</>
	);
};

/**
 * Progress Bar edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProcessBarAttributes > ): JSX.Element {
	const { barType, className, responsiveVisibility, htmlTag, showTitle, showCounter, title, titleTag, titlePosition } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const type = barType || 'line';
	const isLine = type === 'line';
	const fraction = fractionOf( attributes );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const TitleTag: any = titleTag || 'div';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-process-bar',
			`flexa-process-bar--${ type }`,
			blockId && `flexa-process-bar-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const titleStyle: CssProps = {};
	applyTypography( titleStyle, rawDevice( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) titleStyle.color = attributes.titleColor.light;

	const counterStyle: CssProps = {};
	applyTypography( counterStyle, rawDevice( attributes.counterTypography, device ) );
	if ( attributes.counterColor?.light ) counterStyle.color = attributes.counterColor.light;

	const titleEl = showTitle !== false && ( title ?? '' ) !== '' ? (
		<TitleTag className="flexa-process-bar__title" style={ titleStyle }>
			{ title }
		</TitleTag>
	) : null;

	const counterEl = showCounter !== false ? (
		<span className="flexa-process-bar__counter" style={ counterStyle }>
			{ counterText( attributes ) }
		</span>
	) : null;

	let barEl: JSX.Element;
	if ( isLine ) {
		const { track, fill } = buildLine( attributes, device, fraction );
		const header = ( titleEl || counterEl ) ? (
			<div className="flexa-process-bar__header">
				{ titleEl }
				{ counterEl }
			</div>
		) : null;
		barEl = (
			<>
				{ titlePosition !== 'below' && header }
				<div className="flexa-process-bar__bar flexa-process-bar__track" style={ track }>
					<div className="flexa-process-bar__fill" style={ fill } />
				</div>
				{ titlePosition === 'below' && header }
			</>
		);
	} else {
		const { svg, track, fill } = buildRing( attributes, device );
		const isSemi = type === 'semicircle';
		const len = isSemi ? SEMI : CIRC;
		const offset = len * ( 1 - fraction );
		const ring = isSemi ? (
			<svg className="flexa-process-bar__ring" style={ svg } viewBox="0 0 100 58" role="img" aria-hidden="true">
				<path className="flexa-process-bar__ring-track" style={ track } d={ SEMI_PATH } fill="none" />
				<path
					className="flexa-process-bar__ring-fill"
					style={ fill }
					d={ SEMI_PATH }
					fill="none"
					strokeDasharray={ SEMI.toFixed( 3 ) }
					strokeDashoffset={ offset.toFixed( 3 ) }
				/>
			</svg>
		) : (
			<svg className="flexa-process-bar__ring" style={ svg } viewBox="0 0 100 100" role="img" aria-hidden="true">
				<circle className="flexa-process-bar__ring-track" style={ track } cx="50" cy="50" r={ RING_R } fill="none" />
				<circle
					className="flexa-process-bar__ring-fill"
					style={ fill }
					cx="50"
					cy="50"
					r={ RING_R }
					fill="none"
					transform="rotate(-90 50 50)"
					strokeDasharray={ CIRC.toFixed( 3 ) }
					strokeDashoffset={ offset.toFixed( 3 ) }
				/>
			</svg>
		);
		barEl = (
			<>
				{ titleEl }
				<div className="flexa-process-bar__circle">
					{ ring }
					{ counterEl && <div className="flexa-process-bar__circle-counter">{ counterEl }</div> }
				</div>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-process-bar-inspector">
					<InspectorTabs
						layout={
							<>
								<ProcessBarValuePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProcessBarTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProcessBarBarPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProcessBarColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProcessBarTextPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<Tag { ...blockProps }>{ barEl }</Tag>
		</>
	);
}
