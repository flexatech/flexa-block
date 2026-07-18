/**
 * Team Member block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) plus
 * the shared typography controls and option sets come from @components; these
 * cover the parts unique to a team-member card: the photo & layout, the name /
 * role / bio text and the linked social icons. Following the "prefer theme
 * styles" rule nothing is styled by default — only the values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, BaseControl, Flex, Button } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	DualColor,
	TypographyControls,
	IconPicker,
	ItemListPanel,
	useDevice,
	CONTENT_ALIGN_OPTIONS,
	TEXT_TAG_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS, HTML_TAGS } from '@utils';
import type {
	ControlOption,
	IconValue,
	ImageMedia,
	LengthValue,
	LinkAttr,
	PanelProps,
	TeamMemberAttributes,
	TeamSocialItem,
	TypographyDevice,
} from '../../types';

type TMPanelProps = PanelProps< TeamMemberAttributes >;

/** Where the photo sits relative to the text (three values → Segmented). */
const IMAGE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'top', label: __( 'Top', 'flexa-block' ) },
	{ value: 'left', label: __( 'Left', 'flexa-block' ) },
	{ value: 'right', label: __( 'Right', 'flexa-block' ) },
];

/** The photo's silhouette (implemented as border-radius on the image). */
const IMAGE_SHAPE_OPTIONS: ControlOption[] = [
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
];

/** When a beside (left/right) layout collapses to a column (SelectControl). */
const STACK_OPTIONS: ControlOption[] = [
	{ value: 'none', label: __( 'Never', 'flexa-block' ) },
	{ value: 'tablet', label: __( 'Tablet & below', 'flexa-block' ) },
	{ value: 'mobile', label: __( 'Mobile', 'flexa-block' ) },
];

/** A fresh social item for the "Add link" button. */
const newSocialItem = (): TeamSocialItem => ( {
	icon: {},
	link: { url: '', target: '', rel: '' },
} );

/** The collapsed-header preview for one social item. */
const socialPreview = ( item: TeamSocialItem ): JSX.Element | undefined => {
	if ( item.icon?.source === 'upload' && item.icon.url ) {
		return <img src={ item.icon.url } alt="" width={ 18 } height={ 18 } />;
	}
	return item.icon?.markup ? <span dangerouslySetInnerHTML={ { __html: item.icon.markup } } /> : undefined;
};

/**
 * Layout panel — photo position & shape, content alignment, media gap, stacking,
 * card max width and the wrapper tag.
 */
export const TeamMemberLayoutPanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { imagePosition, imageShape, alignment, stackOn, mediaGap, maxWidth, htmlTag } = attributes;
	const isBeside = imagePosition === 'left' || imagePosition === 'right';
	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Photo position', 'flexa-block' ) }
				value={ imagePosition || 'top' }
				onChange={ ( v ) => setAttributes( { imagePosition: v as TeamMemberAttributes[ 'imagePosition' ] } ) }
				options={ IMAGE_POSITION_OPTIONS }
			/>
			<Segmented
				label={ __( 'Photo shape', 'flexa-block' ) }
				value={ imageShape || 'circle' }
				onChange={ ( v ) => setAttributes( { imageShape: v as TeamMemberAttributes[ 'imageShape' ] } ) }
				options={ IMAGE_SHAPE_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ align }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			{ isBeside && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Stack photo on', 'flexa-block' ) }
					value={ stackOn || 'none' }
					options={ STACK_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { stackOn: v as TeamMemberAttributes[ 'stackOn' ] } ) }
				/>
			) }
			<SliderUnit
				label={ __( 'Photo gap', 'flexa-block' ) }
				value={ mediaGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { mediaGap: { ...mediaGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, em: 80, rem: 80, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
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
 * Photo panel — the image and its width.
 */
export const TeamMemberPhotoPanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { image, imageWidth } = attributes;
	const img: ImageMedia = image || {};

	return (
		<PanelBody title={ __( 'Photo', 'flexa-block' ) } initialOpen={ false }>
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
				label={ __( 'Photo width', 'flexa-block' ) }
				value={ imageWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 400, em: 30, rem: 30, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { imageWidth: { ...imageWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Name panel — heading tag, typography, colour and spacing below.
 */
export const TeamMemberNamePanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.nameTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { nameTypography: patchDevice( attributes.nameTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Name', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ attributes.nameTag || 'h3' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { nameTag: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.nameColor || {} } onChange={ ( v ) => setAttributes( { nameColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing below', 'flexa-block' ) }
				value={ attributes.nameSpacing?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { nameSpacing: { ...attributes.nameSpacing, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Role panel — typography, colour and spacing below.
 */
export const TeamMemberRolePanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.roleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { roleTypography: patchDevice( attributes.roleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Role', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.roleColor || {} } onChange={ ( v ) => setAttributes( { roleColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing below', 'flexa-block' ) }
				value={ attributes.roleSpacing?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { roleSpacing: { ...attributes.roleSpacing, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Bio panel — body-text typography, colour and spacing below.
 */
export const TeamMemberBioPanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.bioTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { bioTypography: patchDevice( attributes.bioTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Bio', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.bioColor || {} } onChange={ ( v ) => setAttributes( { bioColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing below', 'flexa-block' ) }
				value={ attributes.bioSpacing?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { bioSpacing: { ...attributes.bioSpacing, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/** Body controls for one social item: the icon picker and its link. */
const SocialItemBody = ( { item, update }: { item: TeamSocialItem; update: ( patch: Partial< TeamSocialItem > ) => void } ): JSX.Element => {
	const link: LinkAttr = item.link || {};

	return (
		<>
			<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link', 'flexa-block' ) }
				type="url"
				placeholder="https://"
				value={ link.url || '' }
				onChange={ ( v: string ) => update( { link: { ...link, url: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ link.target === '_blank' }
				onChange={ ( v: boolean ) => update( { link: { ...link, target: v ? '_blank' : '', rel: v ? 'noopener noreferrer' : '' } } ) }
			/>
		</>
	);
};

/**
 * Social panel — the list of linked social icons (add, reorder, edit, remove)
 * via the shared ItemListPanel, plus the shared size, gap and tint colours.
 */
export const TeamMemberSocialPanel = ( { attributes, setAttributes }: TMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const items: TeamSocialItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<PanelBody title={ __( 'Social links', 'flexa-block' ) } initialOpen={ false }>
			<ItemListPanel< TeamSocialItem >
				title={ __( 'Links', 'flexa-block' ) }
				addLabel={ __( 'Add link', 'flexa-block' ) }
				items={ items }
				onChange={ ( next ) => setAttributes( { items: next } ) }
				newItem={ newSocialItem }
				renderHeader={ ( item, index ) => ( {
					preview: socialPreview( item ),
					title: item.icon?.name || __( 'Social link', 'flexa-block' ) + ' ' + ( index + 1 ),
				} ) }
				renderBody={ ( item, _index, update ) => <SocialItemBody item={ item } update={ update } /> }
			/>
			<SliderUnit
				label={ __( 'Icon size', 'flexa-block' ) }
				value={ attributes.socialSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 6, rem: 6, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { socialSize: { ...attributes.socialSize, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Icon gap', 'flexa-block' ) }
				value={ attributes.socialGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { socialGap: { ...attributes.socialGap, [ device ]: v } } ) }
			/>
			<DualColor label={ __( 'Icon color', 'flexa-block' ) } value={ attributes.socialColor || {} } onChange={ ( v ) => setAttributes( { socialColor: v } ) } />
			<DualColor label={ __( 'Hover color', 'flexa-block' ) } value={ attributes.socialHoverColor || {} } onChange={ ( v ) => setAttributes( { socialHoverColor: v } ) } />
		</PanelBody>
	);
};
