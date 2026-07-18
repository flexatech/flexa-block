/**
 * Modal block — block-specific inspector panels.
 *
 * The shared panels (spacing / border / shadow / visibility / animation) come
 * from @components; these cover the parts unique to the modal: the trigger
 * (button / text / image / icon) and its styling, the modal box size / padding /
 * background / radius, the overlay + close button and the open/close behaviour.
 * Following the "prefer theme styles" rule nothing is coloured by default — only
 * values the user picks produce CSS (the structural look lives in style.scss).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, BaseControl, Button, Flex } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	IconPicker,
	TypographyControls,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, WEIGHT_UNITS, SPACING_UNITS } from '@utils';
import type { BoxValue, ImageMedia, LengthValue, ModalAttributes, PanelProps, TypographyDevice } from '../../types';

type Props = PanelProps< ModalAttributes >;

const EMPTY_MEDIA: ImageMedia = { id: null, url: '', alt: '', width: null, height: null };

/** The four trigger appearances (short list → Segmented). */
const TRIGGER_TYPE_OPTIONS = [
	{ value: 'button', label: __( 'Button', 'flexa-block' ) },
	{ value: 'text', label: __( 'Text', 'flexa-block' ) },
	{ value: 'image', label: __( 'Image', 'flexa-block' ) },
	{ value: 'icon', label: __( 'Icon', 'flexa-block' ) },
];

/**
 * Trigger panel — what the visitor clicks to open the modal (button / text /
 * image / icon), its content, icon size and alignment on the page.
 */
export const TriggerPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { triggerType, triggerText, triggerIcon, triggerImage, triggerIconSize, triggerAlign } = attributes;
	const type = triggerType || 'button';
	const alignVal = triggerAlign?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Trigger', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Type', 'flexa-block' ) }
				value={ type }
				onChange={ ( v ) => setAttributes( { triggerType: v as ModalAttributes[ 'triggerType' ] } ) }
				options={ TRIGGER_TYPE_OPTIONS }
			/>

			{ ( type === 'button' || type === 'text' ) && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Trigger Text', 'flexa-block' ) }
					value={ triggerText ?? 'Open' }
					onChange={ ( v: string ) => setAttributes( { triggerText: v } ) }
				/>
			) }

			{ type === 'icon' && (
				<IconPicker
					label={ __( 'Icon', 'flexa-block' ) }
					value={ triggerIcon || {} }
					onChange={ ( v ) => setAttributes( { triggerIcon: v } ) }
				/>
			) }

			{ type === 'image' && (
				<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ triggerImage?.id ?? undefined }
							onSelect={ ( m: { id: number; url: string; alt?: string; width?: number; height?: number } ) =>
								setAttributes( { triggerImage: { id: m.id, url: m.url, alt: m.alt || '', width: m.width ?? null, height: m.height ?? null } } )
							}
							render={ ( { open }: { open: () => void } ) => (
								<>
									{ triggerImage?.url && (
										<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
											<img src={ triggerImage.url } alt="" />
										</button>
									) }
									<Flex gap={ 2 } justify="flex-start">
										<Button variant="secondary" onClick={ open }>
											{ triggerImage?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
										</Button>
										{ triggerImage?.url && (
											<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { triggerImage: { ...EMPTY_MEDIA } } ) }>
												{ __( 'Remove', 'flexa-block' ) }
											</Button>
										) }
									</Flex>
								</>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }

			{ ( type === 'icon' || type === 'image' ) && (
				<SliderUnit
					label={ __( 'Icon Size', 'flexa-block' ) }
					value={ triggerIconSize || {} }
					units={ WEIGHT_UNITS }
					defaultUnit="px"
					min={ 12 }
					max={ { px: 200 } }
					onChange={ ( v: LengthValue ) => setAttributes( { triggerIconSize: v } ) }
				/>
			) }

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ alignVal }
					responsive
					onChange={ ( v ) => setAttributes( { triggerAlign: { ...triggerAlign, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
		</PanelBody>
	);
};

/**
 * Trigger style panel — the trigger's typography, text + background colours (with
 * hover), padding and corner radius.
 */
export const TriggerStylePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { triggerTypography, triggerTextColor, triggerTextColorHover, triggerBackground, triggerBackgroundHover, triggerPadding, triggerRadius } = attributes;

	const typo = rawDevice( triggerTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { triggerTypography: patchDevice( triggerTypography, device, patch ) } );
	const pad = rawDevice( triggerPadding, device );

	return (
		<PanelBody title={ __( 'Trigger Style', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text Color', 'flexa-block' ) } value={ triggerTextColor || {} } onChange={ ( v ) => setAttributes( { triggerTextColor: v } ) } />
			<DualColor label={ __( 'Text Hover', 'flexa-block' ) } value={ triggerTextColorHover || {} } onChange={ ( v ) => setAttributes( { triggerTextColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ triggerBackground || {} } onChange={ ( v ) => setAttributes( { triggerBackground: v } ) } />
			<DualColor label={ __( 'Background Hover', 'flexa-block' ) } value={ triggerBackgroundHover || {} } onChange={ ( v ) => setAttributes( { triggerBackgroundHover: v } ) } />
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				value={ pad }
				units={ SPACING_UNITS }
				responsive
				onChange={ ( v: BoxValue ) => setAttributes( { triggerPadding: patchDevice( triggerPadding, device, v ) } ) }
			/>
			<SliderUnit
				label={ __( 'Corner Radius', 'flexa-block' ) }
				value={ triggerRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, '%': 50, em: 10, rem: 10 } }
				onChange={ ( v: LengthValue ) => setAttributes( { triggerRadius: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Modal box panel — the dialog box size (width + max height), padding, background
 * and corner radius.
 */
export const ModalBoxPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { modalWidth, modalMaxHeight, modalPadding, modalBackground, modalRadius } = attributes;

	const width = rawDevice( modalWidth, device );
	const maxHeight = rawDevice( modalMaxHeight, device );
	const pad = rawDevice( modalPadding, device );

	return (
		<PanelBody title={ __( 'Modal Box', 'flexa-block' ) } initialOpen={ true }>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ width }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1400, '%': 100, vw: 100, em: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { modalWidth: patchDevice( modalWidth, device, v ) } ) }
			/>
			<SliderUnit
				label={ __( 'Max Height', 'flexa-block' ) }
				value={ maxHeight }
				units={ LENGTH_UNITS }
				defaultUnit="vh"
				max={ { px: 2000, '%': 100, vh: 100, em: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { modalMaxHeight: patchDevice( modalMaxHeight, device, v ) } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				value={ pad }
				units={ SPACING_UNITS }
				responsive
				onChange={ ( v: BoxValue ) => setAttributes( { modalPadding: patchDevice( modalPadding, device, v ) } ) }
			/>
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ modalBackground || {} } onChange={ ( v ) => setAttributes( { modalBackground: v } ) } />
			<SliderUnit
				label={ __( 'Corner Radius', 'flexa-block' ) }
				value={ modalRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, '%': 50, em: 10, rem: 10 } }
				onChange={ ( v: LengthValue ) => setAttributes( { modalRadius: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Overlay & close panel — the backdrop colour behind the box and the close
 * button's colour, size and placement (inside / outside the box).
 */
export const OverlayClosePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { overlayColor, closeIconColor, closeIconSize, closePosition } = attributes;

	return (
		<PanelBody title={ __( 'Overlay & Close', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Overlay Color', 'flexa-block' ) } value={ overlayColor || {} } onChange={ ( v ) => setAttributes( { overlayColor: v } ) } />
			<DualColor label={ __( 'Close Color', 'flexa-block' ) } value={ closeIconColor || {} } onChange={ ( v ) => setAttributes( { closeIconColor: v } ) } />
			<SliderUnit
				label={ __( 'Close Size', 'flexa-block' ) }
				value={ closeIconSize || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 12 }
				max={ { px: 80 } }
				onChange={ ( v: LengthValue ) => setAttributes( { closeIconSize: v } ) }
			/>
			<Segmented
				label={ __( 'Close Position', 'flexa-block' ) }
				value={ closePosition || 'inside' }
				onChange={ ( v ) => setAttributes( { closePosition: v as ModalAttributes[ 'closePosition' ] } ) }
				options={ [
					{ value: 'inside', label: __( 'Inside', 'flexa-block' ) },
					{ value: 'outside', label: __( 'Outside', 'flexa-block' ) },
				] }
			/>
		</PanelBody>
	);
};

/**
 * Behaviour panel — how the modal opens and closes: show it only once per
 * visitor, close on an overlay click, close on the Escape key.
 */
export const BehaviourPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { showOnce, closeOnOverlay, closeOnEsc } = attributes;

	return (
		<PanelBody title={ __( 'Behaviour', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show only once', 'flexa-block' ) }
				help={ __( 'Remember, per visitor, that the modal has been opened and do not auto-show it again.', 'flexa-block' ) }
				checked={ !! showOnce }
				onChange={ ( v: boolean ) => setAttributes( { showOnce: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Close on overlay click', 'flexa-block' ) }
				checked={ closeOnOverlay !== false }
				onChange={ ( v: boolean ) => setAttributes( { closeOnOverlay: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Close on Escape key', 'flexa-block' ) }
				checked={ closeOnEsc !== false }
				onChange={ ( v: boolean ) => setAttributes( { closeOnEsc: v } ) }
			/>
		</PanelBody>
	);
};
