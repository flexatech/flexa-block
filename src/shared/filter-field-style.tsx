/**
 * Per-field styling for the Post Filter's children — one implementation, three blocks.
 *
 * The bar's own Control panel styles EVERY control at once, which is what keeps a
 * filter bar internally consistent. These three panels are the escape hatch: they
 * paint one field's own box, and they outrank the bar (`.flexa-filter-field.
 * flexa-filter-field-<id> …` is one class more specific than `.flexa-post-filter-<id>
 * …`), so "the Tag box, but grey" doesn't mean giving up the shared styling for
 * everything else.
 *
 * "The field's box" is whatever that child actually renders — the search input, the
 * select or disclosure summary, the reset button, the chips (which ARE the control in
 * pills mode). A child only ever renders one of them, so the other selectors in the
 * list are inert rather than wrong. Mirrors Filter_Field_CSS on the PHP side, which
 * emits the same selector for the front end.
 *
 * @package Flexa\Block
 */

import { BackgroundPanel, BorderPanel, ShadowPanel } from '@components';
import { applyBackgroundPreview, applyBorderPreview, boxShadowPreview, editorCss, rawDevice } from '@utils';
import type { CssProps } from '@utils';
import type { BackgroundAttr, BorderDevice, BoxShadowAttr, DeviceKey, ResponsiveValue } from '../types';

/** The style attributes every filter child carries. */
export interface FilterFieldStyleAttrs {
	blockId?: string;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
}

/** The surfaces one filter child can render. See the file header. */
export const fieldSurface = ( blockId: string ): string => {
	const wrap = `.flexa-filter-field.flexa-filter-field-${ blockId }`;
	return [
		`${ wrap } .flexa-filter-field__control`,
		`${ wrap } .flexa-filter-reset__control`,
		// A chip is the control only in pills mode. In a menu or a checkbox list the
		// option is a line in a list, not a box — a border there frames every term.
		`${ wrap } .flexa-filter-field__options--pills .flexa-filter-field__option-text`,
	].join( ', ' );
};

/**
 * The scoped <style> a child renders on the canvas, so its box previews the way the
 * generator will paint it. Light values only — the editor canvas previews light mode.
 *
 * @param attributes The child's style attributes.
 * @param blockId    The child's blockId.
 * @param device     The device being previewed.
 * @return CSS text, or '' when the block has nothing to say.
 */
export const filterFieldCss = ( attributes: FilterFieldStyleAttrs, blockId: string, device: DeviceKey ): string => {
	if ( ! blockId ) {
		return '';
	}

	const props: CssProps = {};
	applyBackgroundPreview( props, attributes.background || {} );
	applyBorderPreview( props, rawDevice( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow || {} );
	if ( shadow ) {
		props.boxShadow = shadow;
	}

	const selector = fieldSurface( blockId );

	return editorCss(
		Object.keys( props ).map( ( key ) => ( {
			selector,
			prop: key.replace( /[A-Z]/g, ( c ) => '-' + c.toLowerCase() ),
			value: props[ key ],
		} ) )
	);
};

/** Props a filter child passes straight through to the shared panels. */
interface StylePanelProps {
	attributes: FilterFieldStyleAttrs;
	setAttributes: ( attrs: Partial< FilterFieldStyleAttrs > ) => void;
}

/**
 * Background / Border / Box Shadow for one filter child — the same three shared panels
 * every other block uses, bound to the same three attribute names, so there is nothing
 * new for an author to learn and nothing new here to maintain.
 */
export const FilterFieldStylePanels = ( { attributes, setAttributes }: StylePanelProps ): JSX.Element => (
	<>
		<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
		<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
		<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
	</>
);
