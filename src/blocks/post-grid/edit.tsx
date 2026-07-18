/**
 * Post Grid block — editor component.
 *
 * Dynamic block: the real grid is produced server-side by render.php (WP_Query).
 * Here the editor previews the query LIVE via `getEntityRecords`, so the chosen
 * post type / count / order are reflected as you edit. The card layout, gaps,
 * typography and colours are drawn with inline styles that mirror the PHP CSS
 * generator (Post_Grid_CSS), and nothing is coloured by default so the cards
 * inherit the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	PaginationPanel,
	PaginationNav,
	ImagePlaceholder,
	useStableBlockId,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	PostGridQueryPanel,
	PostGridLayoutPanel,
	PostGridElementsPanel,
	PostGridTypographyPanel,
	PostGridColorsPanel,
	PostGridResultCountPanel,
	PostGridButtonPanel,
} from './panels';
import type { DeviceKey, EditProps, PostGridAttributes } from '../../types';

/** Strip HTML tags for the plain-text excerpt/title preview. */
const stripHtml = ( html: string ): string => String( html || '' ).replace( /<[^>]+>/g, '' ).trim();

/** Trim a plain-text excerpt to a word count. */
const trimWords = ( text: string, words: number ): string => {
	if ( ! words || words < 1 ) return text;
	const parts = text.split( /\s+/ ).filter( Boolean );
	return parts.length > words ? parts.slice( 0, words ).join( ' ' ) + '…' : text;
};

/** Styled-wrapper preview (width, padding, background). Border + shadow live on each card. */
const buildWrapperStyle = ( attributes: PostGridAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
	const { widthBoxed, widthFullWidth, spacing, background } = attributes;
	const s: CssProps = {};

	const w = effective( isBoxed ? widthBoxed : widthFullWidth, device );
	if ( w.value ) {
		const wu = w.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, wu );
		if ( isBoxed ) {
			s.marginLeft = 'auto';
			s.marginRight = 'auto';
		}
	}
	const sp = effective( spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;

	applyBackgroundPreview( s, background );
	return s;
};

/** Grid preview (columns + gaps). */
const buildGridStyle = ( attributes: PostGridAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { display: 'grid' };
	const cols = effective( attributes.columns, device );
	const count = Math.max( 1, parseInt( String( cols.value || '1' ), 10 ) || 1 );
	s.gridTemplateColumns = `repeat(${ count }, minmax(0, 1fr))`;
	const rg = effective( attributes.rowGap, device );
	if ( rg.value ) s.rowGap = withUnit( rg.value, rg.unit || 'px' );
	const cg = effective( attributes.columnGap, device );
	if ( cg.value ) s.columnGap = withUnit( cg.value, cg.unit || 'px' );
	return s;
};

/** Card preview (background + border + shadow + alignment + equal height). */
const buildCardStyle = ( attributes: PostGridAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { display: 'flex', flexDirection: 'column' };
	if ( attributes.cardBackground?.light ) s.background = attributes.cardBackground.light;
	// Border + box-shadow apply to each card (matching the front-end generator).
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	const ca = attributes.contentAlign || {};
	const a = ( device === 'mobile'
		? ca.mobile || ca.tablet || ca.desktop
		: device === 'tablet'
		? ca.tablet || ca.desktop
		: ca.desktop ) || '';
	if ( a ) {
		s.textAlign = a;
		if ( CONTENT_ALIGN_TO_FLEX[ a ] ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ a ];
	}
	if ( attributes.equalHeight !== false ) s.height = '100%';
	return s;
};

/**
 * Post Grid edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< PostGridAttributes > ): JSX.Element {
	const {
		postType, postsPerPage, offset, orderBy, order, taxonomy, terms,
		showImage, imageRatio, showTitle, titleTag, showMeta, showAuthor, showAvatar, avatarSize, showDate, showComments, showTaxonomy,
		showExcerpt, excerptLength, showReadMore, readMoreText, paginationType,
		showResultCount, resultCountText,
		containerType, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const pagType = paginationType || 'numbered';
	const [ page, setPage ] = useState( 1 );
	// Load-more preview: how many pages (of perPageNum) have been revealed so far.
	const [ morePages, setMorePages ] = useState( 1 );

	useStableBlockId( clientId, blockId, setAttributes );

	// Live query preview: fetch the records for the chosen type, plus the real
	// total-page count so both the numbered pager and the load-more button preview
	// accurately. Numbered fetches one page (perPageNum) at a time; load-more grows
	// per_page as pages are revealed, so cards accumulate exactly like the front-end
	// append. Totals are read from the SAME query object so the value is actually
	// resolved (getEntityRecordsTotalPages only knows totals for a fetched query).
	// getEntityRecords returns null while resolving, then an array.
	const perPageNum = Math.min( 24, Math.max( 1, Number( postsPerPage ) || 6 ) );
	const fetchPerPage = pagType === 'loadmore'
		? Math.min( 100, perPageNum * Math.max( 1, morePages ) )
		: perPageNum;
	const fetchPage = pagType === 'numbered' ? Math.max( 1, page ) : 1;
	const { posts, totalPages, totalItems } = useSelect(
		( select: ( store: string ) => any ) => {
			const core = select( 'core' );
			const query: Record< string, unknown > = {
				per_page: fetchPerPage,
				offset: Math.max( 0, Number( offset ) || 0 ),
				page: fetchPage,
				orderby: orderBy || 'date',
				order: order || 'desc',
				_embed: true,
			};

			// Apply the taxonomy filter the front end applies, so the preview doesn't
			// lie about the author's own constraint (the filter blocks tell visitors
			// they can only narrow WITHIN it — that has to be visible here too).
			// The REST query takes the taxonomy's rest_base (`categories`, `tags`),
			// not its slug.
			const termIds = String( terms || '' ).split( ',' ).map( ( id ) => parseInt( id.trim(), 10 ) ).filter( Boolean );
			if ( taxonomy && termIds.length ) {
				const taxes = core.getTaxonomies( { per_page: -1, type: postType || 'post' } ) || [];
				const restBase = ( taxes.filter( ( t: any ) => t.slug === taxonomy )[ 0 ] || {} ).rest_base;
				if ( restBase ) {
					query[ restBase ] = termIds;
				}
			}

			return {
				posts: core.getEntityRecords( 'postType', postType || 'post', query ) as any[] | null,
				// With load-more's growing per_page, totalPages > 1 exactly means more
				// items remain beyond what's fetched — used to keep the button visible.
				totalPages: ( core.getEntityRecordsTotalPages?.( 'postType', postType || 'post', query ) as number | null ) || 1,
				// The REAL total, so the previewed count line says what visitors will see.
				totalItems: ( core.getEntityRecordsTotalItems?.( 'postType', postType || 'post', query ) as number | null ) ?? null,
			};
		},
		[ postType, fetchPerPage, fetchPage, offset, orderBy, order, taxonomy, terms ]
	);
	const isResolving = posts === null;

	const wrapperStyle = buildWrapperStyle( attributes, device, isBoxed );
	const gridStyle = buildGridStyle( attributes, device );
	const cardStyle = buildCardStyle( attributes, device );

	const statusStyle: CssProps = {};
	applyTypography( statusStyle, effective( attributes.resultCountTypography, device ) );
	if ( attributes.resultCountColor?.light ) statusStyle.color = attributes.resultCountColor.light;
	if ( attributes.resultCountAlign ) statusStyle.textAlign = attributes.resultCountAlign;

	const titleStyle: CssProps = {};
	applyTypography( titleStyle, effective( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) titleStyle.color = attributes.titleColor.light;

	const metaStyle: CssProps = {};
	applyTypography( metaStyle, effective( attributes.metaTypography, device ) );
	if ( attributes.metaColor?.light ) metaStyle.color = attributes.metaColor.light;

	const excerptStyle: CssProps = {};
	applyTypography( excerptStyle, effective( attributes.excerptTypography, device ) );
	if ( attributes.excerptColor?.light ) excerptStyle.color = attributes.excerptColor.light;

	const buttonStyle: CssProps = {};
	applyTypography( buttonStyle, effective( attributes.buttonTypography, device ) );
	if ( attributes.buttonTextColor?.light ) buttonStyle.color = attributes.buttonTextColor.light;
	if ( attributes.buttonBackground?.light ) buttonStyle.background = attributes.buttonBackground.light;
	const buttonPad = spacingShorthand( attributes.buttonPadding );
	if ( buttonPad ) buttonStyle.padding = buttonPad;
	if ( attributes.buttonRadius?.value ) buttonStyle.borderRadius = withUnit( attributes.buttonRadius.value, attributes.buttonRadius.unit || 'px' );
	if ( attributes.buttonAlign && CONTENT_ALIGN_TO_FLEX[ attributes.buttonAlign ] ) buttonStyle.alignSelf = CONTENT_ALIGN_TO_FLEX[ attributes.buttonAlign ];
	if ( attributes.buttonWidth === 'full' ) {
		buttonStyle.display = 'flex';
		buttonStyle.width = '100%';
		buttonStyle.justifyContent = 'center';
	}

	const bodyStyle: CssProps = {};
	const contentPad = spacingShorthand( effective( attributes.contentPadding, device ) );
	if ( contentPad ) bodyStyle.padding = contentPad;
	const cGap = effective( attributes.contentGap, device );
	if ( cGap.value ) bodyStyle.gap = withUnit( cGap.value, cGap.unit || 'px' );

	const imageStyle: CssProps = {};
	if ( imageRatio ) {
		imageStyle.aspectRatio = imageRatio.replace( '/', ' / ' );
		imageStyle.objectFit = 'cover';
	}

	// Read-more button :hover — inline styles can't express `:hover`, so mirror the
	// generator's hover rules (Post_Grid_CSS::add_button) in a scoped <style> using
	// the light values so the editor preview matches the front end.
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-post-grid-${ blockId } .flexa-post-grid__readmore:hover`, prop: 'color', value: attributes.buttonTextColorHover?.light },
			{ selector: `.flexa-post-grid-${ blockId } .flexa-post-grid__readmore:hover`, prop: 'background', value: attributes.buttonBackgroundHover?.light },
		] )
		: '';

	const TitleTag: any = titleTag || 'h3';

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'section';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-post-grid',
			`flexa-post-grid--${ containerType || 'boxed' }`,
			blockId && `flexa-post-grid-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of a live query.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="counter" />
			</div>
		);
	}

	const list = Array.isArray( posts ) ? posts : [];

	// One real post card.
	const renderCard = ( post: ( typeof list )[ number ] ): JSX.Element => {
		const title = stripHtml( post?.title?.rendered || __( '(no title)', 'flexa-block' ) );
		const excerpt = trimWords( stripHtml( post?.excerpt?.rendered || '' ), Number( excerptLength ) || 20 );
		const dateStr = post?.date ? new Date( post.date ).toLocaleDateString() : '';

		// Meta pieces read from the embedded author + terms so the preview matches
		// the enabled toggles (author + avatar, date, taxonomy). Comments count is
		// not in the REST payload, so it only appears on the front end.
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const author = ( post as any )?._embedded?.author?.[ 0 ];
		const authorName = author?.name || '';
		const authorAvatar = author?.avatar_urls?.[ '48' ] || author?.avatar_urls?.[ '96' ] || '';
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const terms = ( ( ( post as any )?._embedded?.[ 'wp:term' ] as any[] ) || [] ).flat().map( ( t: any ) => t?.name ).filter( Boolean );
		const size = typeof avatarSize === 'number' ? avatarSize : 24;

		// Featured image from the embedded media, so the editor shows the real
		// thumbnail (falling back to the neutral placeholder when a post has none).
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const media = ( post as any )?._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
		const mediaSizes = media?.media_details?.sizes || {};
		const imgUrl = mediaSizes?.large?.source_url || mediaSizes?.medium_large?.source_url || mediaSizes?.medium?.source_url || media?.source_url || '';

		const metaParts: JSX.Element[] = [];
		if ( showAuthor !== false && authorName ) {
			metaParts.push(
				<span key="author" className="flexa-post-grid__author">
					{ showAvatar && authorAvatar && (
						<img className="flexa-post-grid__avatar" src={ authorAvatar } alt="" width={ size } height={ size } style={ { width: size, height: size } } />
					) }
					{ authorName }
				</span>
			);
		}
		if ( showDate !== false && dateStr ) {
			metaParts.push( <span key="date" className="flexa-post-grid__date">{ dateStr }</span> );
		}
		if ( showTaxonomy && terms.length > 0 ) {
			metaParts.push( <span key="tax" className="flexa-post-grid__taxonomy">{ terms.join( ', ' ) }</span> );
		}

		return (
			<article key={ post.id } className="flexa-post-grid__item" style={ cardStyle }>
				{ showImage !== false && (
					<div className="flexa-post-grid__image-wrap">
						{ imgUrl
							? <img className="flexa-post-grid__image" src={ imgUrl } alt="" style={ imageStyle } />
							: <ImagePlaceholder style={ imageStyle } /> }
					</div>
				) }
				<div className="flexa-post-grid__body" style={ bodyStyle }>
					{ showMeta !== false && metaParts.length > 0 && (
						<div className="flexa-post-grid__meta" style={ metaStyle }>
							{ metaParts.map( ( part, i ) => (
								<Fragment key={ i }>
									{ i > 0 && <span className="flexa-post-grid__meta-sep" aria-hidden="true">·</span> }
									{ part }
								</Fragment>
							) ) }
						</div>
					) }
					{ showTitle !== false && (
						<TitleTag className="flexa-post-grid__title" style={ titleStyle }>
							{ title }
						</TitleTag>
					) }
					{ showExcerpt !== false && excerpt && (
						<div className="flexa-post-grid__excerpt" style={ excerptStyle }>
							{ excerpt }
						</div>
					) }
					{ showReadMore !== false && (
						<span className="flexa-post-grid__readmore wp-element-button" style={ buttonStyle }>
							{ readMoreText || __( 'Read more', 'flexa-block' ) }
						</span>
					) }
				</div>
			</article>
		);
	};

	// A muted skeleton card, shown while the query resolves or when the site has
	// no matching posts yet — same markup/classes so the layout is visible.
	const renderPlaceholderCard = ( key: number ): JSX.Element => (
		<article key={ `ph-${ key }` } className="flexa-post-grid__item flexa-post-grid__item--skeleton" style={ cardStyle } aria-hidden="true">
			{ showImage !== false && (
				<div className="flexa-post-grid__image-wrap">
					<div className="flexa-post-grid__image flexa-post-grid__image--placeholder" style={ imageStyle } />
				</div>
			) }
			<div className="flexa-post-grid__body" style={ bodyStyle }>
				{ showMeta !== false && ( showAuthor !== false || showDate !== false ) && (
					<div className="flexa-post-grid__meta" style={ metaStyle }>
						{ showAuthor !== false && showAvatar && (
							<span className="flexa-skeleton-line" style={ { width: `${ typeof avatarSize === 'number' ? avatarSize : 24 }px`, height: `${ typeof avatarSize === 'number' ? avatarSize : 24 }px`, borderRadius: '50%', flexShrink: 0 } } />
						) }
						<span className="flexa-skeleton-line flexa-skeleton-line--meta" />
					</div>
				) }
				{ showTitle !== false && (
					<TitleTag className="flexa-post-grid__title" style={ titleStyle }>
						<span className="flexa-skeleton-line flexa-skeleton-line--title" />
					</TitleTag>
				) }
				{ showExcerpt !== false && (
					<div className="flexa-post-grid__excerpt" style={ excerptStyle }>
						<span className="flexa-skeleton-line" />
						<span className="flexa-skeleton-line" />
						<span className="flexa-skeleton-line flexa-skeleton-line--short" />
					</div>
				) }
				{ showReadMore !== false && (
					<span className="flexa-post-grid__readmore wp-element-button" style={ buttonStyle }>
						{ readMoreText || __( 'Read more', 'flexa-block' ) }
					</span>
				) }
			</div>
		</article>
	);

	const cols = Math.max( 1, parseInt( String( effective( attributes.columns, device ).value || '1' ), 10 ) || 1 );
	const placeholderCount = Math.min( 4, Math.max( 3, cols ) );

	const grid = (
		<div className="flexa-post-grid__grid" style={ gridStyle }>
			{ list.length
				? list.map( renderCard )
				: Array.from( { length: placeholderCount }, ( _v, i ) => renderPlaceholderCard( i ) ) }
		</div>
	);

	// Pagination preview (numbered nav / load-more button), styled + functional.
	// Load-more reveals one more page (perPageNum records) per click, matching the
	// front-end append; the button shows while unrevealed pages remain.
	const paginationNav = pagType === 'numbered'
		? <PaginationNav attributes={ attributes } type="numbered" totalPages={ totalPages } page={ Math.max( 1, page ) } onPage={ setPage } />
		: pagType === 'loadmore'
		? <PaginationNav attributes={ attributes } type="loadmore" hasMore={ totalPages > 1 } onLoadMore={ () => setMorePages( ( m ) => m + 1 ) } />
		: null;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-post-grid-inspector">
					<InspectorTabs
						layout={
							<>
								<PostGridQueryPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostGridLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostGridElementsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<PostGridColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostGridTypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ !! showResultCount && (
									<PostGridResultCountPanel attributes={ attributes } setAttributes={ setAttributes } />
								) }
								{ showReadMore !== false && (
									<PostGridButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
								) }
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

			<Tag { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<div className="flexa-post-grid__inner" style={ wrapperStyle }>
					{ /* Mirrors Post_Query::render_status. Only the SHOWN case is previewed —
					     the screen-reader-only one has nothing to look at. */ }
					{ !! showResultCount && (
						<p className="flexa-post-grid__status" style={ statusStyle }>
							{ ( resultCountText?.trim() || __( '%s posts found', 'flexa-block' ) ).replace(
								'%s',
								String( totalItems ?? list.length )
							) }
						</p>
					) }
					{ isResolving && ! list.length && (
						<p className="flexa-post-grid__loading"><Spinner /> { __( 'Loading posts…', 'flexa-block' ) }</p>
					) }
					{ grid }
					{ paginationNav }
				</div>
			</Tag>
		</>
	);
}
