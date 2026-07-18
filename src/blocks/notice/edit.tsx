/**
 * Notice block — editor component.
 *
 * Assembles the notice-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the notice (icon + title + message + optional
 * dismiss button) with inline styles that mirror the PHP CSS generator; nothing
 * is styled by default (beyond the subtle per-type accent from style.scss) so the
 * notice inherits the theme until the user picks a value. The title and message
 * are edited inline with RichText.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	type CssProps,
} from '@utils';
import {
	NoticeSettingsPanel,
	NoticeIconPanel,
	NoticeTitlePanel,
	NoticeMessagePanel,
} from './panels';
import type { ColorPair, DeviceKey, EditProps, NoticeAttributes, TypographyDevice } from '../../types';

/** Per-type default glyph shown when the user has not picked a custom icon.
 *  Mirrors the PHP defaults in render.php so the editor matches the front end. */
const DEFAULT_ICONS: Record< string, string > = {
	info: '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
	success: '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
	warning: '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 18h.01"/></svg>',
	danger: '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
	default: '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
};

/** The active device's content alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: NoticeAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return a[ device ] || a.desktop || '';
};

/** Wrapper preview: spacing, background, border and shadow (foundational). */
const buildWrapperStyle = ( attributes: NoticeAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	// Accent bar (left border) colour override.
	if ( attributes.accentColor?.light ) s.borderLeftColor = attributes.accentColor.light;

	return s;
};

/** Inner row preview: flex-direction from the icon position. */
const buildInnerStyle = ( attributes: NoticeAttributes ): CssProps => {
	return { flexDirection: ( attributes.iconPosition || 'left' ) === 'top' ? 'column' : 'row' };
};

/** Body column preview: content alignment. */
const buildBodyStyle = ( attributes: NoticeAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	if ( align ) s.textAlign = align;
	return s;
};

/** Icon preview: size (width/height/font-size) + colour. */
const buildIconStyle = ( attributes: NoticeAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
		s.fontSize = v;
	}
	if ( attributes.iconColor?.light ) s.color = attributes.iconColor.light;
	return s;
};

/** Text element preview (title / message): typography + colour. */
const buildTextStyle = ( typo: Partial< TypographyDevice >, color?: ColorPair ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, typo );
	if ( color?.light ) s.color = color.light;
	return s;
};

/**
 * Notice edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< NoticeAttributes > ): JSX.Element {
	const {
		noticeType, showIcon, icon, showTitle, title, titleTag, content,
		dismissible, className, responsiveVisibility,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const type = noticeType || 'info';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-notice',
			`flexa-notice--${ type }`,
			blockId && `flexa-notice-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → a simple static placeholder.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<div className="flexa-notice__inner">
					<span className="flexa-notice__icon" dangerouslySetInnerHTML={ { __html: DEFAULT_ICONS[ type ] || DEFAULT_ICONS.info } } />
					<div className="flexa-notice__body">
						<strong className="flexa-notice__title">{ __( 'Heads up!', 'flexa-block' ) }</strong>
						<div className="flexa-notice__content">{ __( 'This is a notice.', 'flexa-block' ) }</div>
					</div>
				</div>
			</div>
		);
	}

	const iconMarkup = icon?.source === 'upload' && icon?.url ? '' : ( icon?.markup || DEFAULT_ICONS[ type ] || DEFAULT_ICONS.info );
	const TitleTag = ( titleTag || 'strong' ) as keyof JSX.IntrinsicElements;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-notice-inspector">
					<InspectorTabs
						layout={
							<>
								<NoticeSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<NoticeIconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<NoticeTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<NoticeMessagePanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				<div className="flexa-notice__inner" style={ buildInnerStyle( attributes ) }>
					{ showIcon !== false && (
						icon?.source === 'upload' && icon?.url ? (
							<span className="flexa-notice__icon" style={ buildIconStyle( attributes, device ) }>
								<img className="flexa-icon" src={ icon.url } alt="" />
							</span>
						) : (
							<span
								className="flexa-notice__icon"
								style={ buildIconStyle( attributes, device ) }
								dangerouslySetInnerHTML={ { __html: iconMarkup } }
							/>
						)
					) }
					<div className="flexa-notice__body" style={ buildBodyStyle( attributes, device ) }>
						{ showTitle !== false && (
							<RichText
								tagName={ TitleTag }
								className="flexa-notice__title"
								style={ buildTextStyle( rawDevice( attributes.titleTypography, device ), attributes.titleColor ) }
								value={ title || '' }
								allowedFormats={ [ 'core/bold', 'core/italic' ] }
								placeholder={ __( 'Notice title…', 'flexa-block' ) }
								onChange={ ( v: string ) => setAttributes( { title: v } ) }
							/>
						) }
						<RichText
							tagName="div"
							className="flexa-notice__content"
							style={ buildTextStyle( rawDevice( attributes.contentTypography, device ), attributes.contentColor ) }
							value={ content || '' }
							allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
							placeholder={ __( 'Add your message…', 'flexa-block' ) }
							onChange={ ( v: string ) => setAttributes( { content: v } ) }
						/>
					</div>
					{ dismissible && (
						<button type="button" className="flexa-notice__dismiss" aria-label={ __( 'Dismiss', 'flexa-block' ) }>
							×
						</button>
					) }
				</div>
			</div>
		</>
	);
}
