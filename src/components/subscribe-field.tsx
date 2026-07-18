/**
 * Shared editor pieces for the Subscribe Form field children (email | name |
 * phone | textarea | select | radio | checkbox | date | url | toggle | upload |
 * hidden).
 *
 * The children differ only by input `kind` and their defaults (set in each
 * block.json). Everything else — the settings panel and the canvas preview —
 * lives here once, so each child's edit.tsx is a thin wrapper (guide §4.5: block
 * #2 touches it → hoist, never copy).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, ToggleControl, RangeControl } from '@wordpress/components';

import { Segmented } from './controls';
import { InspectorTabs } from './tabs';
import { useBlockId } from './hooks';
import { cn } from '@utils';
import type { ControlOption, EditProps, SubscribeFieldAttributes } from '../types';

/** Which native control the field renders. */
export type SubscribeFieldKind =
	| 'email'
	| 'text'
	| 'tel'
	| 'url'
	| 'date'
	| 'textarea'
	| 'select'
	| 'radio'
	| 'checkbox'
	| 'toggle'
	| 'upload'
	| 'hidden';

/** Field column width inside the form row. */
const WIDTH_OPTIONS: ControlOption[] = [
	{ value: '100', label: __( 'Full', 'flexa-block' ) },
	{ value: '50', label: __( 'Half', 'flexa-block' ) },
];

/** Kinds that render a list of choices. */
const CHOICE_KINDS: SubscribeFieldKind[] = [ 'select', 'radio', 'checkbox' ];
/** Kinds whose placeholder is meaningful (single-line inputs + the select prompt). */
const PLACEHOLDER_KINDS: SubscribeFieldKind[] = [ 'email', 'text', 'tel', 'url', 'date', 'textarea', 'select' ];

const hasOptions = ( kind: SubscribeFieldKind ): boolean => CHOICE_KINDS.includes( kind );
const hasPlaceholder = ( kind: SubscribeFieldKind ): boolean => PLACEHOLDER_KINDS.includes( kind );

/**
 * Derive a safe field `name` from a label. Mirrors the PHP fallback in
 * HTML_Helpers::subscribe_field_name() so the editor hint matches the front end.
 */
export const deriveFieldName = ( label: string, fallback: string ): string => {
	const slug = ( label || '' )
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '_' )
		.replace( /^_+|_+$/g, '' );
	return slug || fallback;
};

/** Split an options textarea (one per line) into a trimmed, non-empty list. */
const parseOptions = ( raw: string ): string[] =>
	raw
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( ( line ) => line !== '' );

/**
 * Field settings panel — adapts its controls to the field `kind`.
 */
export const FieldSettingsPanel = ( {
	attributes,
	setAttributes,
	fallbackName,
	kind,
}: {
	attributes: SubscribeFieldAttributes;
	setAttributes: ( attrs: Partial< SubscribeFieldAttributes > ) => void;
	fallbackName: string;
	kind: SubscribeFieldKind;
} ): JSX.Element => {
	const { label, fieldName, placeholder, required, showLabel, width, rows, options, value, accept, multiple, maxSize } = attributes;

	// The hidden field carries no visible chrome — only a key and a fixed value.
	if ( 'hidden' === kind ) {
		return (
			<PanelBody title={ __( 'Hidden field', 'flexa-block' ) } initialOpen={ true }>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Field name', 'flexa-block' ) }
					value={ fieldName ?? '' }
					placeholder={ fallbackName }
					onChange={ ( v: string ) => setAttributes( { fieldName: v } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Value', 'flexa-block' ) }
					help={ __( 'A fixed value sent with the submission (e.g. a source tag).', 'flexa-block' ) }
					value={ value ?? '' }
					onChange={ ( v: string ) => setAttributes( { value: v } ) }
				/>
			</PanelBody>
		);
	}

	return (
		<PanelBody title={ __( 'Field', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show label', 'flexa-block' ) }
				checked={ showLabel !== false }
				onChange={ ( v: boolean ) => setAttributes( { showLabel: v } ) }
			/>
			{ showLabel !== false && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Label', 'flexa-block' ) }
					value={ label ?? '' }
					onChange={ ( v: string ) => setAttributes( { label: v } ) }
				/>
			) }
			{ hasPlaceholder( kind ) && (
				<TextControl
					__nextHasNoMarginBottom
					label={ 'select' === kind ? __( 'Prompt', 'flexa-block' ) : __( 'Placeholder', 'flexa-block' ) }
					value={ placeholder ?? '' }
					onChange={ ( v: string ) => setAttributes( { placeholder: v } ) }
				/>
			) }
			{ hasOptions( kind ) && (
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Options', 'flexa-block' ) }
					help={ __( 'One choice per line.', 'flexa-block' ) }
					value={ ( options ?? [] ).join( '\n' ) }
					onChange={ ( v: string ) => setAttributes( { options: parseOptions( v ) } ) }
				/>
			) }
			{ 'upload' === kind && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Accepted files', 'flexa-block' ) }
						help={ __( 'An accept list, e.g. ".pdf,.jpg,image/*". Leave blank to allow any.', 'flexa-block' ) }
						value={ accept ?? '' }
						onChange={ ( v: string ) => setAttributes( { accept: v } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Allow multiple files', 'flexa-block' ) }
						checked={ !! multiple }
						onChange={ ( v: boolean ) => setAttributes( { multiple: v } ) }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Max size (MB)', 'flexa-block' ) }
						value={ maxSize ?? 5 }
						min={ 1 }
						max={ 25 }
						onChange={ ( v?: number ) => setAttributes( { maxSize: v ?? 5 } ) }
					/>
				</>
			) }
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Field name', 'flexa-block' ) }
				help={ __( 'The key this field is emailed under. Leave blank to derive it from the label.', 'flexa-block' ) }
				value={ fieldName ?? '' }
				placeholder={ deriveFieldName( label ?? '', fallbackName ) }
				onChange={ ( v: string ) => setAttributes( { fieldName: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Required', 'flexa-block' ) }
				checked={ !! required }
				onChange={ ( v: boolean ) => setAttributes( { required: v } ) }
			/>
			<Segmented
				label={ __( 'Width', 'flexa-block' ) }
				value={ width || '100' }
				onChange={ ( v ) => setAttributes( { width: v as SubscribeFieldAttributes[ 'width' ] } ) }
				options={ WIDTH_OPTIONS }
			/>
			{ 'textarea' === kind && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Rows', 'flexa-block' ) }
					value={ rows ?? 4 }
					min={ 2 }
					max={ 12 }
					onChange={ ( v?: number ) => setAttributes( { rows: v ?? 4 } ) }
				/>
			) }
		</PanelBody>
	);
};

/** Render the inert control preview for the canvas. */
const FieldPreview = ( { kind, attributes }: { kind: SubscribeFieldKind; attributes: SubscribeFieldAttributes } ): JSX.Element => {
	const { placeholder, rows, options, multiple } = attributes;
	const list = options ?? [];

	if ( 'textarea' === kind ) {
		return <textarea className="flexa-field__control" placeholder={ placeholder } rows={ rows ?? 4 } disabled />;
	}
	if ( 'select' === kind ) {
		return (
			<select className="flexa-field__control" disabled>
				{ placeholder && <option>{ placeholder }</option> }
				{ list.map( ( opt, i ) => (
					<option key={ i }>{ opt }</option>
				) ) }
			</select>
		);
	}
	if ( 'radio' === kind || 'checkbox' === kind ) {
		return (
			<div className={ `flexa-field__options flexa-field__options--${ kind }` }>
				{ list.map( ( opt, i ) => (
					<label key={ i } className="flexa-field__option">
						<input type={ kind } className="flexa-field__choice" disabled />
						<span>{ opt }</span>
					</label>
				) ) }
			</div>
		);
	}
	if ( 'toggle' === kind ) {
		// Left interactive (uncontrolled) so the switch visibly responds in the
		// editor; the value is never read here — the front end owns submission.
		return (
			<span className="flexa-field__toggle">
				<input type="checkbox" className="flexa-field__toggle-input" />
				<span className="flexa-field__switch-track" aria-hidden="true" />
			</span>
		);
	}
	if ( 'upload' === kind ) {
		return <input className="flexa-field__control" type="file" multiple={ !! multiple } disabled />;
	}
	return <input className="flexa-field__control" type={ kind } placeholder={ placeholder } disabled />;
};

/**
 * The full edit component for a field child. `kind` picks the control.
 */
export const SubscribeFieldEdit = ( {
	kind,
	fallbackName,
	attributes,
	setAttributes,
	clientId,
}: EditProps< SubscribeFieldAttributes > & { kind: SubscribeFieldKind; fallbackName: string } ): JSX.Element => {
	const { label, placeholder, required, showLabel, width, fieldName, value, blockId, className } = attributes;

	useBlockId( clientId, blockId, setAttributes );

	const isHidden = 'hidden' === kind;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-field',
			! isHidden && `flexa-field--w${ width || '100' }`,
			isHidden && 'flexa-field--hidden-edit',
			blockId && `flexa-subscribe-field-${ blockId }`,
			className
		),
	} );

	const inspector = (
		<InspectorControls>
			<div className="flexa-inspector flexa-subscribe-field-inspector">
				<InspectorTabs
					layout={
						<FieldSettingsPanel
							attributes={ attributes }
							setAttributes={ setAttributes }
							fallbackName={ fallbackName }
							kind={ kind }
						/>
					}
					style={ <></> }
					advanced={ <></> }
				/>
			</div>
		</InspectorControls>
	);

	if ( isHidden ) {
		return (
			<>
				{ inspector }
				<div { ...blockProps }>
					<span className="flexa-field__hidden-chip">
						{ __( 'Hidden field', 'flexa-block' ) }
						{ ' · ' }
						<code>{ fieldName || fallbackName }</code>
						{ value ? ` = ${ value }` : '' }
					</span>
				</div>
			</>
		);
	}

	return (
		<>
			{ inspector }
			<div { ...blockProps }>
				{ showLabel !== false && ( label || placeholder || fallbackName ) && (
					<span className="flexa-field__label">
						{ label || placeholder || fallbackName }
						{ required && <span className="flexa-field__required"> *</span> }
					</span>
				) }
				<FieldPreview kind={ kind } attributes={ attributes } />
			</div>
		</>
	);
};
