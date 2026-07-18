/**
 * Column-width options shared by the three post-filter children.
 *
 * One list, so "Full / Half / Auto" means the same thing in every filter control's
 * inspector and maps to the same `.flexa-filter-field--w*` class the parent styles.
 */

import { __ } from '@wordpress/i18n';

/** Width values a filter control can take. Mirrors Filter_Fields::WIDTHS. */
export type FilterWidth = '100' | '50' | 'auto';

export const FIELD_WIDTH_OPTIONS: Array< { value: FilterWidth; label: string } > = [
	{ value: 'auto', label: __( 'Auto', 'flexa-block' ) },
	{ value: '50', label: __( 'Half', 'flexa-block' ) },
	{ value: '100', label: __( 'Full', 'flexa-block' ) },
];
