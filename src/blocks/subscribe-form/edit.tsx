/**
 * Subscribe Form block — editor component (parent of the field children).
 *
 * Owns the <form>: the submit button, the destination email + confirmation, and
 * the styling of the fields, inputs, submit button and messages. The field
 * children render as InnerBlocks so their labels/placeholders stay editable on
 * the canvas; submission itself only runs on the front end (view.ts → AJAX).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, SelectControl, ToggleControl } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	DualColor,
	Dimensions,
	FieldHead,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	effective,
	spacingShorthand,
	radiusShorthand,
	withUnit,
	visibilityClasses,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	LENGTH_UNITS,
	SPACING_UNITS,
	LINE_STYLE_OPTIONS,
	HStartIcon,
	HCenterIcon,
	HEndIcon,
} from '@utils';
import type { BorderDevice, DeviceKey, EditProps, LengthValue, PanelProps, SubscribeFormAttributes } from '../../types';

type CssProps = Record< string, string >;
type FormPanelProps = PanelProps< SubscribeFormAttributes >;

/** The field children a subscribe form can hold. */
const ALLOWED_BLOCKS = [
	'flexa/subscribe-form-email',
	'flexa/subscribe-form-name',
	'flexa/subscribe-form-phone',
	'flexa/subscribe-form-textarea',
	'flexa/subscribe-form-select',
	'flexa/subscribe-form-radio',
	'flexa/subscribe-form-checkbox',
	'flexa/subscribe-form-date',
	'flexa/subscribe-form-url',
	'flexa/subscribe-form-toggle',
	'flexa/subscribe-form-upload',
	'flexa/subscribe-form-hidden',
];

/** A fresh form shows an email + name field so its shape is visible at once. */
const TEMPLATE: Array< [ string ] > = [ [ 'flexa/subscribe-form-email' ], [ 'flexa/subscribe-form-name' ] ];

/** Submit-button horizontal placement. */
const ALIGN_OPTIONS = [
	{ value: 'flex-start', label: __( 'Left', 'flexa-block' ), icon: HStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: HCenterIcon },
	{ value: 'flex-end', label: __( 'Right', 'flexa-block' ), icon: HEndIcon },
];

/**
 * Form panel — submit label, destination email, confirmation behaviour.
 */
const FormPanel = ( { attributes, setAttributes }: FormPanelProps ): JSX.Element => {
	const { submitText, toEmail, emailSubject, successMessage, errorMessage, confirmationType, redirectUrl } = attributes;

	return (
		<PanelBody title={ __( 'Form', 'flexa-block' ) } initialOpen={ true }>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Submit button text', 'flexa-block' ) }
				value={ submitText ?? '' }
				onChange={ ( v: string ) => setAttributes( { submitText: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				type="email"
				label={ __( 'Send entries to', 'flexa-block' ) }
				help={ __( 'Where submissions are emailed. Leave blank to use the site admin email.', 'flexa-block' ) }
				value={ toEmail ?? '' }
				onChange={ ( v: string ) => setAttributes( { toEmail: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Email subject', 'flexa-block' ) }
				value={ emailSubject ?? '' }
				onChange={ ( v: string ) => setAttributes( { emailSubject: v } ) }
			/>
			<Segmented
				label={ __( 'On submit', 'flexa-block' ) }
				value={ confirmationType || 'message' }
				onChange={ ( v ) => setAttributes( { confirmationType: v as SubscribeFormAttributes[ 'confirmationType' ] } ) }
				options={ [
					{ value: 'message', label: __( 'Show message', 'flexa-block' ) },
					{ value: 'url', label: __( 'Redirect', 'flexa-block' ) },
				] }
			/>
			{ 'url' === confirmationType ? (
				<TextControl
					__nextHasNoMarginBottom
					type="url"
					label={ __( 'Redirect URL', 'flexa-block' ) }
					value={ redirectUrl ?? '' }
					onChange={ ( v: string ) => setAttributes( { redirectUrl: v } ) }
				/>
			) : (
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Success message', 'flexa-block' ) }
					value={ successMessage ?? '' }
					onChange={ ( v: string ) => setAttributes( { successMessage: v } ) }
				/>
			) }
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Error message', 'flexa-block' ) }
				value={ errorMessage ?? '' }
				onChange={ ( v: string ) => setAttributes( { errorMessage: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Field layout panel — spacing between fields, submit alignment / width.
 */
const FieldLayoutPanel = ( { attributes, setAttributes }: FormPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { fieldGap, buttonAlign, buttonWidth } = attributes;
	const gap = fieldGap?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Space between fields', 'flexa-block' ) }
				value={ gap }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, rem: 5, em: 5 } }
				onChange={ ( v: LengthValue ) => setAttributes( { fieldGap: { ...fieldGap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Button alignment', 'flexa-block' ) }
				value={ buttonAlign || 'flex-start' }
				onChange={ ( v ) => setAttributes( { buttonAlign: v as SubscribeFormAttributes[ 'buttonAlign' ] } ) }
				options={ ALIGN_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Full-width button', 'flexa-block' ) }
				checked={ 'full' === buttonWidth }
				onChange={ ( v: boolean ) => setAttributes( { buttonWidth: v ? 'full' : 'auto' } ) }
			/>
		</PanelBody>
	);
};

/**
 * Fields & inputs style panel — label colour, input colours / background /
 * border / padding.
 */
const InputStylePanel = ( { attributes, setAttributes }: FormPanelProps ): JSX.Element => {
	const { labelColor, inputTextColor, inputPlaceholderColor, inputBackground, inputBorder, inputPadding } = attributes;
	const border = inputBorder || {};
	const widthUnit = border.width?.unit || 'px';
	const setBorder = ( patch: Partial< BorderDevice > ) => setAttributes( { inputBorder: { ...border, ...patch } } );

	return (
		<PanelBody title={ __( 'Fields & inputs', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Label color', 'flexa-block' ) } value={ labelColor || {} } onChange={ ( v ) => setAttributes( { labelColor: v } ) } />
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ inputTextColor || {} } onChange={ ( v ) => setAttributes( { inputTextColor: v } ) } />
			<DualColor label={ __( 'Placeholder color', 'flexa-block' ) } value={ inputPlaceholderColor || {} } onChange={ ( v ) => setAttributes( { inputPlaceholderColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ inputBackground || {} } onChange={ ( v ) => setAttributes( { inputBackground: v } ) } />
			<FieldHead label={ __( 'Border', 'flexa-block' ) } />
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Style', 'flexa-block' ) }
				value={ border.style || '' }
				options={ [ { value: '', label: __( 'None', 'flexa-block' ) }, ...LINE_STYLE_OPTIONS ] }
				onChange={ ( v: string ) => setBorder( { style: v } ) }
			/>
			<SliderUnit
				label={ __( 'Border width', 'flexa-block' ) }
				showDevice={ false }
				value={ { value: border.width?.top, unit: widthUnit } }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 10, rem: 1, em: 1 } }
				onChange={ ( v: LengthValue ) => setBorder( { width: { top: v.value ?? '', right: v.value ?? '', bottom: v.value ?? '', left: v.value ?? '', unit: v.unit || 'px' } } ) }
			/>
			<DualColor label={ __( 'Border color', 'flexa-block' ) } value={ border.color || {} } onChange={ ( v ) => setBorder( { color: v } ) } />
			<SliderUnit
				label={ __( 'Border radius', 'flexa-block' ) }
				showDevice={ false }
				value={ { value: border.radius?.topLeft, unit: border.radius?.unit || 'px' } }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 40, rem: 3, em: 3 } }
				onChange={ ( v: LengthValue ) => setBorder( { radius: { topLeft: v.value ?? '', topRight: v.value ?? '', bottomRight: v.value ?? '', bottomLeft: v.value ?? '', unit: v.unit || 'px' } } ) }
			/>
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } value={ inputPadding || {} } units={ SPACING_UNITS } onChange={ ( v ) => setAttributes( { inputPadding: v } ) } />
		</PanelBody>
	);
};

/**
 * Submit-button style panel — text / background colours (+ hover), padding, radius.
 */
const SubmitStylePanel = ( { attributes, setAttributes }: FormPanelProps ): JSX.Element => {
	const { submitTextColor, submitTextColorHover, submitBackground, submitBackgroundHover, submitPadding, submitRadius } = attributes;

	return (
		<PanelBody title={ __( 'Submit button', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ submitTextColor || {} } onChange={ ( v ) => setAttributes( { submitTextColor: v } ) } />
			<DualColor label={ __( 'Text color (hover)', 'flexa-block' ) } value={ submitTextColorHover || {} } onChange={ ( v ) => setAttributes( { submitTextColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ submitBackground || {} } onChange={ ( v ) => setAttributes( { submitBackground: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ submitBackgroundHover || {} } onChange={ ( v ) => setAttributes( { submitBackgroundHover: v } ) } />
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } value={ submitPadding || {} } units={ SPACING_UNITS } onChange={ ( v ) => setAttributes( { submitPadding: v } ) } />
			<SliderUnit
				label={ __( 'Border radius', 'flexa-block' ) }
				showDevice={ false }
				value={ submitRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 60, rem: 4, em: 4, '%': 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { submitRadius: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Messages style panel — success / error text + background colours.
 */
const MessageStylePanel = ( { attributes, setAttributes }: FormPanelProps ): JSX.Element => {
	const { successColor, successBackground, errorColor, errorBackground } = attributes;

	return (
		<PanelBody title={ __( 'Messages', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Success', 'flexa-block' ) } />
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ successColor || {} } onChange={ ( v ) => setAttributes( { successColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ successBackground || {} } onChange={ ( v ) => setAttributes( { successBackground: v } ) } />
			<FieldHead label={ __( 'Error', 'flexa-block' ) } />
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ errorColor || {} } onChange={ ( v ) => setAttributes( { errorColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ errorBackground || {} } onChange={ ( v ) => setAttributes( { errorBackground: v } ) } />
		</PanelBody>
	);
};

/** Build the form (box) preview style for the active device. */
const buildFormStyle = ( attributes: SubscribeFormAttributes, device: DeviceKey ): CssProps => {
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
 * Mirror the front-end generator's field / input / submit CSS as a scoped
 * `<style>` so the child previews look the same in the editor as on the front
 * end (the PHP generator only runs at save time). Light values only — the editor
 * canvas previews light mode — and it updates live as the panels change.
 */
const buildFieldsCss = ( a: SubscribeFormAttributes, blockId: string, device: DeviceKey ): string => {
	if ( ! blockId ) {
		return '';
	}
	const root = `.flexa-subscribe-form-${ blockId }`;
	const rules: string[] = [];
	const push = ( selector: string, decls: string[] ): void => {
		if ( decls.length ) {
			rules.push( `${ selector }{${ decls.join( ';' ) }}` );
		}
	};

	const gap = effective( a.fieldGap, device );
	if ( gap?.value ) {
		push( `${ root } .flexa-subscribe-form__fields`, [ `gap:${ withUnit( gap.value, gap.unit || 'px' ) }` ] );
	}

	if ( a.labelColor?.light ) {
		push( `${ root } .flexa-field__label`, [ `color:${ a.labelColor.light }` ] );
	}

	const input: string[] = [];
	if ( a.inputTextColor?.light ) input.push( `color:${ a.inputTextColor.light }` );
	if ( a.inputBackground?.light ) input.push( `background-color:${ a.inputBackground.light }` );
	const b = a.inputBorder || {};
	if ( b.style ) input.push( `border-style:${ b.style }` );
	const bw = spacingShorthand( b.width ); if ( bw ) input.push( `border-width:${ bw }` );
	if ( b.color?.light ) input.push( `border-color:${ b.color.light }` );
	const br = radiusShorthand( b.radius ); if ( br ) input.push( `border-radius:${ br }` );
	const pad = spacingShorthand( a.inputPadding ); if ( pad ) input.push( `padding:${ pad }` );
	push( `${ root } .flexa-field__control`, input );

	if ( a.inputPlaceholderColor?.light ) {
		push( `${ root } .flexa-field__control::placeholder`, [ `color:${ a.inputPlaceholderColor.light }` ] );
	}

	const submit: string[] = [];
	if ( a.submitTextColor?.light ) submit.push( `color:${ a.submitTextColor.light }` );
	if ( a.submitBackground?.light ) submit.push( `background-color:${ a.submitBackground.light }` );
	const sp = spacingShorthand( a.submitPadding ); if ( sp ) submit.push( `padding:${ sp }` );
	if ( a.submitRadius?.value ) submit.push( `border-radius:${ withUnit( a.submitRadius.value, a.submitRadius.unit || 'px' ) }` );
	push( `${ root } .flexa-subscribe-form__submit`, submit );

	return rules.join( '' );
};

/**
 * Subscribe Form edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SubscribeFormAttributes > ): JSX.Element {
	const { spacing, fieldGap, buttonAlign, buttonWidth, submitText, className, responsiveVisibility } = attributes;
	const [ device ] = useDevice();
	const blockId = attributes.blockId;

	useBlockId( clientId, blockId, setAttributes );

	const formStyle = buildFormStyle( attributes, device );
	const margin = spacingShorthand( effective( spacing, device ).margin );
	const gap = effective( fieldGap, device );
	const gapValue = gap?.value ? withUnit( gap.value, gap.unit || 'px' ) : '';
	const fieldsCss = buildFieldsCss( attributes, blockId || '', device );

	// Mirror the generator's submit-button :hover rules so the editor previews the
	// hover state (inline React styles can't express :hover). Light values only.
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-subscribe-form-${ blockId } .flexa-subscribe-form__submit:hover`, prop: 'color', value: attributes.submitTextColorHover?.light },
			{ selector: `.flexa-subscribe-form-${ blockId } .flexa-subscribe-form__submit:hover`, prop: 'background-color', value: attributes.submitBackgroundHover?.light },
		] )
		: '';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-subscribe-form',
			'flexa-subscribe-form--editing',
			blockId && `flexa-subscribe-form-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: { ...formStyle, ...( margin ? { margin } : {} ) },
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-subscribe-form__fields', style: gapValue ? { rowGap: gapValue } : {} },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false }
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-subscribe-form-inspector">
					<InspectorTabs
						layout={
							<>
								<FormPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FieldLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<InputStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<SubmitStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<MessageStylePanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ fieldsCss && <style>{ fieldsCss }</style> }
				{ hoverCss && <style>{ hoverCss }</style> }
				<div { ...innerBlocksProps } />
				<div className={ cn( 'flexa-subscribe-form__actions', `flexa-subscribe-form__actions--${ buttonAlign || 'flex-start' }` ) }>
					<button
						type="button"
						className={ cn( 'flexa-subscribe-form__submit', 'wp-element-button', 'full' === buttonWidth && 'flexa-subscribe-form__submit--full' ) }
						disabled
					>
						{ submitText || __( 'Subscribe', 'flexa-block' ) }
					</button>
				</div>
			</div>
		</>
	);
}
