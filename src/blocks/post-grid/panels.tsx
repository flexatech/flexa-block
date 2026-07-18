/**
 * Post Grid block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation) come from @components; these cover the parts
 * unique to the post grid: the query (post type, count, order, taxonomy filter),
 * the grid layout (width, per-device columns and gaps, card alignment), the
 * element toggles (image / title / meta / excerpt / read-more / pagination) and
 * the card typography and colours. Following the "prefer theme styles" rule the
 * card colours are unset by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl, FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	CONTENT_ALIGN_OPTIONS,
	CONTAINER_WIDTH_OPTIONS,
	TEXT_TAG_OPTIONS,
	ASPECT_RATIO_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type { BoxValue, LengthValue, PanelProps, PostGridAttributes, TypographyDevice } from '../../types';

type PgPanelProps = PanelProps< PostGridAttributes >;

/** Post ordering fields (many values → SelectControl). */
const ORDER_BY_OPTIONS = [
	{ value: 'date', label: __( 'Date', 'flexa-block' ) },
	{ value: 'title', label: __( 'Title', 'flexa-block' ) },
	{ value: 'menu_order', label: __( 'Menu order', 'flexa-block' ) },
	{ value: 'rand', label: __( 'Random', 'flexa-block' ) },
	{ value: 'comment_count', label: __( 'Comment count', 'flexa-block' ) },
];

/** Sort direction (two values → Segmented). */
const ORDER_OPTIONS = [
	{ value: 'desc', label: __( 'Descending', 'flexa-block' ) },
	{ value: 'asc', label: __( 'Ascending', 'flexa-block' ) },
];

/** Where a Post Filter's search box looks. See PostGridAttributes.searchScope. */
const SEARCH_SCOPE_OPTIONS = [
	{ value: 'title_excerpt', label: __( 'Title and excerpt', 'flexa-block' ) },
	{ value: 'all', label: __( 'Everything, including block markup', 'flexa-block' ) },
];

/**
 * Query panel — where the posts come from: post type, how many, ordering and an
 * optional taxonomy-term filter. Post types and taxonomies are read live from the
 * site so the dropdowns match what's registered.
 */
export const PostGridQueryPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => {
	const { postType, postsPerPage, offset, orderBy, order, taxonomy, terms, excludeCurrent, searchScope, showResultCount, resultCountText, blockId, htmlTag } = attributes;

	// Public post types + the chosen type's taxonomies, read from the core store.
	const { postTypeOptions, taxonomyOptions } = useSelect(
		( select: ( store: string ) => any ) => {
			const core = select( 'core' );
			const types = core.getPostTypes( { per_page: -1 } ) || [];
			const taxes = core.getTaxonomies( { per_page: -1, type: postType || 'post' } ) || [];
			const skip = [ 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'wp_font_family', 'wp_font_face' ];
			return {
				postTypeOptions: types
					.filter( ( t: any ) => t.viewable && ! skip.includes( t.slug ) )
					.map( ( t: any ) => ( { value: t.slug, label: t.name || t.slug } ) ),
				taxonomyOptions: [
					{ value: '', label: __( '— None —', 'flexa-block' ) },
					...taxes.map( ( t: any ) => ( { value: t.slug, label: t.name || t.slug } ) ),
				],
			};
		},
		[ postType ]
	);

	// The chosen taxonomy's terms, read live so the multi-select matches the site.
	const termRecords = useSelect(
		( select: ( store: string ) => any ) => {
			if ( ! taxonomy ) {
				return [] as Array< { id: number; name: string } >;
			}
			const records = select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, { per_page: -1, context: 'view' } );
			return ( Array.isArray( records ) ? records : [] ).map( ( t: any ) => ( { id: t.id, name: t.name || String( t.id ) } ) );
		},
		[ taxonomy ]
	);

	// Terms round-trip as a comma-separated string of term IDs (render.php's
	// tax_query reads numeric IDs → term_id). The token field shows names.
	const selectedIds = String( terms || '' ).split( ',' ).map( ( s ) => s.trim() ).filter( Boolean );
	const idToName = new Map( termRecords.map( ( t: { id: number; name: string } ) => [ String( t.id ), t.name ] ) );
	const nameToId = new Map( termRecords.map( ( t: { id: number; name: string } ) => [ t.name.toLowerCase(), String( t.id ) ] ) );
	const selectedTokens = selectedIds.map( ( id ) => idToName.get( id ) || id );
	const termSuggestions = termRecords.map( ( t: { id: number; name: string } ) => t.name );

	const onTermsChange = ( tokens: Array< string | { value: string } > ) => {
		const ids = tokens
			.map( ( tok ) => {
				const name = typeof tok === 'string' ? tok : tok.value;
				return nameToId.get( String( name ).toLowerCase() ) ?? ( /^\d+$/.test( String( name ) ) ? String( name ) : '' );
			} )
			.filter( Boolean );
		setAttributes( { terms: Array.from( new Set( ids ) ).join( ',' ) } );
	};

	const typeOptions = postTypeOptions.length ? postTypeOptions : [ { value: 'post', label: __( 'Post', 'flexa-block' ) } ];

	return (
		<PanelBody title={ __( 'Query', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Post type', 'flexa-block' ) }
				value={ postType || 'post' }
				options={ typeOptions }
				onChange={ ( v: string ) => setAttributes( { postType: v, taxonomy: '', terms: '' } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Posts per page', 'flexa-block' ) }
				value={ typeof postsPerPage === 'number' ? postsPerPage : 6 }
				min={ 1 }
				max={ 24 }
				onChange={ ( v?: number ) => setAttributes( { postsPerPage: v || 1 } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				type="number"
				label={ __( 'Offset', 'flexa-block' ) }
				help={ __( 'Skip this many posts before the first shown.', 'flexa-block' ) }
				value={ String( offset ?? 0 ) }
				onChange={ ( v: string ) => setAttributes( { offset: parseInt( v, 10 ) || 0 } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Order by', 'flexa-block' ) }
				value={ orderBy || 'date' }
				options={ ORDER_BY_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { orderBy: v as PostGridAttributes[ 'orderBy' ] } ) }
			/>
			<Segmented
				label={ __( 'Order', 'flexa-block' ) }
				value={ order || 'desc' }
				onChange={ ( v ) => setAttributes( { order: v as PostGridAttributes[ 'order' ] } ) }
				options={ ORDER_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Filter by taxonomy', 'flexa-block' ) }
				value={ taxonomy || '' }
				options={ taxonomyOptions }
				onChange={ ( v: string ) => setAttributes( { taxonomy: v, terms: '' } ) }
			/>
			{ !! taxonomy && (
				<FormTokenField
					label={ __( 'Terms', 'flexa-block' ) }
					value={ selectedTokens }
					suggestions={ termSuggestions }
					onChange={ onTermsChange }
					__experimentalExpandOnFocus
					__experimentalShowHowTo={ false }
					__nextHasNoMarginBottom
				/>
			) }
			{ !! taxonomy && (
				<p className="flexa-field-help">{ __( 'Pick one or more terms to filter by. Leave empty to show all.', 'flexa-block' ) }</p>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Exclude current post', 'flexa-block' ) }
				checked={ !! excludeCurrent }
				onChange={ ( v: boolean ) => setAttributes( { excludeCurrent: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Visitor search looks in', 'flexa-block' ) }
				help={ __( 'Used by a Post Filter search box. "Everything" is WordPress\'s own behaviour — it searches the raw content, block markup included, so a search for "container" matches every post built with one.', 'flexa-block' ) }
				value={ searchScope || 'title_excerpt' }
				options={ SEARCH_SCOPE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { searchScope: v as PostGridAttributes[ 'searchScope' ] } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show result count', 'flexa-block' ) }
				help={ __( '"12 posts found", above the grid — it updates as visitors filter. It is always announced to screen readers; this only decides whether everyone else sees it too.', 'flexa-block' ) }
				checked={ !! showResultCount }
				onChange={ ( v: boolean ) => setAttributes( { showResultCount: v } ) }
			/>
			{ !! showResultCount && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Result count text', 'flexa-block' ) }
					help={ __( '%s is the number of posts. Leave empty for "%s posts found".', 'flexa-block' ) }
					placeholder={ __( '%s posts found', 'flexa-block' ) }
					value={ resultCountText ?? '' }
					onChange={ ( v: string ) => setAttributes( { resultCountText: v } ) }
				/>
			) }
			<TextControl
				__nextHasNoMarginBottom
				readOnly
				label={ __( 'Grid ID', 'flexa-block' ) }
				help={ __( 'This grid\'s id. A Post Filter normally finds it on its own — copy this only when the filter bar lives somewhere the editor can\'t see it, like a template.', 'flexa-block' ) }
				value={ blockId || __( '— saved when you first edit the block —', 'flexa-block' ) }
				onChange={ () => undefined }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'section' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — content width, per-device columns and gaps, equal-height cards
 * and the card content alignment.
 */
export const PostGridLayoutPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, widthBoxed, widthFullWidth, columns, rowGap, columnGap, equalHeight, contentAlign, contentPadding, contentGap } = attributes;
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as PostGridAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ LENGTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => {
					const next: Partial< PostGridAttributes > = {};
					next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: v.value ?? '', unit: v.unit || ( isBoxed ? 'px' : '%' ) } };
					setAttributes( next );
				} }
			/>
			<SliderUnit
				label={ __( 'Columns', 'flexa-block' ) }
				value={ columns?.[ device ] || {} }
				units={ [ { value: '', label: '' } ] }
				defaultUnit=""
				min={ 1 }
				max={ { '': 12 } }
				onChange={ ( v: LengthValue ) => setAttributes( { columns: { ...columns, [ device ]: { value: v.value ?? '', unit: '' } } } ) }
			/>
			<SliderUnit
				label={ __( 'Row gap', 'flexa-block' ) }
				value={ rowGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { rowGap: { ...rowGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Column gap', 'flexa-block' ) }
				value={ columnGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { columnGap: { ...columnGap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Card alignment', 'flexa-block' ) }
				responsive
				value={ contentAlign?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { contentAlign: { ...contentAlign, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<Dimensions
				label={ __( 'Content padding', 'flexa-block' ) }
				value={ ( contentPadding?.[ device ] || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { contentPadding: { ...contentPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Content gap', 'flexa-block' ) }
				value={ contentGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentGap: { ...contentGap, [ device ]: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Equal height cards', 'flexa-block' ) }
				checked={ equalHeight !== false }
				onChange={ ( v: boolean ) => setAttributes( { equalHeight: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Elements panel — which parts of each card to show (featured image + ratio +
 * source size, title + tag, meta and its sub-fields, excerpt + length, read-more
 * button + label) and whether to page through the results.
 */
export const PostGridElementsPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => {
	const {
		showImage, imageSize, imageRatio,
		showTitle, titleTag,
		showMeta, showAuthor, showAvatar, avatarSize, showDate, showComments, showTaxonomy,
		showExcerpt, excerptLength,
		showReadMore, readMoreText,
	} = attributes;

	return (
		<PanelBody title={ __( 'Elements', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Featured image', 'flexa-block' ) }
				checked={ showImage !== false }
				onChange={ ( v: boolean ) => setAttributes( { showImage: v } ) }
			/>
			{ showImage !== false && (
				<>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Image ratio', 'flexa-block' ) }
						value={ imageRatio || '16/9' }
						options={ [ { value: '', label: __( 'Original', 'flexa-block' ) }, ...ASPECT_RATIO_OPTIONS ] }
						onChange={ ( v: string ) => setAttributes( { imageRatio: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Image source size', 'flexa-block' ) }
						help={ __( 'Registered size name (e.g. large, medium, full).', 'flexa-block' ) }
						value={ imageSize || 'large' }
						onChange={ ( v: string ) => setAttributes( { imageSize: v } ) }
					/>
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Title', 'flexa-block' ) }
				checked={ showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle !== false && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Title tag', 'flexa-block' ) }
					value={ titleTag || 'h3' }
					options={ TEXT_TAG_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Meta', 'flexa-block' ) }
				checked={ showMeta !== false }
				onChange={ ( v: boolean ) => setAttributes( { showMeta: v } ) }
			/>
			{ showMeta !== false && (
				<>
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Author', 'flexa-block' ) } checked={ showAuthor !== false } onChange={ ( v: boolean ) => setAttributes( { showAuthor: v } ) } />
					{ showAuthor !== false && (
						<>
							<ToggleControl __nextHasNoMarginBottom label={ __( 'Author avatar', 'flexa-block' ) } checked={ !! showAvatar } onChange={ ( v: boolean ) => setAttributes( { showAvatar: v } ) } />
							{ !! showAvatar && (
								<RangeControl
									__nextHasNoMarginBottom
									label={ __( 'Avatar size', 'flexa-block' ) }
									value={ typeof avatarSize === 'number' ? avatarSize : 24 }
									min={ 12 }
									max={ 96 }
									onChange={ ( v?: number ) => setAttributes( { avatarSize: v ?? 24 } ) }
								/>
							) }
						</>
					) }
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Date', 'flexa-block' ) } checked={ showDate !== false } onChange={ ( v: boolean ) => setAttributes( { showDate: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Comments', 'flexa-block' ) } checked={ !! showComments } onChange={ ( v: boolean ) => setAttributes( { showComments: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Taxonomy', 'flexa-block' ) } checked={ !! showTaxonomy } onChange={ ( v: boolean ) => setAttributes( { showTaxonomy: v } ) } />
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Excerpt', 'flexa-block' ) }
				checked={ showExcerpt !== false }
				onChange={ ( v: boolean ) => setAttributes( { showExcerpt: v } ) }
			/>
			{ showExcerpt !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Excerpt length (words)', 'flexa-block' ) }
					value={ typeof excerptLength === 'number' ? excerptLength : 20 }
					min={ 0 }
					max={ 100 }
					onChange={ ( v?: number ) => setAttributes( { excerptLength: v ?? 0 } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Read more button', 'flexa-block' ) }
				checked={ showReadMore !== false }
				onChange={ ( v: boolean ) => setAttributes( { showReadMore: v } ) }
			/>
			{ showReadMore !== false && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Read more text', 'flexa-block' ) }
					value={ readMoreText ?? '' }
					onChange={ ( v: string ) => setAttributes( { readMoreText: v } ) }
				/>
			) }

		</PanelBody>
	);
};

/** The typography attributes a TypoGroup can drive. */
type TypoAttr = 'titleTypography' | 'metaTypography' | 'excerptTypography' | 'resultCountTypography';

/**
 * One element's typography, on the active device.
 *
 * No heading of its own: the panel it sits in already names the element, and each
 * control inside carries its own device tag — a "Title" caption under a "Title
 * typography" panel is the same word twice.
 */
const TypoGroup = ( {
	attr,
	attributes,
	setAttributes,
}: {
	attr: TypoAttr;
	attributes: PostGridAttributes;
	setAttributes: PgPanelProps[ 'setAttributes' ];
} ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes[ attr ], device );
	const set = ( patch: Partial< TypographyDevice > ) => {
		const next: Partial< PostGridAttributes > = {};
		next[ attr ] = patchDevice( attributes[ attr ], device, patch );
		setAttributes( next );
	};

	return <TypographyControls value={ value } onChange={ set } />;
};

/**
 * Typography — one panel per element, not one panel with three elements in it.
 *
 * A typography group is six controls tall. Stacked three deep in a single "Typography"
 * panel, an author who opened it to change the title's size scrolled past everything
 * that styles the meta and the excerpt to find it, and had no way to put either away.
 * Three panels: open the one you are editing, and the other two are a line each.
 */
export const PostGridTypographyPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => (
	<>
		<PanelBody title={ __( 'Title typography', 'flexa-block' ) } initialOpen={ false }>
			<TypoGroup attr="titleTypography" attributes={ attributes } setAttributes={ setAttributes } />
		</PanelBody>
		<PanelBody title={ __( 'Meta typography', 'flexa-block' ) } initialOpen={ false }>
			<TypoGroup attr="metaTypography" attributes={ attributes } setAttributes={ setAttributes } />
		</PanelBody>
		<PanelBody title={ __( 'Excerpt typography', 'flexa-block' ) } initialOpen={ false }>
			<TypoGroup attr="excerptTypography" attributes={ attributes } setAttributes={ setAttributes } />
		</PanelBody>
	</>
);

/**
 * Result count panel — the "12 posts found" line.
 *
 * Its own panel rather than another row in Typography/Colours: it is not part of a
 * card, it is the one line ABOUT the cards, and it only exists when the author has
 * switched it on. Rendered only then — a panel for something invisible is furniture.
 */
export const PostGridResultCountPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Result count', 'flexa-block' ) } initialOpen={ false }>
		<TypoGroup attr="resultCountTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<DualColor
			label={ __( 'Colour', 'flexa-block' ) }
			value={ attributes.resultCountColor || {} }
			onChange={ ( v ) => setAttributes( { resultCountColor: v } ) }
		/>
		<Segmented
			label={ __( 'Alignment', 'flexa-block' ) }
			value={ attributes.resultCountAlign || '' }
			onChange={ ( v ) => setAttributes( { resultCountAlign: v as PostGridAttributes[ 'resultCountAlign' ] } ) }
			options={ [
				{ value: '', label: __( 'Inherit', 'flexa-block' ) },
				{ value: 'left', label: __( 'Left', 'flexa-block' ) },
				{ value: 'center', label: __( 'Center', 'flexa-block' ) },
				{ value: 'right', label: __( 'Right', 'flexa-block' ) },
			] }
		/>
	</PanelBody>
);

/**
 * Colours panel — card title / meta / excerpt text colours and the card
 * background. All light/dark pairs, unset by default so the card inherits the
 * theme until the user picks a value. (The read-more button has its own panel.)
 */
export const PostGridColorsPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
		<DualColor label={ __( 'Title', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		<DualColor label={ __( 'Meta', 'flexa-block' ) } value={ attributes.metaColor || {} } onChange={ ( v ) => setAttributes( { metaColor: v } ) } />
		<DualColor label={ __( 'Excerpt', 'flexa-block' ) } value={ attributes.excerptColor || {} } onChange={ ( v ) => setAttributes( { excerptColor: v } ) } />
		<DualColor label={ __( 'Card background', 'flexa-block' ) } value={ attributes.cardBackground || {} } onChange={ ( v ) => setAttributes( { cardBackground: v } ) } />
	</PanelBody>
);

/**
 * Button panel — the read-more button styling: typography, text + background
 * colours (base and hover), corner radius and padding. Empty values inherit the
 * theme's button look (the button keeps `wp-element-button`); only what the user
 * sets produces CSS. Rendered only when the read-more button is shown.
 */
export const PostGridButtonPanel = ( { attributes, setAttributes }: PgPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.buttonTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { buttonTypography: patchDevice( attributes.buttonTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Width', 'flexa-block' ) }
				value={ attributes.buttonWidth || 'auto' }
				onChange={ ( v ) => setAttributes( { buttonWidth: v as PostGridAttributes[ 'buttonWidth' ] } ) }
				options={ [ { value: 'auto', label: __( 'Boxed', 'flexa-block' ) }, { value: 'full', label: __( 'Full width', 'flexa-block' ) } ] }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ attributes.buttonAlign || 'left' }
				onChange={ ( v ) => setAttributes( { buttonAlign: v as PostGridAttributes[ 'buttonAlign' ] } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.buttonTextColor || {} } onChange={ ( v ) => setAttributes( { buttonTextColor: v } ) } />
			<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.buttonTextColorHover || {} } onChange={ ( v ) => setAttributes( { buttonTextColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.buttonBackground || {} } onChange={ ( v ) => setAttributes( { buttonBackground: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ attributes.buttonBackgroundHover || {} } onChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } ) } />
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ attributes.buttonRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				value={ ( attributes.buttonPadding || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { buttonPadding: v } ) }
			/>
		</PanelBody>
	);
};
