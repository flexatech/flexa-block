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
import { InspectorControls, useBlockProps, MediaPlaceholder } from '@wordpress/block-editor';

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
 * Banner edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< BannerAttributes > ): JSX.Element {
	const { containerType, overlay, background, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const bg = background || {};

	// A banner leads with a backdrop — an image (its identity), or a colour/gradient
	// hero. Until the user picks one, show the "add image" placeholder so the block
	// reads as an image banner (distinct from the CTA block).
	const hasBackdrop =
		( bg.type === 'image' && !! bg.image?.url ) ||
		( bg.type === 'classic' && !! bg.color?.light ) ||
		( bg.type === 'gradient' && !! bg.gradient?.light );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-banner',
			`flexa-banner--${ containerType || 'full-width' }`,
			! hasBackdrop && 'flexa-banner--placeholder',
			blockId && `flexa-banner-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const hasOverlay = overlay?.type && overlay.type !== 'none';

	// Hover state — inline styles can't express `:hover`, so mirror the shared promo
	// button hover rules in a scoped <style> (light values, only what's set).
	const hoverCss = blockId
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
								<PromoContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<PromoHeadingPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PromoDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PromoButtonsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ hasBackdrop ? (
					<>
						{ hasOverlay && <div className="flexa-banner__overlay" style={ buildOverlayStyle( overlay ) } aria-hidden="true" /> }
						<div className="flexa-banner__box" style={ buildBoxStyle( attributes, device ) }>
							<PromoContent attributes={ attributes } setAttributes={ setAttributes } />
						</div>
					</>
				) : (
					<MediaPlaceholder
						icon="format-image"
						labels={ {
							title: __( 'Banner', 'flexa-block' ),
							instructions: __( 'Upload or pick an image for the banner background — your heading, text and buttons sit on top of it. Or set a colour/gradient in the Background panel.', 'flexa-block' ),
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
