/**
 * Instagram Feed block — editor component.
 *
 * Dynamic block: the real media is fetched and rendered server-side by render.php
 * (Instagram Graph `me/media`, cached). The editor previews the SAME media live by
 * calling the `flexa-block/v1/instagram-feed-preview` REST route (Instagram_Feed),
 * so the actual images appear as you configure the block; the grid, gaps,
 * typography and colours are drawn with inline styles that mirror the PHP CSS
 * generator (Instagram_Feed_CSS). Nothing is coloured by default so the feed
 * inherits the theme.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
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
	ClockIcon,
	type CssProps,
} from '@utils';
import {
	IgSourcePanel,
	IgLayoutPanel,
	IgContentPanel,
	IgCaptionPanel,
} from './panels';
import type { DeviceKey, EditProps, InstagramFeedAttributes } from '../../types';

/** One normalised media entry from the preview endpoint. */
interface MediaItem {
	id: string;
	caption: string;
	permalink: string;
	date: string;
	image: string;
	type: string;
	username: string;
}

/** Whether the Instagram token is configured (localised, never the token itself). */
const isConnected = (): boolean =>
	!! ( window as unknown as { flexaBlockFeedTokens?: { configured?: Record< string, boolean > } } )
		.flexaBlockFeedTokens?.configured?.instagram;

/**
 * Demo media shown in the editor ONLY when no token is connected, so the layout
 * and styling can be previewed. This sample is never rendered on the front end
 * (render.php shows real media or nothing).
 */
const SAMPLE_MEDIA: MediaItem[] = [
	{ id: 'demo-1', caption: __( 'Demo photo — connect a token to show your real feed. Editor-only.', 'flexa-block' ), permalink: '#', date: 'Jul 12, 2026 2:45 PM', image: 'https://picsum.photos/seed/flexa-ig-1/600/600', type: 'image', username: 'your_handle' },
	{ id: 'demo-2', caption: __( 'Golden hour ✨ (sample video)', 'flexa-block' ), permalink: '#', date: 'Jul 11, 2026 6:10 PM', image: 'https://picsum.photos/seed/flexa-ig-2/600/600', type: 'video', username: 'your_handle' },
	{ id: 'demo-3', caption: __( 'New drop this week 🛍️ (sample album)', 'flexa-block' ), permalink: '#', date: 'Jul 10, 2026 9:30 AM', image: 'https://picsum.photos/seed/flexa-ig-3/600/600', type: 'album', username: 'your_handle' },
	{ id: 'demo-4', caption: __( 'Behind the scenes', 'flexa-block' ), permalink: '#', date: 'Jul 9, 2026 4:05 PM', image: 'https://picsum.photos/seed/flexa-ig-4/600/600', type: 'image', username: 'your_handle' },
	{ id: 'demo-5', caption: __( 'Weekend vibes', 'flexa-block' ), permalink: '#', date: 'Jul 8, 2026 11:20 AM', image: 'https://picsum.photos/seed/flexa-ig-5/600/600', type: 'image', username: 'your_handle' },
	{ id: 'demo-6', caption: __( 'Sample data only', 'flexa-block' ), permalink: '#', date: 'Jul 7, 2026 8:00 PM', image: 'https://picsum.photos/seed/flexa-ig-6/600/600', type: 'image', username: 'your_handle' },
];

/** Trim a plain-text caption to a word count. */
const trimWords = ( text: string, words: number ): string => {
	if ( ! words || words < 1 ) return '';
	const parts = String( text || '' ).split( /\s+/ ).filter( Boolean );
	return parts.length > words ? parts.slice( 0, words ).join( ' ' ) + '…' : text;
};

/** Styled-wrapper preview (width, padding, background). Border + shadow live on each item. */
const buildWrapperStyle = ( attributes: InstagramFeedAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
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

/** Grid container preview (columns + gap; carousel = horizontal scroll-snap row). */
const buildGridStyle = ( attributes: InstagramFeedAttributes, device: DeviceKey, isCarousel: boolean ): CssProps => {
	const gap = effective( attributes.itemGap, device );
	const cols = effective( attributes.columns, device );
	const count = Math.max( 1, parseInt( String( cols.value || '1' ), 10 ) || 1 );

	if ( isCarousel ) {
		const s: CssProps = { display: 'flex', flexWrap: 'nowrap', overflowX: 'auto' };
		if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
		return s;
	}
	const s: CssProps = { display: 'grid', gridTemplateColumns: `repeat(${ count }, minmax(0, 1fr))` };
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/**
 * Instagram Feed edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< InstagramFeedAttributes > ): JSX.Element {
	const {
		feedLayout, numberOfImages, sortNewestFirst, cacheTime,
		squareThumbnail, showCaption, captionLimit, showMeta, showProfile, enableLink,
		containerType, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const layout = feedLayout || 'grid';
	const isCarousel = layout === 'carousel';
	const isOverlay = layout === 'overlay';

	useBlockId( clientId, blockId, setAttributes );

	// Live preview: the real media is fetched through the editor-only REST proxy,
	// which reads the token from the admin-only server store (the token never
	// reaches the browser). `connected` tracks whether a token is configured; it
	// updates live when an admin saves one (flexa:feed-token event).
	const [ items, setItems ] = useState< MediaItem[] | null >( null );
	const [ status, setStatus ] = useState< 'idle' | 'loading' | 'error' >( 'idle' );
	const [ connected, setConnected ] = useState< boolean >( isConnected() );

	useEffect( () => {
		const onToken = ( e: Event ): void => {
			const detail = ( e as CustomEvent ).detail as { service?: string; configured?: boolean } | undefined;
			if ( detail?.service === 'instagram' ) {
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
				path: addQueryArgs( '/flexa-block/v1/instagram-feed-preview', {
					count: Math.min( 50, Math.max( 1, Number( numberOfImages ) || 8 ) ),
					sort: sortNewestFirst === false ? 'oldest' : 'newest',
					cache: Number( cacheTime ) || 30,
				} ),
			} )
				.then( ( res: unknown ) => {
					if ( cancelled ) return;
					const data = res as { items?: MediaItem[] };
					setItems( Array.isArray( data?.items ) ? data.items : [] );
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
	}, [ connected, numberOfImages, sortNewestFirst, cacheTime ] );

	const wrapperStyle = buildWrapperStyle( attributes, device, isBoxed );
	const gridStyle = buildGridStyle( attributes, device, isCarousel );

	const imageStyle: CssProps = {};
	if ( squareThumbnail !== false ) {
		imageStyle.aspectRatio = '1 / 1';
		imageStyle.objectFit = 'cover';
	}

	const itemStyle: CssProps = {};
	// Card background fills the item in every layout (matches the generator).
	if ( attributes.cardBackground?.light ) itemStyle.background = attributes.cardBackground.light;
	applyBorderPreview( itemStyle, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) itemStyle.boxShadow = shadow;

	// Content area (text) padding + gap — applied to the __body / __overlay wrapper.
	const bodyStyle: CssProps = {};
	const contentPad = spacingShorthand( effective( attributes.contentPadding, device ) );
	if ( contentPad ) bodyStyle.padding = contentPad;
	const contentGap = effective( attributes.contentGap, device );
	if ( contentGap.value ) bodyStyle.gap = withUnit( contentGap.value, contentGap.unit || 'px' );

	const overlayStyle: CssProps = { ...bodyStyle };
	if ( attributes.overlayColor?.light ) overlayStyle.background = attributes.overlayColor.light;

	const captionStyle: CssProps = {};
	applyTypography( captionStyle, effective( attributes.captionTypography, device ) );
	if ( attributes.captionColor?.light ) captionStyle.color = attributes.captionColor.light;

	const metaStyle: CssProps = {};
	applyTypography( metaStyle, effective( attributes.metaTypography, device ) );
	if ( attributes.metaColor?.light ) metaStyle.color = attributes.metaColor.light;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'section';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-instagram-feed',
			`flexa-instagram-feed--${ layout }`,
			`flexa-instagram-feed--${ containerType || 'boxed' }`,
			blockId && `flexa-instagram-feed-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
	} );

	// Inserter hover-preview → faint skeleton mock-up.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="grid" />
			</div>
		);
	}

	const renderItem = ( item: MediaItem, i: number ): JSX.Element => {
		const caption = trimWords( item.caption || '', Number( captionLimit ) || 0 );
		const captionEl = showCaption !== false && caption
			? <div className="flexa-instagram-feed__caption" style={ captionStyle }>{ caption }</div>
			: null;
		const metaEl = showMeta && item.date
			? <time className="flexa-instagram-feed__meta" style={ metaStyle }>{ ClockIcon }{ item.date }</time>
			: null;
		const profileEl = showProfile && item.username
			? <span className="flexa-instagram-feed__profile" style={ metaStyle }>@{ item.username }</span>
			: null;
		const bylineSep = profileEl && metaEl
			? <span className="flexa-instagram-feed__byline-sep" style={ metaStyle } aria-hidden="true">·</span>
			: null;
		const bylineEl = ( profileEl || metaEl )
			? <div className="flexa-instagram-feed__byline">{ profileEl }{ bylineSep }{ metaEl }</div>
			: null;

		const media = item.image
			? <img className="flexa-instagram-feed__image" src={ item.image } alt="" style={ imageStyle } />
			: <ImagePlaceholder className="flexa-instagram-feed__image" style={ imageStyle } />;

		const link = enableLink !== false
			? <span className="flexa-instagram-feed__link">{ media }</span>
			: media;

		return (
			<div key={ item.id || i } className="flexa-instagram-feed__item" style={ itemStyle }>
				{ link }
				<MediaBadge type={ item.type } className="flexa-instagram-feed__media-badge" />
				{ ( bylineEl || captionEl ) && ( isOverlay ? (
					<div className="flexa-instagram-feed__overlay" style={ overlayStyle }>
						{ bylineEl }
						{ captionEl }
					</div>
				) : (
					<div className="flexa-instagram-feed__body" style={ bodyStyle }>
						{ bylineEl }
						{ captionEl }
					</div>
				) ) }
			</div>
		);
	};

	const list = Array.isArray( items ) ? items : [];
	const usingDemo = ! connected;

	let body: JSX.Element;
	if ( connected && status === 'loading' && ! list.length ) {
		body = <p className="flexa-instagram-feed__loading"><Spinner /> { __( 'Loading media…', 'flexa-block' ) }</p>;
	} else if ( connected && status === 'error' ) {
		body = <p className="flexa-instagram-feed__empty">{ __( 'Could not load this feed. Check the access token.', 'flexa-block' ) }</p>;
	} else if ( connected && ! list.length ) {
		body = <p className="flexa-instagram-feed__empty">{ __( 'No media found for this account.', 'flexa-block' ) }</p>;
	} else {
		const source = usingDemo ? SAMPLE_MEDIA : list;
		body = (
			<>
				{ usingDemo && (
					<Notice status="info" isDismissible={ false } className="flexa-instagram-feed__demo-note">
						{ __( 'Demo preview — connect an access token in the Source panel to show real media. This sample data is editor-only and will not appear on your published site.', 'flexa-block' ) }
					</Notice>
				) }
				<div className="flexa-instagram-feed__grid" style={ gridStyle }>{ source.map( renderItem ) }</div>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-instagram-feed-inspector">
					<InspectorTabs
						layout={
							<>
								<IgSourcePanel attributes={ attributes } setAttributes={ setAttributes } />
								<IgLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<IgContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<IgCaptionPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-instagram-feed__inner" style={ wrapperStyle }>
					{ body }
				</div>
			</Tag>
		</>
	);
}
