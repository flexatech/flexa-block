/**
 * Filter: Search — editor component (child of flexa/post-filter).
 *
 * The input previews disabled: it filters nothing on the canvas, and the parent's
 * scoped <style> makes it look the way it will on the front end.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

import { Segmented, VisibilityPanel, useBlockId, useDevice } from '@components';
import { cn } from '@utils';
import type { EditProps, FilterSearchAttributes } from '../../types';
import { FIELD_WIDTH_OPTIONS } from '@shared/filter-widths';
import { FilterFieldStylePanels, filterFieldCss } from '@shared/filter-field-style';

/**
 * Filter: Search edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< FilterSearchAttributes > ): JSX.Element {
	const { blockId, label, showLabel, placeholder, width } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const fieldCss = filterFieldCss( attributes, blockId || '', device );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-filter-field',
			'flexa-filter-field--search',
			`flexa-filter-field--w${ width || 'auto' }`,
			blockId && `flexa-filter-field-${ blockId }`
		),
	} );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-filter-search-inspector">
					<PanelBody title={ __( 'Search', 'flexa-block' ) } initialOpen={ true }>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Label', 'flexa-block' ) }
							value={ label ?? '' }
							onChange={ ( v: string ) => setAttributes( { label: v } ) }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show label', 'flexa-block' ) }
							help={ __( 'When off the label is still read out to screen readers — it is hidden, not removed.', 'flexa-block' ) }
							checked={ false !== showLabel }
							onChange={ ( v: boolean ) => setAttributes( { showLabel: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Placeholder', 'flexa-block' ) }
							value={ placeholder ?? '' }
							onChange={ ( v: string ) => setAttributes( { placeholder: v } ) }
						/>
						<Segmented
							label={ __( 'Width', 'flexa-block' ) }
							value={ width || 'auto' }
							onChange={ ( v ) => setAttributes( { width: v as FilterSearchAttributes[ 'width' ] } ) }
							options={ FIELD_WIDTH_OPTIONS }
						/>
					</PanelBody>
					<FilterFieldStylePanels attributes={ attributes } setAttributes={ setAttributes } />
					<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ fieldCss && <style>{ fieldCss }</style> }
				{ label && (
					<label className={ cn( 'flexa-filter-field__label', ! showLabel && 'screen-reader-text' ) }>{ label }</label>
				) }
				<span className="flexa-filter-field__search-wrap">
					{ /* Mirrors render.php: decorative magnifier inside the box. */ }
					<svg
						className="flexa-filter-field__search-icon"
						viewBox="0 0 24 24"
						width="18"
						height="18"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						aria-hidden="true"
						focusable="false"
					>
						<circle cx="11" cy="11" r="7" />
						<path d="M20 20l-3.5-3.5" />
					</svg>
					<input
						type="search"
						className="flexa-filter-field__control"
						placeholder={ placeholder || '' }
						disabled
					/>
				</span>
			</div>
		</>
	);
}
