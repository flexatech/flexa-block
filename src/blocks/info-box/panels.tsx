/**
 * Info Box block — info-box-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) plus
 * the shared typography controls and option sets come from @components; these
 * cover the parts unique to an info box: the layout & media, the prefix / title /
 * description text, an optional divider and the CTA button. Following the "prefer
 * theme styles" rule nothing is styled by default — only the values the user
 * picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, BaseControl, Flex, Button } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	IconPicker,
	useDevice,
	CONTENT_ALIGN_OPTIONS,
	TEXT_TAG_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS, WEIGHT_UNITS, HTML_TAGS, LINE_STYLE_OPTIONS } from '@utils';
import type { ButtonIconAttr, ColorPair, ControlOption, ImageMedia, InfoBoxAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type IBPanelProps = PanelProps< InfoBoxAttributes >;

/** Where the media sits relative to the text (two values → Segmented). */
const ICON_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'top', label: __( 'Top', 'flexa-block' ) },
	{ value: 'left', label: __( 'Left', 'flexa-block' ) },
];

/** When the row layout collapses to a column (SelectControl). */
const STACK_OPTIONS: ControlOption[] = [
	{ value: 'none', label: __( 'Never', 'flexa-block' ) },
	{ value: 'tablet', label: __( 'Tablet & below', 'flexa-block' ) },
	{ value: 'mobile', label: __( 'Mobile', 'flexa-block' ) },
];

/** Add or remove a token from a space-separated `rel` string. */
const toggleRel = ( rel: string | undefined, token: string, on: boolean ): string => {
	const parts = ( rel || '' ).split( /\s+/ ).filter( Boolean ).filter( ( t ) => t !== token );
	if ( on ) {
		parts.push( token );
	}
	return parts.join( ' ' );
};

const relHas = ( rel: string | undefined, token: string ): boolean => ( rel || '' ).split( /\s+/ ).includes( token );

/**
 * Layout panel — media position, content alignment, gaps, stacking, wrapper tag
 * and the element toggles (prefix / separator / button).
 */
export const InfoBoxLayoutPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { iconPosition, alignment, stackOn, mediaGap, contentGap, htmlTag, showPrefix, showSeparator, showButton } = attributes;
	const align = alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' );

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Media position', 'flexa-block' ) }
				value={ iconPosition || 'top' }
				onChange={ ( v ) => setAttributes( { iconPosition: v as InfoBoxAttributes[ 'iconPosition' ] } ) }
				options={ ICON_POSITION_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ align }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			{ iconPosition === 'left' && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Stack media on', 'flexa-block' ) }
					value={ stackOn || 'none' }
					options={ STACK_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { stackOn: v as InfoBoxAttributes[ 'stackOn' ] } ) }
				/>
			) }
			<SliderUnit
				label={ __( 'Media gap', 'flexa-block' ) }
				value={ mediaGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { mediaGap: { ...mediaGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Content gap', 'flexa-block' ) }
				value={ contentGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentGap: { ...contentGap, [ device ]: v } } ) }
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
				label={ __( 'Show prefix', 'flexa-block' ) }
				checked={ !! showPrefix }
				onChange={ ( v: boolean ) => setAttributes( { showPrefix: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show separator', 'flexa-block' ) }
				checked={ !! showSeparator }
				onChange={ ( v: boolean ) => setAttributes( { showSeparator: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show button', 'flexa-block' ) }
				checked={ !! showButton }
				onChange={ ( v: boolean ) => setAttributes( { showButton: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Media panel — icon or image, its size, colour and box styling.
 */
export const InfoBoxMediaPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showMedia, mediaType, icon, image, iconSize, imageWidth, iconColor, mediaBackground, mediaPadding, mediaRadius } = attributes;
	const img: ImageMedia = image || {};
	const type = mediaType || 'icon';

	return (
		<PanelBody title={ __( 'Media', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show media', 'flexa-block' ) }
				checked={ showMedia !== false }
				onChange={ ( v: boolean ) => setAttributes( { showMedia: v } ) }
			/>
			{ showMedia !== false && (
				<>
					<Segmented
						label={ __( 'Type', 'flexa-block' ) }
						value={ type }
						onChange={ ( v ) => setAttributes( { mediaType: v as InfoBoxAttributes[ 'mediaType' ] } ) }
						options={ [
							{ value: 'icon', label: __( 'Icon', 'flexa-block' ) },
							{ value: 'image', label: __( 'Image', 'flexa-block' ) },
						] }
					/>

					{ type === 'icon' ? (
						<>
							<IconPicker
								label={ __( 'Icon', 'flexa-block' ) }
								value={ icon || {} }
								onChange={ ( v ) => setAttributes( { icon: v } ) }
							/>
							<SliderUnit
								label={ __( 'Icon size', 'flexa-block' ) }
								value={ iconSize?.[ device ] || {} }
								units={ LENGTH_UNITS }
								defaultUnit="px"
								max={ { px: 200, em: 12, rem: 12, '%': 300 } }
								onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
							/>
							<DualColor label={ __( 'Icon color', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
						</>
					) : (
						<>
							<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
								<MediaUploadCheck>
									<MediaUpload
										allowedTypes={ [ 'image' ] }
										value={ img.id ?? undefined }
										onSelect={ ( media: { id: number; url: string; alt?: string } ) => setAttributes( { image: { id: media.id, url: media.url, alt: media.alt || '' } } ) }
										render={ ( { open }: { open: () => void } ) => (
											<>
												{ img.url && (
													<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
														<img src={ img.url } alt="" />
													</button>
												) }
												<Flex gap={ 2 } justify="flex-start">
													<Button variant="secondary" onClick={ open }>
														{ img.url ? __( 'Replace', 'flexa-block' ) : __( 'Select image', 'flexa-block' ) }
													</Button>
													{ img.url && (
														<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { image: { id: null, url: '', alt: '' } } ) }>
															{ __( 'Remove', 'flexa-block' ) }
														</Button>
													) }
												</Flex>
											</>
										) }
									/>
								</MediaUploadCheck>
							</BaseControl>
							<SliderUnit
								label={ __( 'Image width', 'flexa-block' ) }
								value={ imageWidth?.[ device ] || {} }
								units={ LENGTH_UNITS }
								defaultUnit="px"
								max={ { px: 600, em: 40, rem: 40, '%': 100 } }
								onChange={ ( v: LengthValue ) => setAttributes( { imageWidth: { ...imageWidth, [ device ]: v } } ) }
							/>
						</>
					) }

					<DualColor label={ __( 'Media background', 'flexa-block' ) } value={ mediaBackground || {} } onChange={ ( v ) => setAttributes( { mediaBackground: v } ) } />
					<Dimensions
						label={ __( 'Media padding', 'flexa-block' ) }
						responsive
						value={ mediaPadding?.[ device ] || {} }
						units={ SPACING_UNITS }
						onChange={ ( v ) => setAttributes( { mediaPadding: { ...mediaPadding, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Media radius', 'flexa-block' ) }
						value={ mediaRadius?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 300, em: 20, rem: 20, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { mediaRadius: { ...mediaRadius, [ device ]: v } } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Prefix panel — small eyebrow text above the title. Rendered only when enabled.
 */
export const InfoBoxPrefixPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.prefixTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { prefixTypography: patchDevice( attributes.prefixTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Prefix', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.prefixColor || {} } onChange={ ( v ) => setAttributes( { prefixColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Title panel — heading tag, typography and colour.
 */
export const InfoBoxTitlePanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ attributes.titleTag || 'h3' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Description panel — body-text typography and colour.
 */
export const InfoBoxDescriptionPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.descriptionTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { descriptionTypography: patchDevice( attributes.descriptionTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Description', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.descriptionColor || {} } onChange={ ( v ) => setAttributes( { descriptionColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Separator panel — divider between the title and the description. Rendered only
 * when enabled.
 */
export const InfoBoxSeparatorPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const { separatorWidth, separatorWeight, separatorStyle, separatorColor } = attributes;

	return (
		<PanelBody title={ __( 'Separator', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				showDevice={ false }
				value={ separatorWidth || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 400, '%': 100, em: 40, rem: 40 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorWidth: v } ) }
			/>
			<SliderUnit
				label={ __( 'Weight', 'flexa-block' ) }
				showDevice={ false }
				value={ separatorWeight || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 1 }
				max={ { px: 20 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorWeight: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Style', 'flexa-block' ) }
				value={ separatorStyle || 'solid' }
				options={ LINE_STYLE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { separatorStyle: v } ) }
			/>
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Button panel — the CTA link, its icon and colours. Rendered only when enabled.
 */
export const InfoBoxButtonPanel = ( { attributes, setAttributes }: IBPanelProps ): JSX.Element => {
	const { buttonUrl, buttonTarget, buttonRel, buttonIcon, buttonTextColor, buttonBgColor, buttonHover } = attributes;
	const iconCfg: ButtonIconAttr = buttonIcon || {};
	const hover = buttonHover || {};
	const setHover = ( patch: Partial< InfoBoxAttributes[ 'buttonHover' ] > ) => setAttributes( { buttonHover: { ...hover, ...patch } } );

	return (
		<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link URL', 'flexa-block' ) }
				type="url"
				value={ buttonUrl || '' }
				placeholder="https://"
				onChange={ ( v: string ) => setAttributes( { buttonUrl: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ buttonTarget === '_blank' }
				onChange={ ( v: boolean ) =>
					setAttributes( {
						buttonTarget: v ? '_blank' : '',
						buttonRel: toggleRel( toggleRel( buttonRel, 'noopener', v ), 'noreferrer', v ),
					} )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add nofollow', 'flexa-block' ) }
				checked={ relHas( buttonRel, 'nofollow' ) }
				onChange={ ( v: boolean ) => setAttributes( { buttonRel: toggleRel( buttonRel, 'nofollow', v ) } ) }
			/>
			<IconPicker
				label={ __( 'Icon', 'flexa-block' ) }
				value={ iconCfg }
				onChange={ ( v ) => setAttributes( { buttonIcon: { ...iconCfg, ...v } } ) }
			/>
			{ iconCfg.source && iconCfg.source !== 'none' && (
				<Segmented
					label={ __( 'Icon position', 'flexa-block' ) }
					value={ iconCfg.position || 'after' }
					onChange={ ( v ) => setAttributes( { buttonIcon: { ...iconCfg, position: v as 'before' | 'after' } } ) }
					options={ [
						{ value: 'before', label: __( 'Before', 'flexa-block' ) },
						{ value: 'after', label: __( 'After', 'flexa-block' ) },
					] }
				/>
			) }
			<DualColor label={ __( 'Text', 'flexa-block' ) } value={ buttonTextColor || {} } onChange={ ( v: ColorPair ) => setAttributes( { buttonTextColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ buttonBgColor || {} } onChange={ ( v: ColorPair ) => setAttributes( { buttonBgColor: v } ) } />
			<DualColor label={ __( 'Hover text', 'flexa-block' ) } value={ hover.text || {} } onChange={ ( v: ColorPair ) => setHover( { text: v } ) } />
			<DualColor label={ __( 'Hover background', 'flexa-block' ) } value={ hover.background || {} } onChange={ ( v: ColorPair ) => setHover( { background: v } ) } />
		</PanelBody>
	);
};
