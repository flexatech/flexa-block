/**
 * FAQ block — editor component.
 *
 * Assembles the FAQ-specific panels (./panels) with the shared inspector panels
 * (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders every item expanded so both the question and
 * the answer are editable; the accordion open/close behaviour is a front-end
 * concern (view.ts). Inline styles mirror the PHP CSS generator, and nothing is
 * styled by default so the FAQ inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import { cn, effective, withUnit, spacingShorthand, radiusShorthand, applyTypography, applyTextFill, applyBgFill, applyBackgroundPreview, applyBorderPreview, boxShadowPreview } from '@utils';
import {
	FaqLayoutPanel,
	FaqBehaviorPanel,
	FaqIconPanel,
	FaqQuestionPanel,
	FaqAnswerPanel,
	FaqItemBoxPanel,
} from './panels';
import type { ColorPair, DeviceKey, EditProps, FaqAttributes, FaqItem } from '../../types';

type CssProps = Record< string, string >;


/** Wrapper preview: max width, spacing, background (border + shadow are per item). */
const buildWrapperStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const { maxWidth, spacing, background } = attributes;
	const sp = effective( spacing, device );
	const s: CssProps = {};

	const w = effective( maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );

	return s;
};

/** List preview: row gap between items. */
const buildListStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const rg = effective( attributes.rowGap, device );
	if ( rg.value ) s.rowGap = withUnit( rg.value, rg.unit || 'px' );
	return s;
};

/** Item preview: background + the shared Border (4-side outline, radius) + shadow. */
const buildItemStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const { itemBackgroundType, itemBackground, itemBackgroundGradient, boxShadow } = attributes;
	const s: CssProps = {};
	applyBgFill( s, itemBackgroundType, itemBackground, itemBackgroundGradient );

	const b = effective( attributes.border, device );
	applyBorderPreview( s, b );
	// FAQ item clips its content to the rounded corners.
	if ( radiusShorthand( b.radius ) ) s.overflow = 'hidden';

	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** Apply the Q/A divider (a bottom border mirroring the item border) to a box. */
const applyQaDivider = ( s: CssProps, attributes: FaqAttributes, device: DeviceKey ): void => {
	const b = effective( attributes.border, device );
	if ( ! b.style ) return;
	s.borderBottomStyle = b.style;
	const w = b.width || {};
	const value = w.bottom || w.top || w.left || w.right || '';
	if ( value ) s.borderBottomWidth = withUnit( value, w.unit || 'px' );
	if ( b.color?.light ) s.borderBottomColor = b.color.light;
};

/** Whether an active (open-state) fill has a value (colour or gradient). */
const hasActiveFill = ( type?: string, color?: ColorPair, gradient?: ColorPair ): boolean =>
	type === 'gradient' ? !! gradient?.light : !! color?.light;

/**
 * Question-button preview: typography, background, padding, icon gap.
 *
 * The editor always shows items expanded (open), so the question previews its
 * OPEN background — falling back to the idle background where no open colour is
 * set, exactly like the front end does for an open item.
 */
const buildQuestionStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const {
		questionBackgroundType, questionBackground, questionBackgroundGradient,
		questionActiveBackgroundType, questionActiveBackground, questionActiveBackgroundGradient,
		questionPadding, iconGap,
	} = attributes;
	const s: CssProps = {};
	applyTypography( s, effective( attributes.questionTypography, device ) );
	if ( hasActiveFill( questionActiveBackgroundType, questionActiveBackground, questionActiveBackgroundGradient ) ) {
		applyBgFill( s, questionActiveBackgroundType, questionActiveBackground, questionActiveBackgroundGradient );
	} else {
		applyBgFill( s, questionBackgroundType, questionBackground, questionBackgroundGradient );
	}
	applyQaDivider( s, attributes, device );
	const padding = spacingShorthand( effective( questionPadding, device ) );
	if ( padding ) s.padding = padding;
	const gap = effective( iconGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Question-text preview: the OPEN text colour (idle fallback) — editor is open. */
const buildQuestionTextStyle = ( attributes: FaqAttributes ): CssProps => {
	const s: CssProps = {};
	if ( hasActiveFill( attributes.questionActiveColorType, attributes.questionActiveColor, attributes.questionActiveColorGradient ) ) {
		applyTextFill( s, attributes.questionActiveColorType, attributes.questionActiveColor, attributes.questionActiveColorGradient );
	} else {
		applyTextFill( s, attributes.questionColorType, attributes.questionColor, attributes.questionColorGradient );
	}
	return s;
};

/** Answer-region preview: background fill only (text/padding live on the inner). */
const buildAnswerWrapStyle = ( attributes: FaqAttributes ): CssProps => {
	const s: CssProps = {};
	applyBgFill( s, attributes.answerBackgroundType, attributes.answerBackground, attributes.answerBackgroundGradient );
	return s;
};

/** Answer-content preview: typography, text colour/gradient, padding. */
const buildAnswerContentStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.answerTypography, device ) );
	applyTextFill( s, attributes.answerColorType, attributes.answerColor, attributes.answerColorGradient );
	const padding = spacingShorthand( effective( attributes.answerPadding, device ) );
	if ( padding ) s.padding = padding;
	return s;
};

/** Icon preview: size + the OPEN colour (idle fallback) — the editor is open. */
const buildIconStyle = ( attributes: FaqAttributes, device: DeviceKey ): CssProps => {
	const { iconSize, iconColor, iconActiveColor } = attributes;
	const s: CssProps = {};
	const size = effective( iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	if ( iconActiveColor?.light ) s.color = iconActiveColor.light;
	else if ( iconColor?.light ) s.color = iconColor.light;
	return s;
};

/** A crisp "×" glyph for the remove button (inherits the button colour). */
const RemoveIcon = (
	<svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
	</svg>
);

/** The open/close icon markup for a given style (idle preview state). */
const IconGlyph = ( { style, iconStyle }: { style: CssProps; iconStyle: string } ): JSX.Element => {
	if ( iconStyle === 'chevron' ) {
		return (
			<span className="flexa-faq__icon flexa-faq__icon--chevron" style={ style } aria-hidden="true">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
				</svg>
			</span>
		);
	}
	if ( iconStyle === 'arrow' ) {
		return (
			<span className="flexa-faq__icon flexa-faq__icon--arrow" style={ style } aria-hidden="true">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 5v14M6 13l6 6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
				</svg>
			</span>
		);
	}
	return <span className="flexa-faq__icon flexa-faq__icon--plus" style={ style } aria-hidden="true" />;
};

/**
 * FAQ edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< FaqAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, showIcon, iconPosition, iconStyle } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const list: FaqItem[] = Array.isArray( items ) ? items : [];

	const updateItem = ( index: number, patch: Partial< FaqItem > ): void => {
		const next = list.map( ( it, i ) => ( i === index ? { ...it, ...patch } : it ) );
		setAttributes( { items: next } );
	};
	const addItem = (): void => {
		setAttributes( { items: [ ...list, { question: '', answer: '' } ] } );
	};
	const removeItem = ( index: number ): void => {
		setAttributes( { items: list.filter( ( _, i ) => i !== index ) } );
	};

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const listStyle = buildListStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const questionStyle = buildQuestionStyle( attributes, device );
	const questionTextStyle = buildQuestionTextStyle( attributes );
	const answerWrapStyle = buildAnswerWrapStyle( attributes );
	const answerContentStyle = buildAnswerContentStyle( attributes, device );
	const iconStyleObj = buildIconStyle( attributes, device );
	const iconLeft = iconPosition === 'left';

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-faq',
			'flexa-faq--accordion',
			iconLeft && 'flexa-faq--icon-left',
			blockId && `flexa-faq-${ blockId }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real FAQ list.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="faq" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-faq-inspector">
					<InspectorTabs
						layout={
							<>
								<FaqLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FaqBehaviorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FaqIconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<FaqQuestionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FaqAnswerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FaqItemBoxPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-faq__list" style={ listStyle }>
					{ list.map( ( item, index ) => (
						<div key={ index } className="flexa-faq__item is-open" style={ itemStyle }>
							<div className="flexa-faq__question" style={ questionStyle }>
								{ showIcon !== false && iconLeft && <IconGlyph style={ iconStyleObj } iconStyle={ iconStyle || 'plus' } /> }
								<RichText
									tagName="span"
									className="flexa-faq__question-text"
									style={ questionTextStyle }
									value={ item.question || '' }
									allowedFormats={ [ 'core/bold', 'core/italic' ] }
									placeholder={ __( 'Question…', 'flexa-block' ) }
									onChange={ ( v: string ) => updateItem( index, { question: v } ) }
								/>
								{ showIcon !== false && ! iconLeft && <IconGlyph style={ iconStyleObj } iconStyle={ iconStyle || 'plus' } /> }
								<Button
									className="flexa-faq__remove"
									icon={ RemoveIcon }
									label={ __( 'Remove item', 'flexa-block' ) }
									showTooltip
									isDestructive
									onClick={ () => removeItem( index ) }
								/>
							</div>
							<div className="flexa-faq__answer" style={ answerWrapStyle }>
								<div className="flexa-faq__answer-inner">
									<RichText
										tagName="div"
										className="flexa-faq__answer-content"
										style={ answerContentStyle }
										value={ item.answer || '' }
										allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
										placeholder={ __( 'Answer…', 'flexa-block' ) }
										onChange={ ( v: string ) => updateItem( index, { answer: v } ) }
									/>
								</div>
							</div>
						</div>
					) ) }
				</div>
				<Button className="flexa-faq__add" variant="secondary" onClick={ addItem }>
					{ __( 'Add question', 'flexa-block' ) }
				</Button>
			</Tag>
		</>
	);
}
