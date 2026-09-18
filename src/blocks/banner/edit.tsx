/**
 * Banner block — editor component.
 *
 * Assembles the banner-specific panels (./panels — layout + overlay) with the
 * shared promo panels (content / heading / description / buttons) and the shared
 * foundational panels (@components). The canvas renders the banner wrapper with a
 * background, an optional colour/gradient overlay and the shared PromoContent
 * (heading / description / CTA buttons) edited inline. Inline styles mirror the
 * PHP CSS generator; nothing is styled by default so the banner inherits the
 * theme + the base style.scss until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, MediaPlaceholder, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	PromoContentPanel,
	PromoHeadingPanel,
	PromoDescriptionPanel,
	PromoButtonsPanel,
	PromoContent,
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
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import { BannerLayoutPanel, BannerOverlayPanel } from './panels';
import type { BannerAttributes, DeviceKey, EditProps, ImageOverlayAttr } from '../../types';

/** Banner wrapper preview: background, border, shadow, sizing + content placement. */
const buildWrapperStyle = ( attributes: BannerAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const isBoxed = attributes.containerType !== 'full-width';

	// Vertical placement of the content within the min-height.
	if ( attributes.verticalAlign ) s.justifyContent = attributes.verticalAlign;

	// Horizontal placement of the (max-width-constrained) content block.
	const align = attributes.contentAlign?.[ device ] || attributes.contentAlign?.desktop || '';
	if ( align ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	const width = effective( isBoxed ? attributes.widthBoxed : attributes.widthFullWidth, device );
	if ( width.value ) {
		const wu = width.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( width.value, wu );
	}

	const minHeight = effective( attributes.size, device ).minHeight;
	if ( minHeight?.value ) s.minHeight = withUnit( minHeight.value, minHeight.unit || 'px' );

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, rawDevice( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Content box preview: full-width by default; a max-width centres it on the grid,
 *  with the content column aligned inside it exactly like the front end. */
const buildBoxStyle = ( attributes: BannerAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { width: '100%', display: 'flex', flexDirection: 'column' };
	const align = attributes.contentAlign?.[ device ] || attributes.contentAlign?.desktop || '';
	if ( align ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
	const box = effective( attributes.contentBoxWidth, device );
	if ( box.value ) {
		s.maxWidth = withUnit( box.value, box.unit || 'px' );
		s.marginInline = 'auto';
	}
	return s;
};

/** Overlay preview: colour/gradient fill + opacity + blend mode. */
const buildOverlayStyle = ( overlay: ImageOverlayAttr = {} ): CssProps => {
	const s: CssProps = {};
	if ( overlay.type === 'color' && overlay.color?.light ) {
		s.background = overlay.color.light;
	} else if ( overlay.type === 'gradient' && overlay.gradient?.light ) {
		s.background = overlay.gradient.light;
	}
	s.opacity = String( ( typeof overlay.opacity === 'number' ? overlay.opacity : 50 ) / 100 );
	if ( overlay.blendMode ) s.mixBlendMode = overlay.blendMode;
	return s;
};

/**
 * Optional top region above the heading — for a breadcrumb, an eyebrow (text) or a
 * meta line (icon-list). Kept as InnerBlocks so any of these blocks can sit inside
 * the banner over the background/overlay, which the fixed promo fields can't hold.
 *
 * This list applies to `contentSource: 'fields'` only. It is a curated set on
 * purpose there: the region is a narrow strip above the promo fields, styled as a
 * 12px-gap column with a trailing margin, so an arbitrary block would fight that
 * layout. With `contentSource: 'custom'` the region IS the content and the
 * restriction is lifted entirely.
 */
const BANNER_INNER_ALLOWED = [
	'flexa/breadcrumb',
	'flexa/heading',
	'flexa/text',
	'flexa/icon-list',
	'flexa/icon',
	'flexa/button',
];

/**
 * Banner edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< BannerAttributes > ): JSX.Element {
	const { contentSource, containerType, overlay, background, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	// `fields` is the default, so a banner saved before this option existed takes
	// exactly the path it always took.
	const isCustom = 'custom' === ( contentSource || 'fields' );

	useBlockId( clientId, blockId, setAttributes );

	const bg = background || {};

	// A banner leads with a backdrop — an image (its identity), or a colour/gradient
	// hero. Until the user picks one, show the "add image" placeholder so the block
	// reads as an image banner (distinct from the CTA block).
	const hasBackdrop =
		( bg.type === 'image' && !! bg.image?.url ) ||
		( bg.type === 'classic' && !! bg.color?.light ) ||
		( bg.type === 'gradient' && !! bg.gradient?.light );

	// In `custom` the InnerBlocks region IS the content, so the "pick a background
	// image" placeholder must not stand in front of it — the author would have
	// nowhere to add blocks. A hero built from blocks is perfectly usable with no
	// backdrop at all.
	const showPlaceholder = ! hasBackdrop && ! isCustom;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-banner',
			`flexa-banner--${ containerType || 'full-width' }`,
			// Only the custom variant gets a class: `fields` is the base styling, so
			// leaving its markup untouched keeps every saved banner byte-identical.
			isCustom && 'flexa-banner--content-custom',
			showPlaceholder && 'flexa-banner--placeholder',
			blockId && `flexa-banner-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const hasOverlay = overlay?.type && overlay.type !== 'none';

	// The InnerBlocks region. In `fields` it is the narrow strip above the promo
	// content (breadcrumb / eyebrow / meta, curated list). In `custom` it is the
	// whole content: no allow-list, and its own class so the strip's column layout
	// and trailing margin don't reshape arbitrary blocks.
	const innerBlocksProps = useInnerBlocksProps(
		{ className: isCustom ? 'flexa-banner__content-blocks' : 'flexa-banner__top' },
		{
			...( isCustom ? {} : { allowedBlocks: BANNER_INNER_ALLOWED } ),
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	// Hover state — inline styles can't express `:hover`, so mirror the shared promo
	// button hover rules in a scoped <style> (light values, only what's set). The
	// promo buttons don't exist in `custom`, so neither does their hover CSS.
	const hoverCss =
		blockId && ! isCustom
		? editorCss( [
				{ selector: `.flexa-banner-${ blockId } .flexa-promo__button--primary:hover`, prop: 'color', value: attributes.primaryHover?.text?.light },
				{ selector: `.flexa-banner-${ blockId } .flexa-promo__button--primary:hover`, prop: 'background-color', value: attributes.primaryHover?.background?.light },
				{ selector: `.flexa-banner-${ blockId } .flexa-promo__button--secondary:hover`, prop: 'color', value: attributes.secondaryHover?.text?.light },
				{ selector: `.flexa-banner-${ blockId } .flexa-promo__button--secondary:hover`, prop: 'background-color', value: attributes.secondaryHover?.background?.light },
		  ] )
		: '';

	const onSelectImage = ( media: { id: number; url: string } ) =>
		setAttributes( { background: { ...bg, type: 'image', image: { ...( bg.image || {} ), id: media.id, url: media.url } } } );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-banner-inspector">
					<InspectorTabs
						layout={
							<>
								<BannerLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ /* The promo panels edit fields that `custom` does not render, so
								     showing them would be offering controls with no effect. */ }
								{ ! isCustom && <PromoContentPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								{ ! isCustom && (
									<>
										<PromoHeadingPanel attributes={ attributes } setAttributes={ setAttributes } />
										<PromoDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
										<PromoButtonsPanel attributes={ attributes } setAttributes={ setAttributes } />
									</>
								) }
								<BannerOverlayPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				{ ! showPlaceholder ? (
					<>
						{ hasOverlay && <div className="flexa-banner__overlay" style={ buildOverlayStyle( overlay ) } aria-hidden="true" /> }
						<div className="flexa-banner__box" style={ buildBoxStyle( attributes, device ) }>
							<div { ...innerBlocksProps } />
							{ ! isCustom && <PromoContent attributes={ attributes } setAttributes={ setAttributes } /> }
						</div>
					</>
				) : (
					<MediaPlaceholder
						icon="format-image"
						labels={ {
							title: __( 'Banner', 'flexa-block' ),
							instructions: __( 'Upload or pick an image for the banner background — your heading, text and buttons sit on top of it. Or set a color/gradient in the Background panel.', 'flexa-block' ),
						} }
						allowedTypes={ [ 'image' ] }
						accept="image/*"
						onSelect={ onSelectImage }
					/>
				) }
			</div>
		</>
	);
}
