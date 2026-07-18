/**
 * Notice block — notice-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) plus
 * the shared typography controls and option sets come from @components; these
 * cover the parts unique to a notice: the severity preset & behaviour, the icon,
 * the title and the message body. Following the "prefer theme styles" rule
 * nothing is styled by default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	TypographyControls,
	IconPicker,
	useDevice,
	TEXT_ALIGN_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, HTML_TAGS } from '@utils';
import type { ControlOption, LengthValue, NoticeAttributes, PanelProps, TypographyDevice } from '../../types';

type NoticePanelProps = PanelProps< NoticeAttributes >;

/** Severity presets (five values, no fitting icons → SelectControl, guide §6.4c). */
const NOTICE_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'info', label: __( 'Info', 'flexa-block' ) },
	{ value: 'success', label: __( 'Success', 'flexa-block' ) },
	{ value: 'warning', label: __( 'Warning', 'flexa-block' ) },
	{ value: 'danger', label: __( 'Danger', 'flexa-block' ) },
	{ value: 'default', label: __( 'Default', 'flexa-block' ) },
];

/** Where the icon sits relative to the text (two values → Segmented). */
const ICON_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'left', label: __( 'Left', 'flexa-block' ) },
	{ value: 'top', label: __( 'Top', 'flexa-block' ) },
];

/**
 * Notice settings panel — severity preset, dismiss behaviour, content alignment
 * and the wrapper tag.
 */
export const NoticeSettingsPanel = ( { attributes, setAttributes }: NoticePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { noticeType, dismissible, rememberDismiss, alignment, htmlTag } = attributes;
	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Notice', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Type', 'flexa-block' ) }
				value={ noticeType || 'info' }
				options={ NOTICE_TYPE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { noticeType: v as NoticeAttributes[ 'noticeType' ] } ) }
			/>
			<DualColor
				label={ __( 'Accent color', 'flexa-block' ) }
				value={ attributes.accentColor || {} }
				onChange={ ( v ) => setAttributes( { accentColor: v } ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ align }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ TEXT_ALIGN_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Dismissible', 'flexa-block' ) }
				checked={ !! dismissible }
				onChange={ ( v: boolean ) => setAttributes( { dismissible: v } ) }
			/>
			{ dismissible && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Remember dismissal', 'flexa-block' ) }
					help={ __( 'Keep the notice hidden for this visitor after they close it.', 'flexa-block' ) }
					checked={ !! rememberDismiss }
					onChange={ ( v: boolean ) => setAttributes( { rememberDismiss: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Icon panel — show/hide, the glyph, its position, size and colour.
 */
export const NoticeIconPanel = ( { attributes, setAttributes }: NoticePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showIcon, icon, iconPosition, iconSize, iconColor } = attributes;

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show icon', 'flexa-block' ) }
				checked={ showIcon !== false }
				onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) }
			/>
			{ showIcon !== false && (
				<>
					<IconPicker
						label={ __( 'Icon', 'flexa-block' ) }
						value={ icon || {} }
						onChange={ ( v ) => setAttributes( { icon: v } ) }
					/>
					<Segmented
						label={ __( 'Icon position', 'flexa-block' ) }
						value={ iconPosition || 'left' }
						onChange={ ( v ) => setAttributes( { iconPosition: v as NoticeAttributes[ 'iconPosition' ] } ) }
						options={ ICON_POSITION_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Icon size', 'flexa-block' ) }
						value={ iconSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 120, em: 8, rem: 8, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Icon color', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Title panel — show/hide, typography and colour.
 */
export const NoticeTitlePanel = ( { attributes, setAttributes }: NoticePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show title', 'flexa-block' ) }
				checked={ attributes.showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Message panel — body-text typography and colour.
 */
export const NoticeMessagePanel = ( { attributes, setAttributes }: NoticePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.contentTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { contentTypography: patchDevice( attributes.contentTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Message', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.contentColor || {} } onChange={ ( v ) => setAttributes( { contentColor: v } ) } />
		</PanelBody>
	);
};
