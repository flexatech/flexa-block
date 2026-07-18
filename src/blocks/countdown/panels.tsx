/**
 * Countdown block — countdown-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography/colour controls come from @components;
 * these cover the parts unique to the countdown: the target date, which units to
 * show, the separator and labels, the completion action, plus the digit / label
 * / unit-box styling. Following the "prefer theme styles" rule nothing is styled
 * by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { PanelBody, SelectControl, ToggleControl, TextControl, TextareaControl, Dropdown, Button, DateTimePicker } from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	FieldHead,
	TypographyControls,
	useDevice,
	TEXT_ALIGN_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, SPACING_UNITS, LENGTH_UNITS, HTML_TAGS } from '@utils';
import type { BoxValue, CountdownAttributes, CountdownExpiredAttr, CountdownLabelsAttr, CountdownSeparatorAttr, LengthValue, PanelProps, TypographyDevice } from '../../types';

type CountdownPanelProps = PanelProps< CountdownAttributes >;

/** Format a numeric UTC offset (hours) as an "UTC+07:00" label. */
const formatOffset = ( hours: number ): string => {
	const sign = hours < 0 ? '-' : '+';
	const abs = Math.abs( hours );
	const hh = Math.floor( abs );
	const mm = Math.round( ( abs - hh ) * 60 );
	return `UTC${ sign }${ String( hh ).padStart( 2, '0' ) }:${ String( mm ).padStart( 2, '0' ) }`;
};

/** The site's UTC offset in hours, from the WordPress date settings. */
const siteOffsetHours = (): number => {
	const off = ( getSettings() as { timezone?: { offset?: number | string } } ).timezone?.offset;
	return typeof off === 'number' ? off : parseFloat( String( off ?? '0' ) ) || 0;
};

/** Selectable UTC offsets (whole + notable fractional zones). */
const OFFSET_VALUES = [
	-12, -11, -10, -9.5, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0,
	1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 8, 8.75, 9, 9.5, 10, 10.5, 11, 12, 12.75, 13, 14,
];

/** Separator styles between units (few values → SelectControl). */
const SEPARATOR_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'colon', label: __( 'Colon ( : )', 'flexa-block' ) },
	{ value: 'dash', label: __( 'Dash ( - )', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom', 'flexa-block' ) },
];

/** Completion action when the countdown reaches zero. */
const EXPIRED_OPTIONS = [
	{ value: 'hide', label: __( 'Hide the countdown', 'flexa-block' ) },
	{ value: 'zero', label: __( 'Keep showing zeros', 'flexa-block' ) },
	{ value: 'message', label: __( 'Show a message', 'flexa-block' ) },
];

/** Screen-reader announcement politeness. */
const ARIA_LIVE_OPTIONS = [
	{ value: 'off', label: __( 'Off', 'flexa-block' ) },
	{ value: 'polite', label: __( 'Polite', 'flexa-block' ) },
	{ value: 'assertive', label: __( 'Assertive', 'flexa-block' ) },
];

/**
 * Countdown panel — target date, which units to show, separator and alignment.
 */
export const CountdownPanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { endDate, showDays, showHours, showMinutes, showSeconds, separator, alignment, itemGap, timezone } = attributes;
	const align = alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	const sep = separator || {};

	// Label in WordPress's own date + time format. The picked value is a bare
	// wall-clock string; we rebuild it as a UTC instant and format it *in UTC* so
	// dateI18n keeps the exact numbers (translated month names, WP formats) instead
	// of re-interpreting against the browser/site timezone (which shifted 21:34 →
	// 14:34).
	const formattedDate = useMemo( () => {
		if ( ! endDate ) {
			return __( 'Select date & time…', 'flexa-block' );
		}
		const m = endDate.match( /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/ );
		if ( ! m ) {
			return endDate;
		}
		const [ , y, mo, d, h, mi, s ] = m;
		const settings = getSettings();
		const fmt = `${ settings.formats.date || 'F j, Y' } ${ settings.formats.time || 'H:i' }`;
		const utc = new Date( Date.UTC( Number( y ), Number( mo ) - 1, Number( d ), Number( h ), Number( mi ), s ? Number( s ) : 0 ) );
		return dateI18n( fmt, utc, 'UTC' );
	}, [ endDate ] );

	// Let the picker's clock match the site's time format (12h vs 24h).
	const is12Hour = useMemo( () => /[gh]/.test( getSettings().formats.time || '' ), [] );

	const tzOptions = useMemo(
		() => [
			{ value: '', label: `${ __( 'Site default', 'flexa-block' ) } (${ formatOffset( siteOffsetHours() ) })` },
			...OFFSET_VALUES.map( ( h ) => ( { value: String( h ), label: formatOffset( h ) } ) ),
		],
		[]
	);

	const setSep = ( patch: Partial< CountdownSeparatorAttr > ) => setAttributes( { separator: { ...sep, ...patch } } );

	return (
		<PanelBody title={ __( 'Countdown', 'flexa-block' ) } initialOpen={ true }>
			<div className="flexa-field">
				<FieldHead label={ __( 'Target date & time', 'flexa-block' ) } />
				<Dropdown
					className="flexa-countdown-datetime"
					contentClassName="flexa-countdown-datetime__popover"
					popoverProps={ { placement: 'left-start', offset: 8 } }
					renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
						<Button variant="primary" onClick={ onToggle } aria-expanded={ isOpen } __next40pxDefaultSize>
							{ formattedDate }
						</Button>
					) }
					renderContent={ () => (
						<DateTimePicker
							currentDate={ endDate || undefined }
							onChange={ ( date: string ) => setAttributes( { endDate: date } ) }
							is12Hour={ is12Hour }
						/>
					) }
				/>
			</div>

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Timezone', 'flexa-block' ) }
				value={ timezone || '' }
				options={ tzOptions }
				onChange={ ( v: string ) => setAttributes( { timezone: v } ) }
			/>

			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show days', 'flexa-block' ) } checked={ showDays !== false } onChange={ ( v: boolean ) => setAttributes( { showDays: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show hours', 'flexa-block' ) } checked={ showHours !== false } onChange={ ( v: boolean ) => setAttributes( { showHours: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show minutes', 'flexa-block' ) } checked={ showMinutes !== false } onChange={ ( v: boolean ) => setAttributes( { showMinutes: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show seconds', 'flexa-block' ) } checked={ showSeconds !== false } onChange={ ( v: boolean ) => setAttributes( { showSeconds: v } ) } />

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Separator', 'flexa-block' ) }
				value={ sep.type || 'none' }
				options={ SEPARATOR_OPTIONS }
				onChange={ ( v: string ) => setSep( { type: v as CountdownSeparatorAttr[ 'type' ] } ) }
			/>
			{ sep.type === 'custom' && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Custom separator', 'flexa-block' ) }
					value={ sep.custom || '' }
					onChange={ ( v: string ) => setSep( { custom: v } ) }
				/>
			) }

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ TEXT_ALIGN_OPTIONS }
				/>
			</div>

			<SliderUnit
				label={ __( 'Gap between units', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100, vw: 50, vh: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Labels panel — show labels, their position and the text per unit.
 */
export const LabelsPanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const labels = attributes.labels || {};
	const setLabels = ( patch: Partial< CountdownLabelsAttr > ) => setAttributes( { labels: { ...labels, ...patch } } );

	return (
		<PanelBody title={ __( 'Labels', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show labels', 'flexa-block' ) } checked={ labels.show !== false } onChange={ ( v: boolean ) => setLabels( { show: v } ) } />
			{ labels.show !== false && (
				<>
					<Segmented
						label={ __( 'Position', 'flexa-block' ) }
						value={ labels.position || 'below' }
						onChange={ ( v ) => setLabels( { position: v as CountdownLabelsAttr[ 'position' ] } ) }
						options={ [
							{ value: 'above', label: __( 'Above', 'flexa-block' ) },
							{ value: 'below', label: __( 'Below', 'flexa-block' ) },
						] }
					/>
					<TextControl __nextHasNoMarginBottom label={ __( 'Days label', 'flexa-block' ) } value={ labels.days ?? '' } onChange={ ( v: string ) => setLabels( { days: v } ) } />
					<TextControl __nextHasNoMarginBottom label={ __( 'Hours label', 'flexa-block' ) } value={ labels.hours ?? '' } onChange={ ( v: string ) => setLabels( { hours: v } ) } />
					<TextControl __nextHasNoMarginBottom label={ __( 'Minutes label', 'flexa-block' ) } value={ labels.minutes ?? '' } onChange={ ( v: string ) => setLabels( { minutes: v } ) } />
					<TextControl __nextHasNoMarginBottom label={ __( 'Seconds label', 'flexa-block' ) } value={ labels.seconds ?? '' } onChange={ ( v: string ) => setLabels( { seconds: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Settings panel — completion action, screen-reader politeness, box sizing and
 * the wrapper HTML tag.
 */
export const CountdownSettingsPanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { expiredAction, ariaLive, maxWidth, size, htmlTag } = attributes;
	const expired = expiredAction || {};
	const minH = size?.[ device ]?.minHeight || {};
	const setExpired = ( patch: Partial< CountdownExpiredAttr > ) => setAttributes( { expiredAction: { ...expired, ...patch } } );

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ false }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'When it ends', 'flexa-block' ) }
				value={ expired.type || 'hide' }
				options={ EXPIRED_OPTIONS }
				onChange={ ( v: string ) => setExpired( { type: v as CountdownExpiredAttr[ 'type' ] } ) }
			/>
			{ expired.type === 'message' && (
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Completion message', 'flexa-block' ) }
					value={ expired.message || '' }
					placeholder={ __( 'e.g. The sale has started!', 'flexa-block' ) }
					onChange={ ( v: string ) => setExpired( { message: v } ) }
				/>
			) }

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Screen reader announcements', 'flexa-block' ) }
				value={ ariaLive || 'off' }
				options={ ARIA_LIVE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { ariaLive: v as CountdownAttributes[ 'ariaLive' ] } ) }
			/>

			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1600, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Min height', 'flexa-block' ) }
				value={ minH }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, vh: 100, '%': 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { size: { ...size, [ device ]: { minHeight: v } } } ) }
			/>

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Digits panel — typography + colour of the numbers.
 */
export const DigitStylePanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.digitTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { digitTypography: patchDevice( attributes.digitTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Digits', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Digit colour', 'flexa-block' ) } value={ attributes.digitColor || {} } onChange={ ( v ) => setAttributes( { digitColor: v } ) } />

			<SliderUnit
				label={ __( 'Separator size', 'flexa-block' ) }
				value={ attributes.separatorFontSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 200, rem: 12, em: 12, '%': 300 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorFontSize: { ...attributes.separatorFontSize, [ device ]: v } } ) }
			/>
			<DualColor label={ __( 'Separator colour', 'flexa-block' ) } value={ attributes.separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Labels-style panel — typography + colour of the unit labels.
 */
export const LabelStylePanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.labelTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { labelTypography: patchDevice( attributes.labelTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Label Style', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Label colour', 'flexa-block' ) } value={ attributes.labelColor || {} } onChange={ ( v ) => setAttributes( { labelColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Unit-box panel — background, padding and radius of each unit's box.
 */
export const ItemBoxPanel = ( { attributes, setAttributes }: CountdownPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { itemBackground, itemPadding, itemBorderRadius } = attributes;

	return (
		<PanelBody title={ __( 'Unit Box', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ itemBackground || {} } onChange={ ( v ) => setAttributes( { itemBackground: v } ) } />
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( itemPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { itemPadding: { ...itemPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Border radius', 'flexa-block' ) }
				value={ itemBorderRadius?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemBorderRadius: { ...itemBorderRadius, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
