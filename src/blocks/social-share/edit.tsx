/**
 * Social Share block — editor component.
 *
 * Assembles the share-specific panels (./panels) with the shared inspector
 * panels (@components). Inline styles mirror the PHP CSS generator, and nothing
 * is styled by default so the buttons keep the official brand artwork and the
 * theme's spacing until the user picks a value. The canvas previews the row of
 * icon buttons; the real share links are built on the front end (render.php).
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
	MonoIcon,
	getPlatform,
} from '@utils';
import type { CssProps } from '@utils';
import { ShareItemsPanel, ShareSourcePanel, ShareLayoutPanel, ShareButtonsPanel } from './panels';
import type { DeviceKey, EditProps, SocialShareAttributes, SocialShareItem } from '../../types';

/** Wrapper preview: spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: SocialShareAttributes, device: DeviceKey ): CssProps => {
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

/** List preview: direction + gap + alignment. */
const buildListStyle = ( attributes: SocialShareAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.direction === 'column' ) s.flexDirection = 'column';
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );

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
		s.justifyContent = CONTENT_ALIGN_TO_FLEX[ align ];
	}
	return s;
};

/** Icon-box preview: per-device square size. */
const buildIconStyle = ( attributes: SocialShareAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** Item-box preview: tint (custom mode) + button background. */
const buildItemStyle = ( attributes: SocialShareAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.colorMode === 'custom' && attributes.tint?.light ) {
		s.color = attributes.tint.light;
	}
	if ( attributes.buttonBackground?.light ) {
		s.backgroundColor = attributes.buttonBackground.light;
	}
	return s;
};

/**
 * Social Share edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SocialShareAttributes > ): JSX.Element {
	const { blockId, items, className, responsiveVisibility, htmlTag, hoverEffect, colorMode, shape } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: SocialShareItem[] = Array.isArray( items ) ? items : [];

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const listStyle = buildListStyle( attributes, device );
	const iconStyle = buildIconStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes );

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-social-share',
			blockId && `flexa-social-share-${ blockId }`,
			hoverEffect && `flexa-social-share--hover-${ hoverEffect }`,
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

	const shapeClass = shape && shape !== 'bare' ? `flexa-social-share__item--shape-${ shape }` : '';

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-social-share-inspector">
					<InspectorTabs
						layout={
							<>
								<ShareItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShareSourcePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShareLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ShareButtonsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-social-share__list" style={ listStyle }>
					{ list.map( ( item, index ) => {
						const platform = getPlatform( item.network );
						if ( ! platform ) {
							return null;
						}
						const iconEl = colorMode === 'custom' ? <MonoIcon path={ platform.monoPath } /> : platform.brand;

						return (
							<span
								key={ index }
								className={ cn( 'flexa-social-share__item', `flexa-social-share__item--${ platform.key }`, shapeClass ) }
								style={ itemStyle }
							>
								<span className="flexa-social-share__icon" style={ iconStyle }>
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
