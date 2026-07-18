/**
 * Icon List block — editor component.
 *
 * Assembles the icon-list-specific panels (./panels) with the shared inspector
 * panels (@components). The canvas previews the list/grid with inline styles
 * that mirror the PHP CSS generator; nothing is styled by default so the list
 * inherits the theme until the user picks a value. Item content (icon, text,
 * link) is edited in the Items panel.
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
	TypographyPanel,
	IconMarkup,
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
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	IconListItemsPanel,
	IconListLayoutPanel,
	IconListIconPanel,
	IconListTextPanel,
} from './panels';
import type { DeviceKey, EditProps, IconListAttributes, IconListItem } from '../../types';

/** The active device's alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: IconListAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || '';
};

/** Wrapper preview: spacing, background, border, shadow (the whole block). */
const buildWrapperStyle = ( attributes: IconListAttributes, device: DeviceKey ): CssProps => {
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

/** List preview: the column grid (grid view) + the gap between items. */
const buildListStyle = ( attributes: IconListAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.view === 'grid' ) {
		const cols = effective( attributes.columns, device ).value;
		if ( cols ) s.gridTemplateColumns = `repeat(${ parseInt( String( cols ), 10 ) || 1 }, minmax(0, 1fr))`;
	}
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Item preview: icon↔text gap + alignment. */
const buildItemStyle = ( attributes: IconListAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.iconGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	const align = alignFor( attributes, device );
	if ( align && CONTENT_ALIGN_TO_FLEX[ align ] ) s.justifyContent = CONTENT_ALIGN_TO_FLEX[ align ];
	return s;
};

/** Icon-box preview: size + colours + frame padding + shape radius. */
const buildIconStyle = ( attributes: IconListAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) s.fontSize = withUnit( size.value, size.unit || 'px' );
	if ( attributes.iconColor?.light ) s.color = attributes.iconColor.light;
	if ( attributes.iconBackground?.light ) s.background = attributes.iconBackground.light;
	if ( attributes.iconBorderColor?.light ) s.borderColor = attributes.iconBorderColor.light;
	const pad = effective( attributes.iconPadding, device );
	if ( pad.value ) s.padding = withUnit( pad.value, pad.unit || 'px' );
	if ( attributes.iconView && attributes.iconView !== 'default' ) {
		const shape = attributes.iconShape || 'square';
		if ( shape === 'circle' ) s.borderRadius = '50%';
		else if ( shape === 'rounded' ) s.borderRadius = '8px';
	}
	return s;
};

/** Text preview: typography + colour. */
const buildTextStyle = ( attributes: IconListAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.typography, device ) );
	if ( attributes.textColor?.light ) s.color = attributes.textColor.light;
	return s;
};

/**
 * Hover CSS for the editor — inline styles can't express `:hover`, so mirror the
 * generator's hover rules in a scoped <style>. Light values only (the editor
 * previews light mode); selectors mirror Icon_List_CSS exactly.
 */
const buildHoverCss = ( attributes: IconListAttributes, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}
	const item = `.flexa-icon-list-${ blockId } .flexa-icon-list__item`;
	return editorCss( [
		{ selector: `${ item }:hover .flexa-icon-list__icon`, prop: 'color', value: attributes.iconColorHover?.light },
		{ selector: `${ item }:hover .flexa-icon-list__icon`, prop: 'background', value: attributes.iconBackgroundHover?.light },
		{ selector: `${ item }:hover .flexa-icon-list__text`, prop: 'color', value: attributes.textColorHover?.light },
	] );
};

/**
 * Icon List edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< IconListAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, view, iconView, iconShape, iconPosition } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: IconListItem[] = Array.isArray( items ) ? items : [];

	const listStyle = buildListStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const iconStyle = buildIconStyle( attributes, device );
	const textStyle = buildTextStyle( attributes, device );
	const hoverCss = buildHoverCss( attributes, blockId );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-icon-list',
			`flexa-icon-list--${ view || 'list' }`,
			`flexa-icon-list--icon-${ iconView || 'default' }`,
			`flexa-icon-list--shape-${ iconShape || 'square' }`,
			blockId && `flexa-icon-list-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real list.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="icon-list" />
			</div>
		);
	}

	const iconBefore = ( iconPosition || 'before' ) !== 'after';

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-icon-list-inspector">
					<InspectorTabs
						layout={
							<>
								<IconListItemsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<IconListLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<IconListIconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<IconListTextPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
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
				{ hoverCss && <style>{ hoverCss }</style> }
				<div className="flexa-icon-list__list" style={ listStyle }>
					{ list.map( ( item, index ) => {
						const icon = item.icon ? (
							<span className="flexa-icon-list__icon" style={ iconStyle }>
								<IconMarkup icon={ item.icon } />
							</span>
						) : null;
						const text =
							( item.text ?? '' ) !== '' ? (
								<span className="flexa-icon-list__text" style={ textStyle }>
									{ item.text }
								</span>
							) : null;

						return (
							<div key={ item.id || index } className="flexa-icon-list__item" style={ itemStyle }>
								{ iconBefore ? (
									<>
										{ icon }
										{ text }
									</>
								) : (
									<>
										{ text }
										{ icon }
									</>
								) }
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
