/**
 * Tabs block — editor component.
 *
 * The tab panels are now `flexa/tab` child blocks (each with a label, optional
 * text and its own inner blocks). This parent renders the tab bar from the
 * children's labels, shows one tab at a time on the canvas, and lets you add /
 * select tabs. All the tab-bar / panel styling controls are unchanged.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

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
	TabsLayoutPanel,
	TabsIconPanel,
	TabsBarPanel,
	TabsContentPanel,
} from './panels';
import type { DeviceKey, EditProps, TabsAttributes } from '../../types';

const ALLOWED_BLOCKS = [ 'flexa/tab' ];
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const TEMPLATE: any = [
	[ 'flexa/tab', { label: __( 'Tab 1', 'flexa-block' ) } ],
	[ 'flexa/tab', { label: __( 'Tab 2', 'flexa-block' ) } ],
	[ 'flexa/tab', { label: __( 'Tab 3', 'flexa-block' ) } ],
];

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
	const { className, responsiveVisibility, htmlTag, tabStyle, tabAlign, showIcon } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	// One-tab-at-a-time editing: track the active tab and show only that panel.
	const [ active, setActive ] = useState( 0 );

	const { children, selectedTab } = useSelect(
		( select: ( store: string ) => any ) => {
			const { getBlock, getSelectedBlockClientId, getBlockParents } = select( 'core/block-editor' );
			const block = getBlock( clientId );
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const kids: any[] = block ? block.innerBlocks : [];
			const list = kids.map( ( b: any ) => ( {
				clientId: b.clientId as string,
				label: ( b.attributes?.label as string ) || '',
				icon: b.attributes?.icon || null,
			} ) );
			const selectedId = getSelectedBlockClientId();
			let sel = -1;
			if ( selectedId ) {
				const chain: string[] = [ selectedId, ...getBlockParents( selectedId ) ];
				sel = list.findIndex( ( t: { clientId: string } ) => chain.includes( t.clientId ) );
			}
			return { children: list, selectedTab: sel };
		},
		[ clientId ]
	);
	const { selectBlock, insertBlock } = useDispatch( 'core/block-editor' );

	// Follow the selection: clicking inside a tab makes it the active one.
	useEffect( () => {
		if ( selectedTab >= 0 && selectedTab !== active ) {
			setActive( selectedTab );
		}
	}, [ selectedTab ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const count = children.length;
	const activeIndex = count ? Math.min( Math.max( active, 0 ), count - 1 ) : 0;

	const addTab = () => {
		const block = createBlock( 'flexa/tab', { label: `${ __( 'Tab', 'flexa-block' ) } ${ count + 1 }` } );
		insertBlock( block, count, clientId );
		setActive( count );
	};

	const navStyle = buildNavStyle( attributes, device );
	const tabStyleObj = buildTabStyle( attributes, device );
	const activeStyleObj = buildActiveStyle( attributes );
	const panelStyle = buildPanelStyle( attributes, device );
	const iconStyleObj = buildIconStyle( attributes, device );
	const showIcons = showIcon !== false;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-tabs',
			'flexa-tabs--editing',
			`flexa-tabs--${ tabStyle || 'underline' }`,
			`flexa-tabs--align-${ tabAlign || 'left' }`,
			blockId && `flexa-tabs-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-tabs__editor', style: panelStyle },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false, renderAppender: false, orientation: 'vertical' }
	);

	// Show only the active tab's child on the canvas (scoped to this instance).
	const soloStyle =
		blockId && count > 0
			? `.flexa-tabs-${ blockId } > .flexa-tabs__panels > .flexa-tabs__editor > *:not(:nth-child(${ activeIndex + 1 })) { display: none !important; }`
			: '';

	// Mirror the generator's tab :hover rule so the editor previews it.
	const hoverCss = blockId
		? editorCss( [ { selector: `.flexa-tabs-${ blockId } .flexa-tabs__tab:hover`, prop: 'color', value: attributes.tabHoverColor?.light } ] )
		: '';

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
				{ soloStyle && <style>{ soloStyle }</style> }
				<div className="flexa-tabs__nav" role="tablist" style={ navStyle }>
					{ children.map( ( tab: { clientId: string; label: string; icon: any }, index: number ) => {
						const isActive = index === activeIndex;
						return (
							<button
								key={ tab.clientId }
								type="button"
								className={ cn( 'flexa-tabs__tab', isActive && 'is-active' ) }
								role="tab"
								aria-selected={ isActive }
								onClick={ () => {
									setActive( index );
									selectBlock( tab.clientId );
								} }
								style={ isActive ? { ...tabStyleObj, ...activeStyleObj } : tabStyleObj }
							>
								{ showIcons && tab.icon?.markup && (
									<span className="flexa-tabs__icon" style={ iconStyleObj } aria-hidden="true" dangerouslySetInnerHTML={ { __html: tab.icon.markup } } />
								) }
								<span className="flexa-tabs__label">{ ( tab.label || '' ).trim() || __( 'Tab', 'flexa-block' ) + ' ' + ( index + 1 ) }</span>
							</button>
						);
					} ) }
					<button type="button" className="flexa-tabs__add" onClick={ addTab } aria-label={ __( 'Add tab', 'flexa-block' ) }>+</button>
				</div>
				<div className="flexa-tabs__panels">
					<div { ...innerBlocksProps } />
				</div>
			</Tag>
		</>
	);
}
