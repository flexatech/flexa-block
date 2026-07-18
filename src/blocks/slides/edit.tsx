/**
 * Slider block — editor component.
 *
 * Owns the carousel behaviour (autoplay / loop / effect / slides-per-view),
 * arrow and dot navigation and the shared box panels (spacing / background /
 * border / shadow / position / visibility). The carousel itself only runs on the
 * front end (view.ts + Swiper); in the editor the slides render stacked so their
 * content stays directly editable.
 *
 * @package Flexa\Block
 */

import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl, SelectControl, Button } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	DualColor,
	FieldHead,
	IconPicker,
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
	effective,
	withUnit,
	spacingShorthand,
	visibilityClasses,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	HEIGHT_UNITS,
	LENGTH_UNITS,
} from '@utils';
import type { DeviceKey, EditProps, IconValue, LengthValue, PanelProps, SlidesAttributes } from '../../types';

type CssProps = Record< string, string >;
type SlidesPanelProps = PanelProps< SlidesAttributes >;

/** Sample slides so a freshly inserted slider shows its shape immediately. */
const TEMPLATE: Array< [ string ] > = [ [ 'flexa/slide' ], [ 'flexa/slide' ] ];
const ALLOWED_BLOCKS = [ 'flexa/slide' ];

/** Effect options (many values → a SelectControl, not a Segmented). */
const EFFECT_OPTIONS = [
	{ value: 'slide', label: __( 'Slide', 'flexa-block' ) },
	{ value: 'fade', label: __( 'Fade', 'flexa-block' ) },
	{ value: 'cube', label: __( 'Cube', 'flexa-block' ) },
	{ value: 'coverflow', label: __( 'Coverflow', 'flexa-block' ) },
	{ value: 'flip', label: __( 'Flip', 'flexa-block' ) },
	{ value: 'creative', label: __( 'Creative', 'flexa-block' ) },
];

/**
 * Behaviour panel — autoplay, loop, transition speed, effect.
 */
const BehaviourPanel = ( { attributes, setAttributes }: SlidesPanelProps ): JSX.Element => {
	const { autoplay, autoplayDelay, pauseOnHover, pauseOnInteraction, loop, speed, effect } = attributes;

	return (
		<PanelBody title={ __( 'Slider', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Autoplay', 'flexa-block' ) }
				checked={ !! autoplay }
				onChange={ ( v: boolean ) => setAttributes( { autoplay: v } ) }
			/>
			{ autoplay && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Autoplay delay (ms)', 'flexa-block' ) }
						value={ autoplayDelay ?? 3000 }
						min={ 500 }
						max={ 10000 }
						step={ 100 }
						onChange={ ( v?: number ) => setAttributes( { autoplayDelay: v ?? 3000 } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Pause on hover', 'flexa-block' ) }
						checked={ !! pauseOnHover }
						onChange={ ( v: boolean ) => setAttributes( { pauseOnHover: v } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Pause on interaction', 'flexa-block' ) }
						checked={ !! pauseOnInteraction }
						onChange={ ( v: boolean ) => setAttributes( { pauseOnInteraction: v } ) }
					/>
				</>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Infinite loop', 'flexa-block' ) }
				checked={ !! loop }
				onChange={ ( v: boolean ) => setAttributes( { loop: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Transition speed (ms)', 'flexa-block' ) }
				value={ speed ?? 500 }
				min={ 100 }
				max={ 3000 }
				step={ 50 }
				onChange={ ( v?: number ) => setAttributes( { speed: v ?? 500 } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Effect', 'flexa-block' ) }
				value={ effect || 'slide' }
				options={ EFFECT_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { effect: v as SlidesAttributes[ 'effect' ] } ) }
			/>
		</PanelBody>
	);
};

/**
 * Slides layout panel — per-device slides per view, gap between slides, height.
 */
const SlidesLayoutPanel = ( { attributes, setAttributes }: SlidesPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { slidesPerView, spaceBetween, minHeight } = attributes;
	const perView = slidesPerView?.[ device ] ?? ( device === 'desktop' ? 1 : undefined );
	const minH = minHeight?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<div className="flexa-field">
				<FieldHead label={ __( 'Slides per view', 'flexa-block' ) } />
				<RangeControl
					__nextHasNoMarginBottom
					value={ perView }
					min={ 1 }
					max={ 6 }
					allowReset
					onChange={ ( v?: number ) => setAttributes( { slidesPerView: { ...slidesPerView, [ device ]: v ?? 1 } } ) }
				/>
			</div>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Space between slides (px)', 'flexa-block' ) }
				value={ spaceBetween ?? 0 }
				min={ 0 }
				max={ 120 }
				onChange={ ( v?: number ) => setAttributes( { spaceBetween: v ?? 0 } ) }
			/>
			<SliderUnit
				label={ __( 'Slide height', 'flexa-block' ) }
				value={ minH }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1200, vh: 100, '%': 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { minHeight: { ...minHeight, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Arrows panel — visibility, icons and arrow styling.
 */
const ArrowsPanel = ( { attributes, setAttributes }: SlidesPanelProps ): JSX.Element => {
	const { showArrows, arrowPosition, arrowShowOnHover, arrowIconPrev, arrowIconNext, arrowSize, arrowColor, arrowColorHover, arrowBackground, arrowBackgroundHover, arrowRadius, arrowOffset } = attributes;

	return (
		<PanelBody title={ __( 'Arrows', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show arrows', 'flexa-block' ) }
				checked={ !! showArrows }
				onChange={ ( v: boolean ) => setAttributes( { showArrows: v } ) }
			/>
			{ showArrows && (
				<>
					<Segmented
						label={ __( 'Position', 'flexa-block' ) }
						value={ arrowPosition || 'inside' }
						onChange={ ( v ) => setAttributes( { arrowPosition: v as SlidesAttributes[ 'arrowPosition' ] } ) }
						options={ [
							{ value: 'inside', label: __( 'Inside', 'flexa-block' ) },
							{ value: 'outside', label: __( 'Outside', 'flexa-block' ) },
						] }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Reveal on hover', 'flexa-block' ) }
						help={ __( 'Hide the arrows until the slider is hovered.', 'flexa-block' ) }
						checked={ !! arrowShowOnHover }
						onChange={ ( v: boolean ) => setAttributes( { arrowShowOnHover: v } ) }
					/>
					<IconPicker label={ __( 'Previous icon', 'flexa-block' ) } value={ arrowIconPrev || {} } onChange={ ( v: IconValue ) => setAttributes( { arrowIconPrev: v } ) } />
					<IconPicker label={ __( 'Next icon', 'flexa-block' ) } value={ arrowIconNext || {} } onChange={ ( v: IconValue ) => setAttributes( { arrowIconNext: v } ) } />
					<SliderUnit label={ __( 'Icon size', 'flexa-block' ) } showDevice={ false } value={ arrowSize || {} } units={ LENGTH_UNITS } defaultUnit="px" max={ { px: 80, rem: 5, em: 5 } } onChange={ ( v: LengthValue ) => setAttributes( { arrowSize: v } ) } />
					<DualColor label={ __( 'Color', 'flexa-block' ) } value={ arrowColor || {} } onChange={ ( v ) => setAttributes( { arrowColor: v } ) } />
					<DualColor label={ __( 'Color (hover)', 'flexa-block' ) } value={ arrowColorHover || {} } onChange={ ( v ) => setAttributes( { arrowColorHover: v } ) } />
					<DualColor label={ __( 'Background', 'flexa-block' ) } value={ arrowBackground || {} } onChange={ ( v ) => setAttributes( { arrowBackground: v } ) } />
					<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ arrowBackgroundHover || {} } onChange={ ( v ) => setAttributes( { arrowBackgroundHover: v } ) } />
					<SliderUnit label={ __( 'Border radius', 'flexa-block' ) } showDevice={ false } value={ arrowRadius || {} } units={ LENGTH_UNITS } defaultUnit="px" max={ { px: 100, rem: 5, em: 5, '%': 50 } } onChange={ ( v: LengthValue ) => setAttributes( { arrowRadius: v } ) } />
					<SliderUnit label={ __( 'Edge offset', 'flexa-block' ) } showDevice={ false } value={ arrowOffset || {} } units={ LENGTH_UNITS } defaultUnit="px" min={ -60 } max={ { px: 100, rem: 6, em: 6, '%': 20 } } onChange={ ( v: LengthValue ) => setAttributes( { arrowOffset: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Pagination panel — visibility, type, bullet styling.
 */
const PaginationPanel = ( { attributes, setAttributes }: SlidesPanelProps ): JSX.Element => {
	const { showPagination, paginationPosition, paginationType, bulletColor, bulletColorActive, bulletSize } = attributes;

	return (
		<PanelBody title={ __( 'Pagination', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show pagination', 'flexa-block' ) }
				checked={ !! showPagination }
				onChange={ ( v: boolean ) => setAttributes( { showPagination: v } ) }
			/>
			{ showPagination && (
				<>
					<Segmented
						label={ __( 'Position', 'flexa-block' ) }
						value={ paginationPosition || 'outside' }
						onChange={ ( v ) => setAttributes( { paginationPosition: v as SlidesAttributes[ 'paginationPosition' ] } ) }
						options={ [
							{ value: 'inside', label: __( 'Inside', 'flexa-block' ) },
							{ value: 'outside', label: __( 'Outside', 'flexa-block' ) },
						] }
					/>
					<Segmented
						label={ __( 'Type', 'flexa-block' ) }
						value={ paginationType || 'bullets' }
						onChange={ ( v ) => setAttributes( { paginationType: v as SlidesAttributes[ 'paginationType' ] } ) }
						options={ [
							{ value: 'bullets', label: __( 'Dots', 'flexa-block' ) },
							{ value: 'fraction', label: __( 'Fraction', 'flexa-block' ) },
							{ value: 'progressbar', label: __( 'Bar', 'flexa-block' ) },
						] }
					/>
					<DualColor label={ __( 'Dot color', 'flexa-block' ) } value={ bulletColor || {} } onChange={ ( v ) => setAttributes( { bulletColor: v } ) } />
					<DualColor label={ __( 'Active dot color', 'flexa-block' ) } value={ bulletColorActive || {} } onChange={ ( v ) => setAttributes( { bulletColorActive: v } ) } />
					<SliderUnit label={ __( 'Dot size', 'flexa-block' ) } showDevice={ false } value={ bulletSize || {} } units={ LENGTH_UNITS } defaultUnit="px" max={ { px: 40, rem: 3, em: 3 } } onChange={ ( v: LengthValue ) => setAttributes( { bulletSize: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Build the wrapper preview style for the active device (box styling only; the
 * carousel motion is front-end).
 */
const buildWrapperStyle = ( attributes: SlidesAttributes, device: DeviceKey ): CssProps => {
	const { spacing, border, background, boxShadow, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	applyBorderPreview( s, b );
	applyBackgroundPreview( s, background );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( adv.overflow ) s.overflow = adv.overflow;
	return s;
};

/**
 * Slider edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SlidesAttributes > ): JSX.Element {
	const { spacing, minHeight, responsiveVisibility, className, showArrows, arrowPosition, arrowShowOnHover, showPagination, paginationPosition } = attributes;
	const [ device ] = useDevice();
	const blockId = attributes.blockId;

	useBlockId( clientId, blockId, setAttributes );

	// One-slide-at-a-time editing: track the active slide and only show that one,
	// so a slider with many slides stays compact in the editor. The index is UI
	// state (not saved) and follows the selection into a slide.
	const [ active, setActive ] = useState( 0 );
	const { slideIds, selectedSlide } = useSelect(
		( select: ( store: string ) => any ) => {
			const { getBlock, getSelectedBlockClientId, getBlockParents } = select( 'core/block-editor' );
			const block = getBlock( clientId );
			const ids: string[] = block ? block.innerBlocks.map( ( b: { clientId: string } ) => b.clientId ) : [];
			const selectedId = getSelectedBlockClientId();
			let sel = -1;
			if ( selectedId ) {
				const chain: string[] = [ selectedId, ...getBlockParents( selectedId ) ];
				sel = ids.findIndex( ( id ) => chain.includes( id ) );
			}
			return { slideIds: ids, selectedSlide: sel };
		},
		[ clientId ]
	);
	const { selectBlock, insertBlock } = useDispatch( 'core/block-editor' );

	// Follow the selection: clicking inside a slide makes it the active one.
	useEffect( () => {
		if ( selectedSlide >= 0 && selectedSlide !== active ) {
			setActive( selectedSlide );
		}
	}, [ selectedSlide ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const count = slideIds.length;
	const activeIndex = count ? Math.min( Math.max( active, 0 ), count - 1 ) : 0;

	const addSlide = () => {
		const block = createBlock( 'flexa/slide' );
		insertBlock( block, count, clientId );
		setActive( count );
	};

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const margin = spacingShorthand( effective( spacing, device ).margin );
	const slideMinHeight = effective( minHeight, device );
	const minH = slideMinHeight?.value ? withUnit( slideMinHeight.value, slideMinHeight.unit || 'px' ) : '';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-slides',
			'flexa-slides--editing',
			blockId && `flexa-slides-${ blockId }`,
			showArrows && `flexa-slides--arrows-${ arrowPosition || 'inside' }`,
			showArrows && arrowShowOnHover && 'flexa-slides--arrows-hover',
			showPagination && `flexa-slides--pagination-${ paginationPosition || 'outside' }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: { ...wrapperStyle, ...( margin ? { margin } : {} ) },
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-slides__editor' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, orientation: 'vertical', renderAppender: false }
	);

	// Show only the active slide, and give slides the configured height so the
	// content alignment (e.g. vertical centre) previews like the front end.
	// Scoped to this instance by blockId so multiple sliders don't affect each other.
	const soloStyle =
		blockId && count > 0
			? `.flexa-slides-${ blockId } > .flexa-slides__editor > *:not(:nth-child(${ activeIndex + 1 })) { display: none !important; }` +
			  ( minH ? `.flexa-slides-${ blockId } > .flexa-slides__editor > * { min-height: ${ minH }; }` : '' )
			: '';

	// Mirror the generator's nav-arrow :hover rules so the editor previews the
	// hover state (inline React styles can't express :hover). Light values only.
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-slides-${ blockId } .flexa-slides__arrow:hover`, prop: 'color', value: attributes.arrowColorHover?.light },
			{ selector: `.flexa-slides-${ blockId } .flexa-slides__arrow:hover`, prop: 'background-color', value: attributes.arrowBackgroundHover?.light },
		] )
		: '';

	// Inserter hover-preview → faint skeleton mock-up instead of the real slider.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="container" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-slides-inspector">
					<InspectorTabs
						layout={
							<>
								<BehaviourPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SlidesLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ArrowsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PaginationPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ soloStyle && <style>{ soloStyle }</style> }
				{ hoverCss && <style>{ hoverCss }</style> }

				{ count > 0 && (
					<div className="flexa-slides__tabs">
						{ slideIds.map( ( id: string, i: number ) => (
							<Button
								key={ id }
								size="small"
								variant={ i === activeIndex ? 'primary' : 'secondary' }
								className="flexa-slides__tab"
								onClick={ () => {
									setActive( i );
									selectBlock( id );
								} }
							>
								{ sprintf( /* translators: %d: slide number. */ __( 'Slide %d', 'flexa-block' ), i + 1 ) }
							</Button>
						) ) }
						<Button size="small" variant="tertiary" className="flexa-slides__tab-add" onClick={ addSlide }>
							{ __( '+ Add slide', 'flexa-block' ) }
						</Button>
					</div>
				) }

				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
