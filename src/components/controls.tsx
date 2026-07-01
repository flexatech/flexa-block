/**
 * Reusable inspector controls (shared across blocks).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Button,
	BaseControl,
	Dropdown,
	ColorPalette,
	Tooltip,
	Icon,
	RangeControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
	CustomGradientPicker as StableGradientPicker,
	__experimentalCustomGradientPicker as ExperimentalGradientPicker,
} from '@wordpress/components';

// WordPress 7.0 graduated this out of __experimental; keep a fallback so the
// control works on older builds too.
const CustomGradientPicker = StableGradientPicker || ExperimentalGradientPicker;

import { DesktopIcon, TabletIcon, MobileIcon, ResetIcon, LinkIcon, LightIcon, DarkIcon, DEFAULT_PALETTE } from '@utils';
import { useDeviceType, setDeviceType, getDeviceKey } from '@hooks';
import type { BoxValue, ColorPair, ControlOption, DeviceKey, DeviceType, LengthValue } from '../types';

const SIDES = [ 'top', 'right', 'bottom', 'left' ] as const;

/**
 * Hook returning the active responsive device key and a setter.
 *
 * @return [ deviceKey, setDevice ].
 */
export const useDevice = (): [ DeviceKey, ( device: DeviceType | string ) => void ] => {
	const deviceType = useDeviceType();
	return [ getDeviceKey( deviceType ), setDeviceType ];
};

/**
 * Device switcher — three segmented icon buttons synced to the editor preview.
 */
export const DeviceSwitcher = (): JSX.Element => {
	const deviceType = useDeviceType();
	const devices = [
		{ key: 'Desktop', icon: DesktopIcon, label: __( 'Desktop', 'flexa-block' ) },
		{ key: 'Tablet', icon: TabletIcon, label: __( 'Tablet', 'flexa-block' ) },
		{ key: 'Mobile', icon: MobileIcon, label: __( 'Mobile', 'flexa-block' ) },
	];

	return (
		<div className="flexa-devices">
			{ devices.map( ( d ) => (
				<Tooltip key={ d.key } text={ d.label }>
					<Button
						className={ 'flexa-devices__btn' + ( deviceType === d.key ? ' is-active' : '' ) }
						icon={ <Icon icon={ d.icon } /> }
						onClick={ () => setDeviceType( d.key ) }
						label={ d.label }
						showTooltip={ false }
					/>
				</Tooltip>
			) ) }
		</div>
	);
};

interface SegmentedProps {
	label: string;
	value: string;
	options: ControlOption[];
	onChange: ( value: string ) => void;
	isBlock?: boolean;
	responsive?: boolean;
}

/**
 * Segmented control with optional icon options. When `responsive`, shows a
 * device toggle next to the label (value is stored per device).
 */
export const Segmented = ( { label, value, options, onChange, isBlock = true, responsive = false }: SegmentedProps ): JSX.Element => (
	<div className="flexa-field">
		{ responsive && <FieldHead label={ label } /> }
		<ToggleGroupControl
			label={ label }
			hideLabelFromVision={ responsive }
			value={ value }
			onChange={ onChange }
			isBlock={ isBlock }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		>
			{ options.map( ( opt ) =>
				opt.icon ? (
					<ToggleGroupControlOptionIcon key={ opt.value } value={ opt.value } icon={ <Icon icon={ opt.icon } /> } label={ opt.label } />
				) : (
					<ToggleGroupControlOption key={ opt.value } value={ opt.value } label={ opt.label } />
				)
			) }
		</ToggleGroupControl>
	</div>
);

/* --- Dimensions: four-side box on our { top,right,bottom,left,unit } shape. --- */

interface DimensionsProps {
	label: string;
	value?: BoxValue;
	onChange: ( value: BoxValue ) => void;
	units?: Array< { value: string; label: string } >;
	responsive?: boolean;
}

/**
 * Dimensions control (padding / margin / border width / radius) on our
 * { top, right, bottom, left, unit } shape — four side inputs, a unit dropdown,
 * a link toggle (sync all sides) and an optional responsive device toggle.
 */
export const Dimensions = ( { label, value = {}, onChange, units, responsive = false }: DimensionsProps ): JSX.Element => {
	const [ linked, setLinked ] = useState( true );
	const unit = value.unit || units?.[ 0 ]?.value || 'px';

	const setSide = ( side: ( typeof SIDES )[ number ], v: string ) => {
		if ( linked ) {
			onChange( { top: v, right: v, bottom: v, left: v, unit } );
		} else {
			onChange( { ...value, [ side ]: v, unit } );
		}
	};

	return (
		<div className="flexa-field flexa-box">
			<div className="flexa-box__head">
				<span className="flexa-field__label">
					{ label }
					{ responsive && <DeviceTag /> }
				</span>
				<div className="flexa-box__tools">
					{ units && units.length > 1 && (
						<select
							className="flexa-unit-chip"
							value={ unit }
							aria-label={ __( 'Unit', 'flexa-block' ) }
							onChange={ ( e: { target: { value: string } } ) => onChange( { ...value, unit: e.target.value } ) }
						>
							{ units.map( ( u ) => (
								<option key={ u.value } value={ u.value }>{ u.label }</option>
							) ) }
						</select>
					) }
				</div>
			</div>
			<div className="flexa-box__inputs">
				{ SIDES.map( ( side ) => (
					<input
						key={ side }
						type="number"
						className="flexa-box__input"
						value={ value[ side ] ?? '' }
						onChange={ ( e: { target: { value: string } } ) => setSide( side, e.target.value ) }
					/>
				) ) }
				<button
					type="button"
					className={ 'flexa-box__link' + ( linked ? ' is-linked' : '' ) }
					aria-label={ __( 'Link sides', 'flexa-block' ) }
					aria-pressed={ linked }
					onClick={ () => setLinked( ! linked ) }
				>
					<Icon icon={ LinkIcon } />
				</button>
			</div>
			<div className="flexa-box__labels">
				{ SIDES.map( ( side ) => (
					<span key={ side }>{ side }</span>
				) ) }
			</div>
		</div>
	);
};

/* --- Color swatch + dual (light/dark) color control. --- */

interface SwatchProps {
	label: string;
	color?: string;
	onChange: ( color: string ) => void;
	palette: unknown;
}

const Swatch = ( { label, color, onChange, palette }: SwatchProps ): JSX.Element => (
	<Dropdown
		className="flexa-swatch"
		contentClassName="flexa-swatch__popover"
		renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
			<Tooltip text={ label }>
				<button
					type="button"
					aria-expanded={ isOpen }
					onClick={ onToggle }
					className="flexa-swatch__btn"
					style={ { background: color || 'transparent' } }
				>
					{ ! color && <span className="flexa-swatch__empty" /> }
				</button>
			</Tooltip>
		) }
		renderContent={ () => (
			<div className="flexa-swatch__content">
				<ColorPalette colors={ palette } value={ color } onChange={ ( c: string ) => onChange( c || '' ) } enableAlpha />
			</div>
		) }
	/>
);

/**
 * Whether dark mode is enabled site-wide (from the admin setting).
 *
 * Defaults to true when the flag isn't present (e.g. before localization), so
 * behaviour is unchanged unless dark mode is explicitly turned off.
 *
 * @return True if dark color controls should show.
 */
export const isDarkModeEnabled = (): boolean => window.flexaBlockEditor?.darkModeEnabled !== false;

type ColorMode = 'light' | 'dark';

const MODE_LIST: Array< { key: ColorMode; icon: JSX.Element; label: string } > = [
	{ key: 'light', icon: LightIcon, label: __( 'Light', 'flexa-block' ) },
	{ key: 'dark', icon: DarkIcon, label: __( 'Dark', 'flexa-block' ) },
];

/**
 * Light / Dark mode toggle (mirrors the responsive DeviceTag) — picks which
 * colour (light or dark) the swatch edits.
 */
const ModeTag = ( { mode, onChange }: { mode: ColorMode; onChange: ( m: ColorMode ) => void } ): JSX.Element => {
	const current = MODE_LIST.find( ( m ) => m.key === mode ) || MODE_LIST[ 0 ];
	return (
		<Dropdown
			className="flexa-mode-tag-wrap"
			contentClassName="flexa-device-menu__popover"
			popoverProps={ { placement: 'bottom-end' } }
			renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
				<Tooltip text={ __( 'Light / Dark colour', 'flexa-block' ) }>
					<button
						type="button"
						className={ 'flexa-device-tag' + ( mode === 'dark' ? ' is-active' : '' ) }
						aria-expanded={ isOpen }
						aria-label={ __( 'Choose light or dark', 'flexa-block' ) }
						onClick={ onToggle }
					>
						<Icon icon={ current.icon } />
					</button>
				</Tooltip>
			) }
			renderContent={ ( { onClose }: { onClose: () => void } ) => (
				<div className="flexa-device-menu">
					{ MODE_LIST.map( ( m ) => (
						<Tooltip key={ m.key } text={ m.label }>
							<button
								type="button"
								aria-label={ m.label }
								className={ 'flexa-device-menu__item' + ( mode === m.key ? ' is-active' : '' ) }
								onClick={ () => {
									onChange( m.key );
									onClose();
								} }
							>
								<Icon icon={ m.icon } />
							</button>
						</Tooltip>
					) ) }
				</div>
			) }
		/>
	);
};

interface DualColorProps {
	label: string;
	value?: ColorPair;
	onChange: ( value: ColorPair ) => void;
	palette?: unknown;
}

/**
 * Colour control — one swatch with a light/dark mode toggle (when dark mode is
 * enabled) and a reset button. Clicking the swatch opens the colour picker.
 */
export const DualColor = ( { label, value = {}, onChange, palette = DEFAULT_PALETTE }: DualColorProps ): JSX.Element => {
	const darkEnabled = isDarkModeEnabled();
	const [ mode, setMode ] = useState( 'light' );
	const activeMode: ColorMode = darkEnabled ? mode : 'light';

	return (
		<div className="flexa-field flexa-dualcolor">
			<div className="flexa-dualcolor__head">
				<span className="flexa-field__label">
					{ label }
					{ darkEnabled && <ModeTag mode={ activeMode } onChange={ setMode } /> }
				</span>
				<div className="flexa-dualcolor__tools">
					<button
						type="button"
						className="flexa-dualcolor__reset"
						aria-label={ __( 'Reset', 'flexa-block' ) }
						onClick={ () => onChange( { ...value, [ activeMode ]: '' } ) }
					>
						<Icon icon={ ResetIcon } />
					</button>
					<Swatch
						label={ activeMode === 'dark' ? __( 'Dark', 'flexa-block' ) : __( 'Color', 'flexa-block' ) }
						color={ value[ activeMode ] }
						palette={ palette }
						onChange={ ( c ) => onChange( { ...value, [ activeMode ]: c } ) }
					/>
				</div>
			</div>
		</div>
	);
};

interface GradientControlProps {
	label: string;
	value?: ColorPair;
	onChange: ( value: ColorPair ) => void;
}

/**
 * Gradient control — visual gradient builder (stops, type, angle) with a
 * light/dark mode toggle (when dark mode is enabled) and a reset button.
 */
export const GradientControl = ( { label, value = {}, onChange }: GradientControlProps ): JSX.Element => {
	const darkEnabled = isDarkModeEnabled();
	const [ mode, setMode ] = useState( 'light' );
	const activeMode: ColorMode = darkEnabled ? mode : 'light';

	return (
		<div className="flexa-field flexa-gradient">
			<div className="flexa-dualcolor__head">
				<span className="flexa-field__label">
					{ label }
					{ darkEnabled && <ModeTag mode={ activeMode } onChange={ setMode } /> }
				</span>
				<div className="flexa-dualcolor__tools">
					<button
						type="button"
						className="flexa-dualcolor__reset"
						aria-label={ __( 'Reset', 'flexa-block' ) }
						onClick={ () => onChange( { ...value, [ activeMode ]: '' } ) }
					>
						<Icon icon={ ResetIcon } />
					</button>
				</div>
			</div>
			<CustomGradientPicker
				__nextHasNoMargin
				value={ value[ activeMode ] || undefined }
				onChange={ ( g: string ) => onChange( { ...value, [ activeMode ]: g } ) }
			/>
		</div>
	);
};

/* --- Slider + number input + unit toggle (length / size control). --- */

/** Default slider upper bound per unit. */
const SLIDER_MAX: Record< string, number > = {
	px: 1600,
	'%': 100,
	vw: 100,
	vh: 100,
	em: 60,
	rem: 60,
};

const DEVICE_ICON: Record< DeviceKey, JSX.Element > = {
	desktop: DesktopIcon,
	tablet: TabletIcon,
	mobile: MobileIcon,
};

const DEVICE_LIST: Array< { key: DeviceKey; type: DeviceType; label: string } > = [
	{ key: 'desktop', type: 'Desktop', label: __( 'Desktop', 'flexa-block' ) },
	{ key: 'tablet', type: 'Tablet', label: __( 'Tablet', 'flexa-block' ) },
	{ key: 'mobile', type: 'Mobile', label: __( 'Mobile', 'flexa-block' ) },
];

/**
 * Clickable responsive indicator — opens a dropdown to pick the active editor
 * device (Desktop / Tablet / Mobile). Responsive attributes are stored per
 * device, so picking one lets a control hold a different value per breakpoint.
 */
export const DeviceTag = (): JSX.Element => {
	const [ device, setDevice ] = useDevice();
	return (
		<Dropdown
			className="flexa-device-tag-wrap"
			contentClassName="flexa-device-menu__popover"
			popoverProps={ { placement: 'bottom-end' } }
			renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
				<Tooltip text={ __( 'Responsive — choose device', 'flexa-block' ) }>
					<button
						type="button"
						className={ 'flexa-device-tag' + ( device !== 'desktop' ? ' is-active' : '' ) }
						aria-expanded={ isOpen }
						aria-label={ __( 'Choose device', 'flexa-block' ) }
						onClick={ onToggle }
					>
						<Icon icon={ DEVICE_ICON[ device ] } />
					</button>
				</Tooltip>
			) }
			renderContent={ ( { onClose }: { onClose: () => void } ) => (
				<div className="flexa-device-menu">
					{ DEVICE_LIST.map( ( d ) => (
						<Tooltip key={ d.key } text={ d.label }>
							<button
								type="button"
								title={ d.label }
								aria-label={ d.label }
								className={ 'flexa-device-menu__item' + ( device === d.key ? ' is-active' : '' ) }
								onClick={ () => {
									setDevice( d.type );
									onClose();
								} }
							>
								<Icon icon={ DEVICE_ICON[ d.key ] } />
							</button>
						</Tooltip>
					) ) }
				</div>
			) }
		/>
	);
};

/** Label row with a responsive device toggle (next to the label). */
export const FieldHead = ( { label }: { label: string } ): JSX.Element => (
	<div className="flexa-field__head">
		<span className="flexa-field__label">
			{ label }
			<DeviceTag />
		</span>
	</div>
);

interface SliderUnitProps {
	label: string;
	value?: LengthValue;
	units: Array< { value: string; label: string } >;
	onChange: ( value: LengthValue ) => void;
	min?: number;
	max?: Record< string, number >;
	defaultUnit?: string;
	showDevice?: boolean;
}

/**
 * Length control: drag a slider OR type a number, with a PX/%/VW unit toggle
 * and a reset button. Operates on our { value, unit } shape; the slider's upper
 * bound adapts to the active unit.
 */
export const SliderUnit = ( { label, value = {}, units, onChange, min = 0, max, defaultUnit, showDevice = true }: SliderUnitProps ): JSX.Element => {
	const unit = value.unit || defaultUnit || units[ 0 ]?.value || 'px';
	const raw = value.value;
	const num = raw === '' || raw === undefined || raw === null ? undefined : parseFloat( String( raw ) );
	const sliderMax = ( max && max[ unit ] ) ?? SLIDER_MAX[ unit ] ?? 1000;

	return (
		<BaseControl __nextHasNoMarginBottom className="flexa-slider-unit">
			<div className="flexa-slider-unit__head">
				<span className="flexa-slider-unit__label">
					{ label }
					{ showDevice && <DeviceTag /> }
				</span>
				<div className="flexa-slider-unit__tools">
					<button
						type="button"
						className="flexa-slider-unit__reset"
						aria-label={ __( 'Reset', 'flexa-block' ) }
						onClick={ () => onChange( { value: '', unit } ) }
					>
						<Icon icon={ ResetIcon } />
					</button>
					<select
						className="flexa-unit-chip"
						value={ unit }
						onChange={ ( e: { target: { value: string } } ) => onChange( { value: raw ?? '', unit: e.target.value } ) }
						aria-label={ __( 'Unit', 'flexa-block' ) }
					>
						{ units.map( ( u ) => (
							<option key={ u.value } value={ u.value }>{ u.label }</option>
						) ) }
					</select>
				</div>
			</div>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				value={ num }
				min={ min }
				max={ sliderMax }
				onChange={ ( v: number | undefined ) => onChange( { value: v === undefined || v === null ? '' : String( v ), unit } ) }
			/>
		</BaseControl>
	);
};
