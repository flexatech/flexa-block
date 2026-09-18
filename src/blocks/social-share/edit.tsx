/**
 * Social Share block — editor component.
 *
 * Assembles the share-specific panels (./panels) with the shared inspector
 * panels (@components). Inline styles mirror the PHP CSS generator, and nothing
 * is styled by default so the buttons keep the official brand artwork and the
 * theme's spacing until the user picks a value. The canvas previews the row of
 * icon buttons; the real share links are built on the front end (render.php) —
 * including the "Product Share" variation, whose URL, title and image come from
 * the product WooCommerce resolves for the request and so cannot be previewed.
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
	PositionPanel,
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
	rawDevice,
	withUnit,
	applyTypography,
	wrapperPreviewStyle,
	CONTENT_ALIGN_TO_FLEX,
	MonoIcon,
	getPlatform,
} from '@utils';
import type { CssProps } from '@utils';
import { ShareItemsPanel, ShareSourcePanel, ShareLayoutPanel, ShareButtonsPanel, ShareLabelPanel } from './panels';
import type { DeviceKey, EditProps, SocialShareAttributes, SocialShareItem } from '../../types';

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

/** Label preview: typography only — the colour comes from the button. */
const buildLabelStyle = ( attributes: SocialShareAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.labelTypography, device ) );
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
	const { blockId, items, className, responsiveVisibility, htmlTag, hoverEffect, colorMode, shape, showLabels } =
		attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: SocialShareItem[] = Array.isArray( items ) ? items : [];

	const listStyle = buildListStyle( attributes, device );
	const iconStyle = buildIconStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes );
	const labelStyle = buildLabelStyle( attributes, device );

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-social-share',
			blockId && `flexa-social-share-${ blockId }`,
			showLabels && 'flexa-social-share--labels',
			hoverEffect && `flexa-social-share--hover-${ hoverEffect }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperPreviewStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real icons.
	if ( attributes.isExamplePreview ) {
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
								{ !! showLabels && <ShareLabelPanel attributes={ attributes } setAttributes={ setAttributes } /> }
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
								{ !! showLabels && (
									<span className="flexa-social-share__label" style={ labelStyle }>
										{ platform.label }
									</span>
								) }
							</span>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
