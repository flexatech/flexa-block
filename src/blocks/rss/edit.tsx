/**
 * RSS block — editor component.
 *
 * Dynamic block: the real feed is fetched and rendered server-side by render.php
 * (WordPress `fetch_feed()`, cached). The editor previews the SAME feed live by
 * calling the `flexa-block/v1/rss-preview` REST route (Rss_Feed), so the actual
 * entries appear as you configure the block; the card layout, gaps, typography
 * and colours are drawn with inline styles that mirror the PHP CSS generator
 * (Rss_CSS). Nothing is coloured by default so the cards inherit the theme.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

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
	useBlockId,
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
	RssSourcePanel,
	RssLayoutPanel,
	RssElementsPanel,
	RssTypographyPanel,
	RssColorsPanel,
	RssButtonPanel,
} from './panels';
import type { DeviceKey, EditProps, RssAttributes } from '../../types';

/** One normalised feed entry from the preview endpoint. */
interface FeedItem {
	title: string;
	link: string;
	date: string;
	author: string;
	source: string;
	image: string;
	excerpt: string;
}

/** Trim a plain-text excerpt to a word count. */
const trimWords = ( text: string, words: number ): string => {
	if ( ! words || words < 1 ) return '';
	const parts = String( text || '' ).split( /\s+/ ).filter( Boolean );
	return parts.length > words ? parts.slice( 0, words ).join( ' ' ) + '…' : text;
};

/** Styled-wrapper preview (width, padding, background). Border + shadow live on each card. */
const buildWrapperStyle = ( attributes: RssAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
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

/** Grid/list container preview (columns + gaps for grid, stacked flex for list). */
const buildGridStyle = ( attributes: RssAttributes, device: DeviceKey, isGrid: boolean ): CssProps => {
	const rg = effective( attributes.rowGap, device );
	if ( ! isGrid ) {
		const s: CssProps = { display: 'flex', flexDirection: 'column' };
		if ( rg.value ) s.rowGap = withUnit( rg.value, rg.unit || 'px' );
		return s;
	}
	const s: CssProps = { display: 'grid' };
	const cols = effective( attributes.columns, device );
	const count = Math.max( 1, parseInt( String( cols.value || '1' ), 10 ) || 1 );
	s.gridTemplateColumns = `repeat(${ count }, minmax(0, 1fr))`;
	if ( rg.value ) s.rowGap = withUnit( rg.value, rg.unit || 'px' );
	const cg = effective( attributes.columnGap, device );
	if ( cg.value ) s.columnGap = withUnit( cg.value, cg.unit || 'px' );
	return s;
};

/** Card preview (background + border + shadow + alignment + equal height + direction). */
const buildCardStyle = ( attributes: RssAttributes, device: DeviceKey, isGrid: boolean ): CssProps => {
	const s: CssProps = { display: 'flex', flexDirection: isGrid ? 'column' : 'row' };
	if ( attributes.cardBackground?.light ) s.background = attributes.cardBackground.light;
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
		if ( isGrid && CONTENT_ALIGN_TO_FLEX[ a ] ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ a ];
	}
	if ( isGrid && attributes.equalHeight !== false ) s.height = '100%';
	return s;
};

/**
 * RSS edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< RssAttributes > ): JSX.Element {
	const {
		feedLayout, feedUrl, itemsToShow, sortNewestFirst, cacheTime,
		showImage, imageRatio, showTitle, titleTag, showMeta, showDate, showAuthor, showSource,
		showExcerpt, excerptLength, showReadMore, readMoreText,
		paginationType, perPage,
		containerType, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const isGrid = ( feedLayout || 'grid' ) === 'grid';

	useBlockId( clientId, blockId, setAttributes );

	// Live preview: fetch the real feed through the editor-only REST proxy (an
	// external feed can't be fetched directly from the browser). Debounced so
	// typing the URL doesn't spam the endpoint.
	const [ items, setItems ] = useState< FeedItem[] | null >( null );
	const [ total, setTotal ] = useState( 0 );
	const [ status, setStatus ] = useState< 'idle' | 'loading' | 'error' >( 'idle' );
	const [ page, setPage ] = useState( 1 );
	const [ loadRows, setLoadRows ] = useState( 2 );
	const hasUrl = !! feedUrl && /^https?:\/\//i.test( feedUrl );

	useEffect( () => {
		if ( ! hasUrl ) {
			setItems( null );
			setTotal( 0 );
			setStatus( 'idle' );
			return;
		}
		let cancelled = false;
		setStatus( 'loading' );
		const timer = setTimeout( () => {
			apiFetch( {
				path: addQueryArgs( '/flexa-block/v1/rss-preview', {
					url: feedUrl,
					items: Math.min( 100, Math.max( 1, Number( itemsToShow ) || 5 ) ),
					sort: sortNewestFirst === false ? 'oldest' : 'newest',
					cache: Number( cacheTime ) || 60,
				} ),
			} )
				.then( ( res: unknown ) => {
					if ( cancelled ) return;
					const data = res as { items?: FeedItem[]; total?: number };
					setItems( Array.isArray( data?.items ) ? data.items : [] );
					setTotal( Number( data?.total ) || 0 );
					setPage( 1 );
					setLoadRows( 2 );
					setStatus( 'idle' );
				} )
				.catch( () => {
					if ( cancelled ) return;
					setItems( [] );
					setStatus( 'error' );
				} );
		}, 500 );
		return () => {
			cancelled = true;
			clearTimeout( timer );
		};
	}, [ hasUrl, feedUrl, itemsToShow, sortNewestFirst, cacheTime ] );

	const wrapperStyle = buildWrapperStyle( attributes, device, isBoxed );
	const gridStyle = buildGridStyle( attributes, device, isGrid );
	const cardStyle = buildCardStyle( attributes, device, isGrid );

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
	// generator's hover rules (Rss_CSS::add_button) in a scoped <style> using the
	// light values so the editor preview matches the front end.
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-rss-${ blockId } .flexa-rss__readmore:hover`, prop: 'color', value: attributes.buttonTextColorHover?.light },
			{ selector: `.flexa-rss-${ blockId } .flexa-rss__readmore:hover`, prop: 'background', value: attributes.buttonBackgroundHover?.light },
		] )
		: '';

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const TitleTag: any = titleTag || 'h3';
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'section';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-rss',
			`flexa-rss--${ feedLayout || 'grid' }`,
			`flexa-rss--${ containerType || 'boxed' }`,
			blockId && `flexa-rss-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
	} );

	// Inserter hover-preview → faint skeleton mock-up.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="counter" />
			</div>
		);
	}

	const renderCard = ( item: FeedItem, i: number ): JSX.Element => {
		const excerpt = trimWords( item.excerpt || '', Number( excerptLength ) || 20 );
		const metaParts: JSX.Element[] = [];
		if ( showDate !== false && item.date ) {
			metaParts.push( <span key="date" className="flexa-rss__date">{ item.date }</span> );
		}
		if ( showAuthor !== false && item.author ) {
			metaParts.push( <span key="author" className="flexa-rss__author">{ item.author }</span> );
		}
		if ( showSource && item.source ) {
			metaParts.push( <span key="source" className="flexa-rss__source">{ item.source }</span> );
		}

		return (
			<article key={ i } className="flexa-rss__item" style={ cardStyle }>
				{ showImage !== false && ( item.image ? (
					<div className="flexa-rss__image-wrap">
						<img className="flexa-rss__image" src={ item.image } alt="" style={ imageStyle } />
					</div>
				) : (
					<div className="flexa-rss__image-wrap">
						<ImagePlaceholder style={ imageStyle } />
					</div>
				) ) }
				<div className="flexa-rss__body" style={ bodyStyle }>
					{ showMeta !== false && metaParts.length > 0 && (
						<div className="flexa-rss__meta" style={ metaStyle }>
							{ metaParts.map( ( part, j ) => (
								<Fragment key={ j }>
									{ j > 0 && <span className="flexa-rss__meta-sep" aria-hidden="true">·</span> }
									{ part }
								</Fragment>
							) ) }
						</div>
					) }
					{ showTitle !== false && item.title && (
						<TitleTag className="flexa-rss__title" style={ titleStyle }>
							<span className="flexa-rss__title-link">{ item.title }</span>
						</TitleTag>
					) }
					{ showExcerpt !== false && excerpt && (
						<div className="flexa-rss__excerpt" style={ excerptStyle }>{ excerpt }</div>
					) }
					{ showReadMore !== false && (
						<span className="flexa-rss__readmore wp-element-button" style={ buttonStyle }>
							{ readMoreText || __( 'Read more', 'flexa-block' ) }
						</span>
					) }
				</div>
			</article>
		);
	};

	const list = Array.isArray( items ) ? items : [];

	let body: JSX.Element;
	if ( ! hasUrl ) {
		body = <p className="flexa-rss__empty">{ __( 'Add a feed URL in the Feed panel to preview entries.', 'flexa-block' ) }</p>;
	} else if ( status === 'loading' && ! list.length ) {
		body = <p className="flexa-rss__loading"><Spinner /> { __( 'Loading feed…', 'flexa-block' ) }</p>;
	} else if ( status === 'error' ) {
		body = <p className="flexa-rss__empty">{ __( 'Could not load this feed. Check the URL.', 'flexa-block' ) }</p>;
	} else if ( ! list.length ) {
		body = <p className="flexa-rss__empty">{ __( 'No entries found in this feed.', 'flexa-block' ) }</p>;
	} else {
		// Paginate the preview the same way the front end does, so the per-page
		// count and the styled nav match what ships.
		const pagType = paginationType || 'none';
		let shown = list;
		let nav: JSX.Element | null = null;

		if ( pagType === 'numbered' ) {
			const per = Math.max( 1, Number( perPage ) || 6 );
			const totalPages = Math.max( 1, Math.ceil( list.length / per ) );
			const cur = Math.min( Math.max( 1, page ), totalPages );
			shown = list.slice( ( cur - 1 ) * per, cur * per );
			nav = <PaginationNav attributes={ attributes } type="numbered" totalPages={ totalPages } page={ cur } onPage={ setPage } />;
		} else if ( pagType === 'loadmore' ) {
			const cols = isGrid ? Math.max( 1, parseInt( String( effective( attributes.columns, device ).value || '1' ), 10 ) || 1 ) : 1;
			const visible = Math.min( list.length, Math.max( 1, loadRows ) * cols );
			shown = list.slice( 0, visible );
			nav = <PaginationNav attributes={ attributes } type="loadmore" hasMore={ visible < list.length } onLoadMore={ () => setLoadRows( ( r ) => r + 1 ) } />;
		}

		body = (
			<>
				<div className="flexa-rss__grid" style={ gridStyle }>{ shown.map( renderCard ) }</div>
				{ nav }
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-rss-inspector">
					<InspectorTabs
						layout={
							<>
								<RssSourcePanel attributes={ attributes } setAttributes={ setAttributes } maxItems={ total } />
								<RssLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<RssElementsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<RssColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<RssTypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showReadMore !== false && (
									<RssButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
								) }
								<PaginationPanel
									attributes={ attributes }
									setAttributes={ setAttributes }
									perPage={ perPage }
									onPerPage={ ( v ) => setAttributes( { perPage: v } ) }
									typeHelp={ __( 'Pages through the items already fetched (Items to show).', 'flexa-block' ) }
								/>
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
				<div className="flexa-rss__inner" style={ wrapperStyle }>
					{ body }
				</div>
			</Tag>
		</>
	);
}
