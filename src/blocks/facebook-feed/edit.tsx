/**
 * Facebook Feed block — editor component.
 *
 * Dynamic block: the real feed is fetched and rendered server-side by render.php
 * (Facebook Graph API via the cached Facebook_Feed helper). The editor previews
 * the SAME feed live by calling the `flexa-block/v1/facebook-feed-preview` REST
 * route (the Page access token must stay server-side, so the browser can't call
 * the Graph API directly), so the actual posts appear as you configure the block;
 * the card layout, gaps, typography and colours are drawn with inline styles that
 * mirror the PHP CSS generator (Facebook_Feed_CSS). Nothing is coloured by default
 * so the cards inherit the theme.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, Spinner } from '@wordpress/components';
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
	MediaBadge,
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
	demoImage,
	ClockIcon,
	ThumbsUpIcon,
	CommentIcon,
	ShareIcon,
	type CssProps,
} from '@utils';
import {
	FbSourcePanel,
	FbLayoutPanel,
	FbContentPanel,
	FbTypographyPanel,
	FbColorsPanel,
} from './panels';
import type { DeviceKey, EditProps, FacebookFeedAttributes } from '../../types';

/** One normalised post from the preview endpoint. */
interface FeedPost {
	id: string;
	message: string;
	permalink: string;
	date: string;
	image: string;
	type: string;
	likes: number;
	comments: number;
	shares: number;
	pageName: string;
	pageAvatar: string;
}

/** Whether the Facebook token is configured (localised, never the token itself). */
const isConnected = (): boolean =>
	!! ( window as unknown as { flexaBlockFeedTokens?: { configured?: Record< string, boolean > } } )
		.flexaBlockFeedTokens?.configured?.facebook;

/**
 * Demo posts shown in the editor ONLY when no token is connected, so the layout
 * and styling can be previewed. This sample is never rendered on the front end
 * (render.php shows real posts or nothing).
 */
const SAMPLE_POSTS: FeedPost[] = [
	{ id: 'demo-1', message: __( 'This is a demo Facebook post — connect a Page access token to show your real feed. It appears only in the editor.', 'flexa-block' ), permalink: '#', date: 'Jul 12, 2026 2:45 PM', image: demoImage( 'flexa-fb-1', 640, 420 ), type: 'image', likes: 128, comments: 24, shares: 8, pageName: __( 'Your Page', 'flexa-block' ), pageAvatar: demoImage( 'flexa-fb-a', 80, 80 ) },
	{ id: 'demo-2', message: __( 'Behind the scenes from today’s shoot 📸 (sample video)', 'flexa-block' ), permalink: '#', date: 'Jul 11, 2026 6:10 PM', image: demoImage( 'flexa-fb-2', 640, 420 ), type: 'video', likes: 342, comments: 51, shares: 19, pageName: __( 'Your Page', 'flexa-block' ), pageAvatar: demoImage( 'flexa-fb-a', 80, 80 ) },
	{ id: 'demo-3', message: __( 'Weekend sale is on! (sample album). Demo data only — it will not appear on your published site.', 'flexa-block' ), permalink: '#', date: 'Jul 10, 2026 9:30 AM', image: demoImage( 'flexa-fb-3', 640, 420 ), type: 'album', likes: 87, comments: 12, shares: 5, pageName: __( 'Your Page', 'flexa-block' ), pageAvatar: demoImage( 'flexa-fb-a', 80, 80 ) },
	{ id: 'demo-4', message: __( 'Thanks for 10k followers! 🎉', 'flexa-block' ), permalink: '#', date: 'Jul 8, 2026 11:20 AM', image: demoImage( 'flexa-fb-4', 640, 420 ), type: 'image', likes: 512, comments: 96, shares: 40, pageName: __( 'Your Page', 'flexa-block' ), pageAvatar: demoImage( 'flexa-fb-a', 80, 80 ) },
];

/** Trim a plain-text message to a word count. */
const trimWords = ( text: string, words: number ): string => {
	if ( ! words || words < 1 ) return String( text || '' );
	const parts = String( text || '' ).split( /\s+/ ).filter( Boolean );
	return parts.length > words ? parts.slice( 0, words ).join( ' ' ) + '…' : text;
};

/** Styled-wrapper preview (width, padding, background). Border + shadow live on each card. */
const buildWrapperStyle = ( attributes: FacebookFeedAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
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

/** Grid container preview for the four layouts. */
const buildGridStyle = ( attributes: FacebookFeedAttributes, device: DeviceKey, layout: string ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.itemGap, device );
	const gapValue = gap.value ? withUnit( gap.value, gap.unit || 'px' ) : '';
	const cols = effective( attributes.columns, device );
	const count = Math.max( 1, parseInt( String( cols.value || '1' ), 10 ) || 1 );

	if ( layout === 'list' ) {
		s.display = 'flex';
		s.flexDirection = 'column';
		if ( gapValue ) s.gap = gapValue;
	} else if ( layout === 'masonry' ) {
		s.columnCount = String( count );
		if ( gapValue ) s.columnGap = gapValue;
	} else if ( layout === 'carousel' ) {
		s.display = 'flex';
		s.overflowX = 'auto';
		if ( gapValue ) s.gap = gapValue;
	} else {
		s.display = 'grid';
		s.gridTemplateColumns = `repeat(${ count }, minmax(0, 1fr))`;
		if ( gapValue ) s.gap = gapValue;
	}
	return s;
};

/** Card preview (background + border + shadow + content padding/gap). */
const buildCardStyle = ( attributes: FacebookFeedAttributes, device: DeviceKey, layout: string ): CssProps => {
	const s: CssProps = {};
	if ( attributes.cardBackground?.light ) s.background = attributes.cardBackground.light;
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	const contentPad = spacingShorthand( effective( attributes.contentPadding, device ) );
	if ( contentPad ) s.padding = contentPad;
	const contentGap = effective( attributes.contentGap, device );
	if ( contentGap.value ) s.gap = withUnit( contentGap.value, contentGap.unit || 'px' );
	if ( layout === 'masonry' ) s.breakInside = 'avoid';
	if ( layout === 'carousel' ) s.flex = '0 0 auto';
	return s;
};

/**
 * Facebook Feed edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< FacebookFeedAttributes > ): JSX.Element {
	const {
		feedLayout, pageId, numberOfPosts, sortNewestFirst, cacheTime,
		showAvatar, showPageName, showTimestamp, showImage, showMessage, messageLimit,
		showReactions, showComments, showShares, enableLink,
		paginationType, perPage,
		containerType, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const layout = feedLayout || 'grid';
	const isGrid = layout === 'grid' || layout === 'masonry';

	useBlockId( clientId, blockId, setAttributes );

	// Live preview: the real feed is fetched through the editor-only REST proxy,
	// which reads the token from the admin-only server store (the token never
	// reaches the browser). `connected` tracks whether a token is configured; it
	// updates live when an admin saves one (flexa:feed-token event).
	const [ items, setItems ] = useState< FeedPost[] | null >( null );
	const [ status, setStatus ] = useState< 'idle' | 'loading' | 'error' >( 'idle' );
	const [ page, setPage ] = useState( 1 );
	const [ loadRows, setLoadRows ] = useState( 2 );
	const [ connected, setConnected ] = useState< boolean >( isConnected() );

	useEffect( () => {
		const onToken = ( e: Event ): void => {
			const detail = ( e as CustomEvent ).detail as { service?: string; configured?: boolean } | undefined;
			if ( detail?.service === 'facebook' ) {
				setConnected( !! detail.configured );
			}
		};
		document.addEventListener( 'flexa:feed-token', onToken );
		return () => document.removeEventListener( 'flexa:feed-token', onToken );
	}, [] );

	useEffect( () => {
		if ( ! connected ) {
			setItems( null );
			setStatus( 'idle' );
			return;
		}
		let cancelled = false;
		setStatus( 'loading' );
		const timer = setTimeout( () => {
			apiFetch( {
				path: addQueryArgs( '/flexa-block/v1/facebook-feed-preview', {
					pageId: pageId || '',
					count: Math.min( 50, Math.max( 1, Number( numberOfPosts ) || 6 ) ),
					sort: sortNewestFirst === false ? 'oldest' : 'newest',
					cache: Number( cacheTime ) || 30,
				} ),
			} )
				.then( ( res: unknown ) => {
					if ( cancelled ) return;
					const data = res as { items?: FeedPost[] };
					setItems( Array.isArray( data?.items ) ? data.items : [] );
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
	}, [ connected, pageId, numberOfPosts, sortNewestFirst, cacheTime ] );

	const wrapperStyle = buildWrapperStyle( attributes, device, isBoxed );
	const gridStyle = buildGridStyle( attributes, device, layout );
	const cardStyle = buildCardStyle( attributes, device, layout );

	const headerStyle: CssProps = {};
	applyTypography( headerStyle, effective( attributes.headerTypography, device ) );
	if ( attributes.headerColor?.light ) headerStyle.color = attributes.headerColor.light;

	const messageStyle: CssProps = {};
	applyTypography( messageStyle, effective( attributes.messageTypography, device ) );
	if ( attributes.messageColor?.light ) messageStyle.color = attributes.messageColor.light;

	const metaStyle: CssProps = {};
	applyTypography( metaStyle, effective( attributes.metaTypography, device ) );
	if ( attributes.metaColor?.light ) metaStyle.color = attributes.metaColor.light;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'section';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-facebook-feed',
			`flexa-facebook-feed--${ layout }`,
			`flexa-facebook-feed--${ containerType || 'boxed' }`,
			blockId && `flexa-facebook-feed-${ blockId }`,
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

	const renderCard = ( post: FeedPost, i: number ): JSX.Element => {
		const message = showMessage !== false ? trimWords( post.message || '', Number( messageLimit ) || 0 ) : '';
		const actions: JSX.Element[] = [];
		if ( showReactions !== false ) {
			actions.push( <span key="reactions" className="flexa-facebook-feed__reactions">{ ThumbsUpIcon }{ post.likes || 0 }</span> );
		}
		if ( showComments ) {
			actions.push( <span key="comments" className="flexa-facebook-feed__comments">{ CommentIcon }{ post.comments || 0 }</span> );
		}
		if ( showShares !== false ) {
			actions.push( <span key="shares" className="flexa-facebook-feed__shares">{ ShareIcon }{ post.shares || 0 }</span> );
		}

		const avatarEl = showAvatar !== false && post.pageAvatar
			? <img className="flexa-facebook-feed__avatar" src={ post.pageAvatar } alt="" />
			: null;
		const nameEl = showPageName !== false
			? <span className="flexa-facebook-feed__page-name" style={ headerStyle }>{ post.pageName || __( 'Facebook Page', 'flexa-block' ) }</span>
			: null;
		const timestampEl = showTimestamp !== false && post.date
			? <time className="flexa-facebook-feed__timestamp" style={ metaStyle }>{ ClockIcon }{ post.date }</time>
			: null;

		return (
			<article key={ post.id || i } className="flexa-facebook-feed__item" style={ cardStyle }>
				{ ( avatarEl || nameEl || timestampEl ) && (
					<div className="flexa-facebook-feed__header">
						{ avatarEl }
						{ ( nameEl || timestampEl ) && (
							<div className="flexa-facebook-feed__header-text">
								{ nameEl }
								{ timestampEl }
							</div>
						) }
					</div>
				) }
				{ showImage !== false && ( post.image ? (
					<div className="flexa-facebook-feed__image-wrap">
						<img className="flexa-facebook-feed__image" src={ post.image } alt="" />
						<MediaBadge type={ post.type } className="flexa-facebook-feed__media-badge" />
					</div>
				) : (
					<div className="flexa-facebook-feed__image-wrap">
						<ImagePlaceholder />
					</div>
				) ) }
				{ showMessage !== false && message && (
					<div className="flexa-facebook-feed__message" style={ messageStyle }>{ message }</div>
				) }
				{ actions.length > 0 && (
					<div className="flexa-facebook-feed__actions" style={ metaStyle }>
						{ actions.map( ( part, j ) => (
							<Fragment key={ j }>{ part }</Fragment>
						) ) }
					</div>
				) }
			</article>
		);
	};

	const list = Array.isArray( items ) ? items : [];
	const usingDemo = ! connected;

	let body: JSX.Element;
	if ( connected && status === 'loading' && ! list.length ) {
		body = <p className="flexa-facebook-feed__loading"><Spinner /> { __( 'Loading posts…', 'flexa-block' ) }</p>;
	} else if ( connected && status === 'error' ) {
		body = <p className="flexa-facebook-feed__empty">{ __( 'Could not load posts. Check the token and Page ID.', 'flexa-block' ) }</p>;
	} else if ( connected && ! list.length ) {
		body = <p className="flexa-facebook-feed__empty">{ __( 'No posts could be loaded for this Page.', 'flexa-block' ) }</p>;
	} else {
		// Real feed when connected; otherwise the editor-only demo sample.
		const source = usingDemo ? SAMPLE_POSTS : list;
		let shown = source;
		let nav: JSX.Element | null = null;

		if ( ! usingDemo ) {
			// Paginate the preview the same way the front end does.
			const pagType = paginationType || 'none';
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
		}

		body = (
			<>
				{ usingDemo && (
					<Notice status="info" isDismissible={ false } className="flexa-facebook-feed__demo-note">
						{ __( 'Demo preview — connect a Page access token in the Source panel to show real posts. This sample data is editor-only and will not appear on your published site.', 'flexa-block' ) }
					</Notice>
				) }
				<div className="flexa-facebook-feed__grid" style={ gridStyle }>{ shown.map( renderCard ) }</div>
				{ nav }
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-facebook-feed-inspector">
					<InspectorTabs
						layout={
							<>
								<FbSourcePanel attributes={ attributes } setAttributes={ setAttributes } />
								<FbLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FbContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<FbColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<FbTypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PaginationPanel
									attributes={ attributes }
									setAttributes={ setAttributes }
									perPage={ perPage }
									onPerPage={ ( v ) => setAttributes( { perPage: v } ) }
									typeHelp={ __( 'Pages through the posts already fetched (Number of posts).', 'flexa-block' ) }
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
				<div className="flexa-facebook-feed__inner" style={ wrapperStyle }>
					{ body }
				</div>
			</Tag>
		</>
	);
}
