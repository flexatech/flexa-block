/**
 * Social Icons block — editor component.
 *
 * Assembles the social-specific panels (./panels) with the shared inspector
 * panels (@components). Inline styles mirror the PHP CSS generator, and nothing
 * is styled by default so the icons keep the official brand artwork and the
 * theme's spacing until the user picks a value. All content editing (platform,
 * link, colour) happens in the inspector — the canvas only previews the row.
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
	VisibilityPanel,
	AnimationPanel,
	useBlockId,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	withUnit,
	spacingShorthand,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	CONTENT_ALIGN_TO_FLEX,
} from '@utils';
import type { CssProps } from '@utils';
import { SocialItemsPanel, SocialLayoutPanel } from './panels';
import { getPlatform, MonoIcon, CUSTOM_KEY } from './social-data';
import type { DeviceKey, EditProps, SocialIconAttributes, SocialIconItem } from '../../types';

/** Wrapper preview: spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: SocialIconAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
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

/** List preview: gap + alignment. */
const buildListStyle = ( attributes: SocialIconAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Icon-box preview: per-device square size. */
const buildIconStyle = ( attributes: SocialIconAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** The custom-mode tint for one item — its own picked colour, if any. */
const itemTint = ( item: SocialIconItem ): string => item.color?.light || '';

/**
 * Social Icons edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SocialIconAttributes > ): JSX.Element {
	const { blockId, items, className, responsiveVisibility, htmlTag, hoverEffect } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: SocialIconItem[] = Array.isArray( items ) ? items : [];

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const listStyle = buildListStyle( attributes, device );
	const iconStyle = buildIconStyle( attributes, device );

	// Alignment is a plain per-device string — cascade desktop → tablet → mobile
	// by hand (effective() only merges object-valued devices).
	const alignGroup = attributes.alignment || {};
	const align =
		device === 'mobile'
			? alignGroup.mobile || alignGroup.tablet || alignGroup.desktop
			: device === 'tablet'
				? alignGroup.tablet || alignGroup.desktop
				: alignGroup.desktop;
	if ( align && CONTENT_ALIGN_TO_FLEX[ align ] ) {
		listStyle.justifyContent = CONTENT_ALIGN_TO_FLEX[ align ];
	}

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-social-icon',
			blockId && `flexa-social-icon-${ blockId }`,
			hoverEffect && `flexa-social-icon--hover-${ hoverEffect }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real icons.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="social-icon" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-social-icon-inspector">
					<InspectorTabs
						layout={
							<>
								<SocialItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SocialLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } allowImage={ false } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<Tag { ...blockProps }>
				<div className="flexa-social-icon__list" style={ listStyle }>
					{ list.map( ( item, index ) => {
						const isCustomIcon = item.platform === CUSTOM_KEY;
						const platform = getPlatform( item.platform );

						// Resolve the icon element + its type class three ways: a picked
						// library/upload icon, a brand mark tinted (custom colour mode),
						// or the full-colour brand artwork.
						let iconEl: JSX.Element | null = null;
						let typeClass = '';
						if ( isCustomIcon ) {
							if ( item.icon?.source === 'upload' && item.icon.url ) {
								iconEl = <img className="flexa-icon" src={ item.icon.url } alt="" />;
							} else if ( item.icon?.markup ) {
								iconEl = <span dangerouslySetInnerHTML={ { __html: item.icon.markup } } />;
							} else {
								return null; // nothing picked yet — mirrors render.php.
							}
							typeClass = 'flexa-social-icon__item--custom-icon';
						} else if ( platform ) {
							iconEl = item.colorMode === 'custom' ? <MonoIcon path={ platform.monoPath } /> : platform.brand;
							typeClass = `flexa-social-icon__item--${ platform.key }`;
						} else {
							return null;
						}

						const tinted = isCustomIcon || item.colorMode === 'custom';
						const tint = tinted ? itemTint( item ) : '';

						return (
							<span
								key={ index }
								className={ cn( 'flexa-social-icon__item', typeClass, tinted && 'flexa-social-icon__item--custom' ) }
								style={ tint ? { color: tint } : undefined }
							>
								<span className="flexa-social-icon__icon" style={ iconStyle }>
									{ iconEl }
								</span>
							</span>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
