/**
 * Tabs block — editor component.
 *
 * Assembles the tabs-specific panels (./panels) with the shared inspector panels
 * (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the horizontal tab bar; clicking a tab previews
 * its panel, and the active tab's content is edited inline in a textarea (the
 * accordion collapse is a front-end concern handled by view.ts). Inline styles
 * mirror the PHP CSS generator, and nothing is styled by default so the tabs
 * inherit the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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
	spacingShorthand,
	applyTypography,
	applyBgFill,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	type CssProps,
} from '@utils';
import {
	TabsListPanel,
	TabsLayoutPanel,
	TabsIconPanel,
	TabsBarPanel,
	TabsContentPanel,
} from './panels';
import type { DeviceKey, EditProps, TabItem, TabsAttributes } from '../../types';

/** Wrapper preview: max width, spacing, background. */
const buildWrapperStyle = ( attributes: TabsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const w = effective( attributes.maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	return s;
};

/** Nav preview: gap between tabs. */
const buildNavStyle = ( attributes: TabsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.tabGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Tab-button preview: typography, padding, icon gap and idle text colour. */
const buildTabStyle = ( attributes: TabsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.tabTypography, device ) );
	const padding = spacingShorthand( rawDevice( attributes.tabPadding, device ) );
	if ( padding ) s.padding = padding;
	const gap = effective( attributes.iconGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	if ( attributes.tabColor?.light ) s.color = attributes.tabColor.light;
	return s;
};

/** Active-tab preview: active text colour + the indicator for the chosen style. */
const buildActiveStyle = ( attributes: TabsAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.tabActiveColor?.light ) s.color = attributes.tabActiveColor.light;
	const ind =
		attributes.tabActiveIndicatorType === 'gradient'
			? attributes.tabActiveIndicatorGradient?.light
			: attributes.tabActiveIndicator?.light;
	if ( ( attributes.tabStyle || 'underline' ) === 'underline' ) {
		s.boxShadow = `inset 0 -2px 0 0 ${ ind || 'currentColor' }`;
	} else {
		s.background = ind || 'rgba(127,127,127,0.15)';
	}
	return s;
};

/** Panel preview: typography, text colour, background, padding, border, shadow. */
const buildPanelStyle = ( attributes: TabsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.contentTypography, device ) );
	if ( attributes.contentColor?.light ) s.color = attributes.contentColor.light;
	applyBgFill( s, attributes.contentBackgroundType, attributes.contentBackground, attributes.contentBackgroundGradient );
	const padding = spacingShorthand( rawDevice( attributes.contentPadding, device ) );
	if ( padding ) s.padding = padding;
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** Icon preview: width + height from the icon size. */
const buildIconStyle = ( attributes: TabsAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/**
 * Tabs edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TabsAttributes > ): JSX.Element {
	const { tabs, className, responsiveVisibility, htmlTag, tabStyle, tabAlign, showIcon } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const list: TabItem[] = Array.isArray( tabs ) ? tabs : [];
	const [ preview, setPreview ] = useState< number >( 0 );
	const active = Math.min( Math.max( preview, 0 ), Math.max( list.length - 1, 0 ) );

	const updateTab = ( index: number, patch: Partial< TabItem > ): void => {
		setAttributes( { tabs: list.map( ( it, i ) => ( i === index ? { ...it, ...patch } : it ) ) } );
	};

	const navStyle = buildNavStyle( attributes, device );
	const tabStyleObj = buildTabStyle( attributes, device );
	const activeStyleObj = buildActiveStyle( attributes );
	const panelStyle = buildPanelStyle( attributes, device );
	const iconStyleObj = buildIconStyle( attributes, device );
	const showIcons = showIcon !== false;

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-tabs-${ blockId } .flexa-tabs__tab:hover`, prop: 'color', value: attributes.tabHoverColor?.light },
		  ] )
		: '';

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-tabs',
			`flexa-tabs--${ tabStyle || 'underline' }`,
			`flexa-tabs--align-${ tabAlign || 'left' }`,
			blockId && `flexa-tabs-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real tabs.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="container" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-tabs-inspector">
					<InspectorTabs
						layout={
							<>
								<TabsListPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TabsLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TabsIconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TabsBarPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TabsContentPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-tabs__nav" role="tablist" style={ navStyle }>
					{ list.map( ( tab, index ) => {
						const isActive = index === active;
						return (
							<button
								key={ index }
								type="button"
								className={ cn( 'flexa-tabs__tab', isActive && 'is-active' ) }
								role="tab"
								aria-selected={ isActive }
								onClick={ () => setPreview( index ) }
								style={ isActive ? { ...tabStyleObj, ...activeStyleObj } : tabStyleObj }
							>
								{ showIcons && tab.icon?.markup && (
									<span className="flexa-tabs__icon" style={ iconStyleObj } aria-hidden="true" dangerouslySetInnerHTML={ { __html: tab.icon.markup } } />
								) }
								<span className="flexa-tabs__label">{ ( tab.label || '' ).trim() || __( 'Tab', 'flexa-block' ) + ' ' + ( index + 1 ) }</span>
							</button>
						);
					} ) }
				</div>
				<div className="flexa-tabs__panels">
					{ list[ active ] && (
						<div className="flexa-tabs__panel" role="tabpanel" style={ panelStyle }>
							<textarea
								className="flexa-tabs__content-input"
								value={ list[ active ].content ?? '' }
								placeholder={ __( 'Tab content…', 'flexa-block' ) }
								onChange={ ( e ) => updateTab( active, { content: e.target.value } ) }
							/>
						</div>
					) }
				</div>
			</Tag>
		</>
	);
}
