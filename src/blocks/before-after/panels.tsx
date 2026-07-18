/**
 * Before / After block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) come from @components; these cover the parts unique to the
 * comparison slider: the two images, the split behaviour (orientation, initial
 * position, interaction, labels), the frame size, and the handle / divider /
 * label styling. Following the "prefer theme styles" rule nothing is styled by
 * default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl, BaseControl, Button, Flex } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	DualColor,
	useDevice,
	CONTENT_ALIGN_OPTIONS,
	OBJECT_FIT_OPTIONS,
	ASPECT_RATIO_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, WEIGHT_UNITS } from '@utils';
import type { BeforeAfterAttributes, ImageMedia, LengthValue, PanelProps } from '../../types';

type Props = PanelProps< BeforeAfterAttributes >;

const EMPTY_MEDIA: ImageMedia = { id: null, url: '', alt: '', width: null, height: null };

/**
 * A single media slot with a preview thumbnail plus Select / Replace / Remove.
 * Shared by the two slots in the Media panel so the picker isn't written twice.
 */
const MediaField = ( { label, value, onChange }: { label: string; value?: ImageMedia; onChange: ( m: ImageMedia ) => void } ): JSX.Element => (
	<BaseControl __nextHasNoMarginBottom label={ label }>
		<MediaUploadCheck>
			<MediaUpload
				allowedTypes={ [ 'image' ] }
				value={ value?.id ?? undefined }
				onSelect={ ( m: { id: number; url: string; alt?: string; width?: number; height?: number } ) =>
					onChange( { id: m.id, url: m.url, alt: m.alt || '', width: m.width ?? null, height: m.height ?? null } )
				}
				render={ ( { open }: { open: () => void } ) => (
					<>
						{ value?.url && (
							<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
								<img src={ value.url } alt="" />
							</button>
						) }
						<Flex gap={ 2 } justify="flex-start">
							<Button variant="secondary" onClick={ open }>
								{ value?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
							</Button>
							{ value?.url && (
								<Button isDestructive variant="tertiary" onClick={ () => onChange( { ...EMPTY_MEDIA } ) }>
									{ __( 'Remove', 'flexa-block' ) }
								</Button>
							) }
						</Flex>
					</>
				) }
			/>
		</MediaUploadCheck>
	</BaseControl>
);

/**
 * Media panel — the two comparison images, their alt text, how they fill the
 * frame (object-fit) and an optional locked aspect ratio.
 */
export const MediaPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { beforeImage, afterImage, beforeAlt, afterAlt, objectFit, aspectRatio } = attributes;
	const ratio = aspectRatio || {};

	return (
		<PanelBody title={ __( 'Images', 'flexa-block' ) } initialOpen={ true }>
			<MediaField label={ __( 'Before Image', 'flexa-block' ) } value={ beforeImage } onChange={ ( m ) => setAttributes( { beforeImage: m } ) } />
			<TextControl __nextHasNoMarginBottom label={ __( 'Before Alt Text', 'flexa-block' ) } value={ beforeAlt || '' } onChange={ ( v: string ) => setAttributes( { beforeAlt: v } ) } />

			<MediaField label={ __( 'After Image', 'flexa-block' ) } value={ afterImage } onChange={ ( m ) => setAttributes( { afterImage: m } ) } />
			<TextControl __nextHasNoMarginBottom label={ __( 'After Alt Text', 'flexa-block' ) } value={ afterAlt || '' } onChange={ ( v: string ) => setAttributes( { afterAlt: v } ) } />

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Object Fit', 'flexa-block' ) }
				value={ objectFit || 'cover' }
				options={ OBJECT_FIT_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { objectFit: v } ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Lock aspect ratio', 'flexa-block' ) }
				help={ __( 'Keep both images the same height, even if they differ.', 'flexa-block' ) }
				checked={ !! ratio.enabled }
				onChange={ ( v: boolean ) => setAttributes( { aspectRatio: { ...ratio, enabled: v } } ) }
			/>
			{ ratio.enabled && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Aspect Ratio', 'flexa-block' ) }
					value={ ratio.ratio || '16/9' }
					options={ ASPECT_RATIO_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { aspectRatio: { ...ratio, ratio: v } } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Comparison panel — split direction, starting handle position, how visitors
 * move it, and the corner labels.
 */
export const ComparePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { orientation, initialPosition, interaction, showLabels, beforeLabel, afterLabel } = attributes;

	return (
		<PanelBody title={ __( 'Comparison', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Orientation', 'flexa-block' ) }
				value={ orientation || 'horizontal' }
				onChange={ ( v ) => setAttributes( { orientation: v as BeforeAfterAttributes[ 'orientation' ] } ) }
				options={ [
					{ value: 'horizontal', label: __( 'Horizontal', 'flexa-block' ) },
					{ value: 'vertical', label: __( 'Vertical', 'flexa-block' ) },
				] }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Start Position', 'flexa-block' ) }
				value={ initialPosition ?? 50 }
				min={ 0 }
				max={ 100 }
				onChange={ ( v?: number ) => setAttributes( { initialPosition: v ?? 50 } ) }
			/>
			<Segmented
				label={ __( 'Move On', 'flexa-block' ) }
				value={ interaction || 'drag' }
				onChange={ ( v ) => setAttributes( { interaction: v as BeforeAfterAttributes[ 'interaction' ] } ) }
				options={ [
					{ value: 'drag', label: __( 'Drag', 'flexa-block' ) },
					{ value: 'hover', label: __( 'Hover', 'flexa-block' ) },
				] }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show labels', 'flexa-block' ) }
				checked={ showLabels !== false }
				onChange={ ( v: boolean ) => setAttributes( { showLabels: v } ) }
			/>
			{ showLabels !== false && (
				<>
					<TextControl __nextHasNoMarginBottom label={ __( 'Before Label', 'flexa-block' ) } value={ beforeLabel ?? 'Before' } onChange={ ( v: string ) => setAttributes( { beforeLabel: v } ) } />
					<TextControl __nextHasNoMarginBottom label={ __( 'After Label', 'flexa-block' ) } value={ afterLabel ?? 'After' } onChange={ ( v: string ) => setAttributes( { afterLabel: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Size panel — the frame's max width, its alignment on the page, and a minimum
 * height for the comparison box.
 */
export const SizePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { maxWidth, align, size } = attributes;
	const width = rawDevice( maxWidth, device );
	const alignVal = align?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	const minH = rawDevice( size, device ).minHeight || {};

	return (
		<PanelBody title={ __( 'Size', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Max Width', 'flexa-block' ) }
				value={ width }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 2000, '%': 100, vw: 100, em: 100, rem: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: patchDevice( maxWidth, device, v ) } ) }
			/>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ alignVal }
					responsive
					onChange={ ( v ) => setAttributes( { align: { ...align, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
			<SliderUnit
				label={ __( 'Min Height', 'flexa-block' ) }
				value={ minH }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, vh: 100, '%': 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { size: patchDevice( size, device, { minHeight: v } ) } ) }
			/>
		</PanelBody>
	);
};

/**
 * Handle panel — the divider line width + colour, the grip size, and the corner
 * label colours.
 */
export const HandlePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { handleColor, handleSize, dividerWidth, showLabels, labelColor, labelBackground } = attributes;

	return (
		<PanelBody title={ __( 'Handle & Labels', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Handle Color', 'flexa-block' ) } value={ handleColor || {} } onChange={ ( v ) => setAttributes( { handleColor: v } ) } />
			<SliderUnit
				label={ __( 'Handle Size', 'flexa-block' ) }
				value={ handleSize || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 16 }
				max={ { px: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { handleSize: v } ) }
			/>
			<SliderUnit
				label={ __( 'Divider Width', 'flexa-block' ) }
				value={ dividerWidth || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 20 } }
				onChange={ ( v: LengthValue ) => setAttributes( { dividerWidth: v } ) }
			/>
			{ showLabels !== false && (
				<>
					<DualColor label={ __( 'Label Text', 'flexa-block' ) } value={ labelColor || {} } onChange={ ( v ) => setAttributes( { labelColor: v } ) } />
					<DualColor label={ __( 'Label Background', 'flexa-block' ) } value={ labelBackground || {} } onChange={ ( v ) => setAttributes( { labelBackground: v } ) } />
				</>
			) }
		</PanelBody>
	);
};
