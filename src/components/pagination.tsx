/**
 * Shared pagination — inspector panel + editor nav, used by Post Grid and RSS.
 *
 * `PaginationPanel` is the Style-tab control set: the pagination type (none /
 * numbered / load-more), alignment, and type-adaptive styling — numbered shows
 * the page-link colours + prev/next labels + radius; load-more shows the button
 * text + colour + background. `PaginationNav` renders the matching preview in the
 * editor (a functional numbered nav or a load-more button), styled inline to
 * mirror the PHP CSS generator (CSS_Helpers::add_pagination / add_loadmore). The
 * front-end paging behaviour stays per-block (Post Grid queries the DB via AJAX;
 * RSS slices a fetched pool) — only these UI pieces + the generator are shared.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, RangeControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import { Segmented, SliderUnit, DualColor } from './controls';
import { CONTENT_ALIGN_OPTIONS } from './panels';
import { cn, withUnit, CONTENT_ALIGN_TO_FLEX, type CssProps } from '@utils';
import { pageWindow } from '@shared/pagination';
import type { LengthValue, PaginationAttributes, PanelProps } from '../types';

/** Pagination mode (few values → SelectControl). */
const PAGINATION_TYPE_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'numbered', label: __( 'Numbered pages', 'flexa-block' ) },
	{ value: 'loadmore', label: __( 'Load more button', 'flexa-block' ) },
];

type PaginationPanelProps = PanelProps< PaginationAttributes > & {
	/** Current per-page value + setter (rendered only when provided). */
	perPage?: number;
	onPerPage?: ( value: number ) => void;
	/** Load-more reveal count + setter (rendered only when provided). */
	perLoad?: number;
	onPerLoad?: ( value: number ) => void;
	/** Help text under the type selector (block-specific wording). */
	typeHelp?: string;
};

/**
 * Pagination panel — the shared Style-tab controls.
 */
export const PaginationPanel = ( {
	attributes,
	setAttributes,
	perPage,
	onPerPage,
	perLoad,
	onPerLoad,
	typeHelp,
	initialOpen = false,
}: PaginationPanelProps ): JSX.Element => {
	const type = attributes.paginationType || 'none';

	return (
		<PanelBody title={ __( 'Pagination', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Pagination', 'flexa-block' ) }
				help={ typeHelp }
				value={ type }
				options={ PAGINATION_TYPE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { paginationType: v as PaginationAttributes[ 'paginationType' ] } ) }
			/>
			{ 'numbered' === type && undefined !== perPage && onPerPage && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Items per page', 'flexa-block' ) }
					value={ typeof perPage === 'number' ? perPage : 6 }
					min={ 1 }
					max={ 30 }
					onChange={ ( v?: number ) => onPerPage( v || 1 ) }
				/>
			) }
			{ 'none' !== type && (
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ attributes.paginationAlign || 'center' }
					onChange={ ( v ) => setAttributes( { paginationAlign: v as PaginationAttributes[ 'paginationAlign' ] } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			) }
			{ 'none' !== type && (
				<SliderUnit
					label={ __( 'Font size', 'flexa-block' ) }
					value={ attributes.paginationFontSize || {} }
					units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
					defaultUnit="px"
					max={ { px: 80, em: 6, rem: 6 } }
					onChange={ ( v: LengthValue ) => setAttributes( { paginationFontSize: v } ) }
				/>
			) }
			{ 'numbered' === type && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Previous label', 'flexa-block' ) }
						help={ __( 'Leave empty for the default arrow.', 'flexa-block' ) }
						value={ attributes.prevLabel ?? '' }
						onChange={ ( v: string ) => setAttributes( { prevLabel: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Next label', 'flexa-block' ) }
						value={ attributes.nextLabel ?? '' }
						onChange={ ( v: string ) => setAttributes( { nextLabel: v } ) }
					/>
					<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.paginationColor || {} } onChange={ ( v ) => setAttributes( { paginationColor: v } ) } />
					<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.paginationBackground || {} } onChange={ ( v ) => setAttributes( { paginationBackground: v } ) } />
					<DualColor label={ __( 'Active text colour', 'flexa-block' ) } value={ attributes.paginationActiveColor || {} } onChange={ ( v ) => setAttributes( { paginationActiveColor: v } ) } />
					<DualColor label={ __( 'Active background', 'flexa-block' ) } value={ attributes.paginationActiveBackground || {} } onChange={ ( v ) => setAttributes( { paginationActiveBackground: v } ) } />
					<SliderUnit
						label={ __( 'Corner radius', 'flexa-block' ) }
						value={ attributes.paginationRadius || {} }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' }, { value: '%', label: '%' } ] }
						defaultUnit="px"
						max={ { px: 100, em: 10, rem: 10, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { paginationRadius: v } ) }
					/>
				</>
			) }
			{ 'loadmore' === type && (
				<>
					{ undefined !== perLoad && onPerLoad && (
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Items per load', 'flexa-block' ) }
							help={ __( 'How many more items each “Load more” click reveals.', 'flexa-block' ) }
							value={ typeof perLoad === 'number' ? perLoad : 6 }
							min={ 1 }
							max={ 30 }
							onChange={ ( v?: number ) => onPerLoad( v || 1 ) }
						/>
					) }
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Load more text', 'flexa-block' ) }
						value={ attributes.loadMoreText ?? '' }
						onChange={ ( v: string ) => setAttributes( { loadMoreText: v } ) }
					/>
					<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.loadMoreColor || {} } onChange={ ( v ) => setAttributes( { loadMoreColor: v } ) } />
					<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.loadMoreBackground || {} } onChange={ ( v ) => setAttributes( { loadMoreBackground: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/** Page-link inline colours mirroring the generator (editor preview). */
function pageStyles( attributes: PaginationAttributes ): { base: CssProps; current: CssProps } {
	const base: CssProps = {};
	if ( attributes.paginationColor?.light ) base.color = attributes.paginationColor.light;
	if ( attributes.paginationBackground?.light ) base.background = attributes.paginationBackground.light;
	if ( attributes.paginationRadius?.value ) base.borderRadius = withUnit( attributes.paginationRadius.value, attributes.paginationRadius.unit || 'px' );
	if ( attributes.paginationFontSize?.value ) base.fontSize = withUnit( attributes.paginationFontSize.value, attributes.paginationFontSize.unit || 'px' );
	const current: CssProps = { ...base };
	if ( attributes.paginationActiveColor?.light ) current.color = attributes.paginationActiveColor.light;
	if ( attributes.paginationActiveBackground?.light ) current.background = attributes.paginationActiveBackground.light;
	return { base, current };
}

type PaginationNavProps = {
	attributes: PaginationAttributes;
	type: 'none' | 'numbered' | 'loadmore';
	/** Numbered: total pages + current page + page setter. */
	totalPages?: number;
	page?: number;
	onPage?: ( page: number ) => void;
	/** Load-more: whether more items remain + the reveal handler. */
	hasMore?: boolean;
	onLoadMore?: () => void;
};

/**
 * Pagination nav — the editor preview (functional numbered nav / load-more
 * button), styled inline to mirror the front end.
 */
export const PaginationNav = ( { attributes, type, totalPages = 1, page = 1, onPage, hasMore, onLoadMore }: PaginationNavProps ): JSX.Element | null => {
	const wrapStyle: CssProps = { justifyContent: CONTENT_ALIGN_TO_FLEX[ attributes.paginationAlign || 'center' ] || 'center' };

	// `:hover` cannot be an inline style, and the preview is styled inline — so the
	// hovered link is tracked here instead, and wears the Active colours the generator
	// gives it on the front end. Mirrors CSS_Helpers::add_pagination.
	const [ hovered, setHovered ] = useState< string | null >( null );

	if ( 'numbered' === type ) {
		if ( totalPages <= 1 ) {
			return null;
		}
		const { base, current } = pageStyles( attributes );
		// Keyed by the LINK, not the page it leads to: "prev" and the "2" button both
		// lead to page 2, and hovering one must not light the other up.
		const linkStyle = ( key: string, isCurrent = false ): CssProps => ( isCurrent || hovered === key ? current : base );
		const hoverProps = ( key: string ) => ( {
			onMouseEnter: () => setHovered( key ),
			onMouseLeave: () => setHovered( ( h ) => ( h === key ? null : h ) ),
		} );

		return (
			<nav className="flexa-pagination" style={ wrapStyle }>
				{ page > 1 && (
					<button type="button" className="page-numbers prev" style={ linkStyle( 'prev' ) } { ...hoverProps( 'prev' ) } onClick={ () => onPage && onPage( page - 1 ) }>{ attributes.prevLabel || '‹' }</button>
				) }
				{ pageWindow( totalPages, page ).map( ( token, i ) => token === 'dots'
					? <span key={ `d${ i }` } className="page-numbers dots">…</span>
					: <button key={ token } type="button" className={ cn( 'page-numbers', token === page && 'current' ) } style={ linkStyle( `p${ token }`, token === page ) } { ...hoverProps( `p${ token }` ) } onClick={ () => onPage && onPage( token ) }>{ token }</button>
				) }
				{ page < totalPages && (
					<button type="button" className="page-numbers next" style={ linkStyle( 'next' ) } { ...hoverProps( 'next' ) } onClick={ () => onPage && onPage( page + 1 ) }>{ attributes.nextLabel || '›' }</button>
				) }
			</nav>
		);
	}

	if ( 'loadmore' === type ) {
		if ( ! hasMore ) {
			return null;
		}
		const btnStyle: CssProps = {};
		if ( attributes.loadMoreColor?.light ) btnStyle.color = attributes.loadMoreColor.light;
		if ( attributes.loadMoreBackground?.light ) btnStyle.background = attributes.loadMoreBackground.light;
		if ( attributes.paginationFontSize?.value ) btnStyle.fontSize = withUnit( attributes.paginationFontSize.value, attributes.paginationFontSize.unit || 'px' );
		return (
			<div className="flexa-pagination-loadmore" style={ wrapStyle }>
				<button type="button" className="flexa-pagination-loadmore__btn wp-element-button" style={ btnStyle } onClick={ () => onLoadMore && onLoadMore() }>
					{ attributes.loadMoreText || __( 'Load more', 'flexa-block' ) }
				</button>
			</div>
		);
	}

	return null;
};
