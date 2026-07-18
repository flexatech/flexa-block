/**
 * Shared TypeScript types for Flexa Block.
 *
 * These describe the block attribute shapes. Typing them once here is the main
 * payoff of the TypeScript move: every panel, control and CSS-related helper
 * reads/writes these, so a wrong field name or shape is caught at compile time
 * across all blocks — and refactors become safe.
 *
 * @package Flexa\Block
 */

/** Responsive attribute keys. */
export type DeviceKey = 'desktop' | 'tablet' | 'mobile';

/** Editor preview device types (WordPress naming). */
export type DeviceType = 'Desktop' | 'Tablet' | 'Mobile';

/** A value that can differ per device. */
export interface ResponsiveValue< T > {
	desktop?: T;
	tablet?: T;
	mobile?: T;
}

/** 4-side box on our shape (padding / margin / border width). */
export interface BoxValue {
	top?: string;
	right?: string;
	bottom?: string;
	left?: string;
	unit?: string;
}

/** Border radius by corner. */
export interface RadiusValue {
	topLeft?: string;
	topRight?: string;
	bottomRight?: string;
	bottomLeft?: string;
	unit?: string;
}

/** A light / dark colour pair. */
export interface ColorPair {
	light?: string;
	dark?: string;
}

/** Flex gap. */
export interface GapValue {
	column?: string;
	row?: string;
	unit?: string;
}

/** A single CSS length value. */
export interface LengthValue {
	value?: string;
	unit?: string;
}

export interface LayoutDevice {
	display?: string;
	direction?: string;
	justifyContent?: string;
	alignItems?: string;
	wrap?: string;
	gap?: GapValue;
}

/**
 * One device's CSS Grid layout. `columns`/`rows` are track definitions: a `fr`
 * unit stores a track count (expanded to `repeat(n, 1fr)`), while `custom` holds
 * a raw grid-template value (e.g. "1fr 2fr 1fr"). Empty `rows` → auto rows.
 */
export interface GridLayoutDevice {
	columns?: LengthValue;
	rows?: LengthValue;
	gap?: GapValue;
	autoFlow?: string;
	justifyItems?: string;
	alignItems?: string;
	justifyContent?: string;
	alignContent?: string;
}

/** Per-device grid item span: how many columns/rows a grid child occupies. */
export interface GridSpanDevice {
	column?: string;
	row?: string;
}

export interface SpacingDevice {
	padding?: BoxValue;
	margin?: BoxValue;
}

export interface BorderDevice {
	style?: string;
	width?: BoxValue;
	color?: ColorPair;
	radius?: RadiusValue;
}

export interface AdvancedLayoutDevice {
	overflow?: string;
	position?: string;
	zIndex?: string;
	inset?: BoxValue;
}

export interface SizeDevice {
	minHeight?: LengthValue;
}

export interface BackgroundImage {
	id?: number | null;
	url?: string;
	size?: string;
	position?: string;
	repeat?: string;
}

export interface BackgroundAttr {
	type?: 'none' | 'classic' | 'gradient' | 'image';
	color?: ColorPair;
	gradient?: ColorPair;
	image?: BackgroundImage;
	lazyLoad?: boolean;
}

export interface BoxShadowAttr {
	enabled?: boolean;
	horizontal?: string;
	vertical?: string;
	blur?: string;
	spread?: string;
	color?: ColorPair;
	inset?: boolean;
}

export interface ResponsiveVisibilityAttr {
	hideOnDesktop?: boolean;
	hideOnTablet?: boolean;
	hideOnMobile?: boolean;
}

/**
 * Scroll-entrance animation (single, non-responsive) — Flatsome-style.
 *
 * `type` is the effect name (`none` disables it); `duration` picks a fixed
 * speed preset; `delay` is a millisecond step ('' = none). The effect is
 * applied on the front end by a shared `render_block` filter (which marks the
 * block with `data-flexa-animate`) + a shared IntersectionObserver, never by
 * per-block generated CSS.
 */
export interface AnimationAttr {
	type?: string;
	duration?: 'slow' | 'normal' | 'fast';
	delay?: string;
}

/** Full Container block attributes (the shared panels read these). */
export interface ContainerAttributes {
	blockId?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	variationSelected?: boolean;
	className?: string;
	layout?: ResponsiveValue< LayoutDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	border?: ResponsiveValue< BorderDevice >;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	size?: ResponsiveValue< SizeDevice >;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	background?: BackgroundAttr;
	boxShadow?: BoxShadowAttr;
	gridSpan?: ResponsiveValue< GridSpanDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
}

/** Typography controls for one device. */
export interface TypographyDevice {
	fontSize?: LengthValue;
	fontWeight?: string;
	letterSpacing?: LengthValue;
	textTransform?: string;
	lineHeight?: string;
}

/**
 * A chosen icon, resolved to render-ready data.
 *
 * `source` records where it came from; `markup` holds the sanitised inline SVG
 * for builtin/library icons; `url`/`id` reference an uploaded SVG in the media
 * library. Storing the final markup means the front end never needs to know the
 * icon catalog — it just prints `markup` (or an <img> for uploads).
 */
export interface IconValue {
	source?: 'none' | 'builtin' | 'library' | 'upload';
	name?: string;
	markup?: string;
	url?: string;
	id?: number | null;
}

/** Icon slot for the button — an IconValue plus its position around the label. */
export interface ButtonIconAttr extends IconValue {
	position?: 'before' | 'after';
}

/** Preset motion effect applied to the button on `:hover`. */
export type ButtonHoverTransform = 'none' | 'grow' | 'shrink' | 'lift' | 'push';

/** Hover set (text / background / border colours + motion transform) for the button. */
export interface ButtonHoverAttr {
	text?: ColorPair;
	background?: ColorPair;
	border?: ColorPair;
	transform?: ButtonHoverTransform;
}

/** A single custom key/value HTML attribute pair. */
export interface CustomAttribute {
	key?: string;
	value?: string;
}

/** Custom HTML attributes wrapper (mirrors block.json `htmlAttributes`). */
export interface HtmlAttributesAttr {
	customAttributes?: CustomAttribute[];
}

/**
 * Full Button block attributes.
 *
 * The layout/style fields (`spacing`, `border`, `boxShadow`, `background`,
 * `responsiveVisibility`) intentionally reuse the same shapes as the Container
 * so the shared inspector panels work unchanged. The rest are button-specific.
 */
export interface ButtonAttributes {
	blockId?: string;
	className?: string;
	text?: string;
	url?: string;
	linkTarget?: string;
	rel?: string;
	variant?: 'fill' | 'outline' | 'ghost';
	sizePreset?: 'sm' | 'md' | 'lg';
	align?: 'left' | 'center' | 'right';
	fullWidth?: boolean;
	icon?: ButtonIconAttr;
	textColor?: ColorPair;
	hover?: ButtonHoverAttr;
	typography?: ResponsiveValue< TypographyDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	background?: BackgroundAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/* ---------------------------------------------------------------------------
 * Slider (flexa/slides) + Slide (flexa/slide) — a carousel parent that holds
 * individual slide children. The parent owns the carousel behaviour, navigation
 * and pagination; each slide owns its own content box (background, padding,
 * content alignment). Effects are powered by Swiper on the front end.
 * ------------------------------------------------------------------------- */

/** Slide transition effect (maps to a Swiper effect module). */
export type SlidesEffect = 'slide' | 'fade' | 'cube' | 'coverflow' | 'flip' | 'creative';

/** Pagination style for the slider. */
export type SlidesPaginationType = 'bullets' | 'fraction' | 'progressbar';

/** Full Slider (parent) block attributes. */
export interface SlidesAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	// Carousel behaviour (front-end via Swiper, carried in a data attribute).
	autoplay?: boolean;
	autoplayDelay?: number;
	pauseOnHover?: boolean;
	pauseOnInteraction?: boolean;
	loop?: boolean;
	speed?: number;
	effect?: SlidesEffect;
	slidesPerView?: ResponsiveValue< number >;
	spaceBetween?: number;
	// Navigation arrows.
	showArrows?: boolean;
	arrowPosition?: 'inside' | 'outside';
	arrowShowOnHover?: boolean;
	arrowIconPrev?: IconValue;
	arrowIconNext?: IconValue;
	arrowSize?: LengthValue;
	arrowColor?: ColorPair;
	arrowColorHover?: ColorPair;
	arrowBackground?: ColorPair;
	arrowBackgroundHover?: ColorPair;
	arrowRadius?: LengthValue;
	arrowOffset?: LengthValue;
	// Pagination.
	showPagination?: boolean;
	paginationPosition?: 'inside' | 'outside';
	paginationType?: SlidesPaginationType;
	bulletColor?: ColorPair;
	bulletColorActive?: ColorPair;
	bulletSize?: LengthValue;
	// Base box (inherited from the Container family).
	minHeight?: ResponsiveValue< LengthValue >;
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One slide's content-box layout for a device (vertical/horizontal align + gap). */
export interface SlideLayoutDevice {
	justifyContent?: string;
	alignItems?: string;
	gap?: GapValue;
}

/** Full Slide (child) block attributes. */
export interface SlideAttributes {
	blockId?: string;
	className?: string;
	background?: BackgroundAttr;
	spacing?: ResponsiveValue< SpacingDevice >;
	layout?: ResponsiveValue< SlideLayoutDevice >;
	contentMaxWidth?: ResponsiveValue< LengthValue >;
}

/** Text-stroke effect (single, non-responsive). */
export interface TextStrokeAttr {
	enabled?: boolean;
	width?: LengthValue;
	color?: ColorPair;
}

/** Text-shadow effect (single, non-responsive). */
export interface TextShadowAttr {
	enabled?: boolean;
	horizontal?: string;
	vertical?: string;
	blur?: string;
	color?: ColorPair;
}

/**
 * Full Heading block attributes.
 *
 * The heading-specific fields (content/tag/link, typography, colours, effects,
 * subheading, separator) carry the block's identity. The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `responsiveVisibility`) reuse
 * the same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface HeadingAttributes {
	blockId?: string;
	className?: string;
	// Heading element.
	content?: string;
	tag?: string;
	url?: string;
	linkTarget?: string;
	rel?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	gap?: LengthValue;
	// Typography + colours + effects on the title.
	typography?: ResponsiveValue< TypographyDevice >;
	textType?: 'color' | 'gradient';
	textColor?: ColorPair;
	textGradient?: ColorPair;
	textColorHover?: ColorPair;
	textStroke?: TextStrokeAttr;
	textShadow?: TextShadowAttr;
	blendMode?: string;
	// Subheading.
	showSubheading?: boolean;
	subheadingContent?: string;
	subheadingPosition?: 'top' | 'bottom';
	subheadingTag?: string;
	subheadingTypography?: ResponsiveValue< TypographyDevice >;
	subheadingColor?: ColorPair;
	// Separator.
	showSeparator?: boolean;
	separatorPosition?: 'top' | 'bottom';
	separatorWidth?: LengthValue;
	separatorWeight?: LengthValue;
	separatorStyle?: string;
	separatorColor?: ColorPair;
	separatorSpacing?: LengthValue;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** A selected media image, resolved to render-ready data. */
export interface ImageMedia {
	id?: number | null;
	url?: string;
	alt?: string;
	width?: number | null;
	height?: number | null;
}

/** Aspect-ratio lock (single, non-responsive) — `ratio` is a CSS ratio like "16/9". */
export interface AspectRatioAttr {
	enabled?: boolean;
	ratio?: string;
}

/** Colour or gradient overlay laid over the image, with opacity + blend. */
export interface ImageOverlayAttr {
	type?: 'none' | 'color' | 'gradient';
	color?: ColorPair;
	gradient?: ColorPair;
	opacity?: number;
	blendMode?: string;
}

/** Image mask — a built-in shape name, or a custom uploaded SVG/PNG mask. */
export interface ImageMaskAttr {
	shape?: string;
	customImage?: { id?: number | null; url?: string };
	size?: string;
	position?: string;
	repeat?: string;
}

/** Caption settings — text source, placement and alignment. */
export interface ImageCaptionAttr {
	show?: boolean;
	source?: 'custom' | 'attachment';
	text?: string;
	display?: 'below' | 'overlay';
	position?: 'top' | 'center' | 'bottom';
	alignment?: 'left' | 'center' | 'right';
}

/** A link target (url / target / rel). */
export interface LinkAttr {
	url?: string;
	target?: string;
	rel?: string;
}

/** Lightbox appearance (overlay background + whether to show the caption). */
export interface ImageLightboxAttr {
	background?: ColorPair;
	showCaption?: boolean;
}

/**
 * Full Image block attributes.
 *
 * The image-specific fields (media, sizing, aspect ratio, overlay, mask,
 * caption, link/lightbox) carry the block's identity. The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `responsiveVisibility`) reuse
 * the same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface ImageAttributes {
	blockId?: string;
	className?: string;
	// Media.
	image?: ImageMedia;
	imageSize?: string;
	altText?: string;
	// Sizing + fit.
	objectFit?: string;
	imageWidth?: ResponsiveValue< LengthValue >;
	imageAlign?: ResponsiveValue< string >;
	aspectRatio?: AspectRatioAttr;
	lazyLoad?: boolean;
	// Effects.
	hoverEffect?: string;
	overlay?: ImageOverlayAttr;
	mask?: ImageMaskAttr;
	// Caption.
	caption?: ImageCaptionAttr;
	captionColor?: ColorPair;
	captionBackground?: ColorPair;
	captionTypography?: ResponsiveValue< TypographyDevice >;
	// Interaction.
	clickAction?: 'none' | 'link' | 'lightbox' | 'media';
	link?: LinkAttr;
	lightbox?: ImageLightboxAttr;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Before/After block attributes.
 *
 * The block-specific fields (the two comparison images, the horizontal/vertical
 * split, the initial handle position, drag vs hover interaction, the corner
 * labels, and the handle / divider styling) carry the block's identity. The
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged. The two images reuse
 * the Image block's `ImageMedia`/`AspectRatioAttr` shapes.
 */
export interface BeforeAfterAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	// Media — the two images being compared.
	beforeImage?: ImageMedia;
	afterImage?: ImageMedia;
	beforeAlt?: string;
	afterAlt?: string;
	objectFit?: string;
	aspectRatio?: AspectRatioAttr;
	// Comparison behaviour (view.ts reads these via data attributes).
	orientation?: 'horizontal' | 'vertical';
	initialPosition?: number;
	interaction?: 'drag' | 'hover';
	// Corner labels.
	showLabels?: boolean;
	beforeLabel?: string;
	afterLabel?: string;
	labelColor?: ColorPair;
	labelBackground?: ColorPair;
	// Handle + divider line.
	handleColor?: ColorPair;
	handleSize?: LengthValue;
	dividerWidth?: LengthValue;
	// Size + alignment of the comparison frame.
	maxWidth?: ResponsiveValue< LengthValue >;
	align?: ResponsiveValue< string >;
	size?: ResponsiveValue< SizeDevice >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** How units are visually separated in the timer. */
export interface CountdownSeparatorAttr {
	type?: 'none' | 'colon' | 'dash' | 'custom';
	custom?: string;
}

/** Unit labels — whether to show them, where, and the text per unit. */
export interface CountdownLabelsAttr {
	show?: boolean;
	position?: 'above' | 'below';
	days?: string;
	hours?: string;
	minutes?: string;
	seconds?: string;
}

/** What happens when the countdown reaches zero. */
export interface CountdownExpiredAttr {
	type?: 'hide' | 'zero' | 'message';
	message?: string;
}

/**
 * Full Countdown block attributes.
 *
 * The countdown-specific fields (target date, which units to show, separator,
 * labels, completion action, per-unit digit/label typography and colours, the
 * unit "box" styling) carry the block's identity. The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`, `size`,
 * `responsiveVisibility`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface CountdownAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Timer behaviour.
	endDate?: string;
	showDays?: boolean;
	showHours?: boolean;
	showMinutes?: boolean;
	showSeconds?: boolean;
	separator?: CountdownSeparatorAttr;
	labels?: CountdownLabelsAttr;
	expiredAction?: CountdownExpiredAttr;
	ariaLive?: 'off' | 'polite' | 'assertive';
	/** UTC offset in hours as a string (e.g. "7", "-5.5"); '' = site default. */
	timezone?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	itemGap?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	size?: ResponsiveValue< SizeDevice >;
	// Digit + label typography / colours.
	digitTypography?: ResponsiveValue< TypographyDevice >;
	labelTypography?: ResponsiveValue< TypographyDevice >;
	separatorFontSize?: ResponsiveValue< LengthValue >;
	digitColor?: ColorPair;
	labelColor?: ColorPair;
	separatorColor?: ColorPair;
	// Unit "box".
	itemBackground?: ColorPair;
	itemPadding?: ResponsiveValue< BoxValue >;
	itemBorderRadius?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** A single FAQ entry — a question and its answer, both rich text. */
export interface FaqItem {
	question?: string;
	answer?: string;
}

/**
 * Full FAQ block attributes.
 *
 * The FAQ-specific fields (the question/answer items, accordion vs grid layout,
 * open/close behaviour, the open-state icon, dividers, per-question and answer
 * typography / colours / padding and the item box) carry the block's identity.
 * The layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged.
 */
export interface FaqAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content + layout.
	items?: FaqItem[];
	rowGap?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	// Behaviour.
	expandFirst?: boolean;
	closeOthers?: boolean;
	allowToggle?: boolean;
	enableSchema?: boolean;
	// Icon.
	showIcon?: boolean;
	iconStyle?: 'plus' | 'chevron' | 'arrow';
	iconPosition?: 'left' | 'right';
	iconSize?: ResponsiveValue< LengthValue >;
	iconGap?: ResponsiveValue< LengthValue >;
	iconColor?: ColorPair;
	iconActiveColor?: ColorPair;
	// Question styling. Text/background fills offer a colour|gradient tab, so each
	// carries a `*Type` discriminator plus a matching `*Gradient` pair.
	questionTypography?: ResponsiveValue< TypographyDevice >;
	questionColorType?: 'color' | 'gradient';
	questionColor?: ColorPair;
	questionColorGradient?: ColorPair;
	questionActiveColorType?: 'color' | 'gradient';
	questionActiveColor?: ColorPair;
	questionActiveColorGradient?: ColorPair;
	questionBackgroundType?: 'color' | 'gradient';
	questionBackground?: ColorPair;
	questionBackgroundGradient?: ColorPair;
	questionActiveBackgroundType?: 'color' | 'gradient';
	questionActiveBackground?: ColorPair;
	questionActiveBackgroundGradient?: ColorPair;
	questionPadding?: ResponsiveValue< BoxValue >;
	// Answer styling.
	answerTypography?: ResponsiveValue< TypographyDevice >;
	answerColorType?: 'color' | 'gradient';
	answerColor?: ColorPair;
	answerColorGradient?: ColorPair;
	answerBackgroundType?: 'color' | 'gradient';
	answerBackground?: ColorPair;
	answerBackgroundGradient?: ColorPair;
	answerPadding?: ResponsiveValue< BoxValue >;
	// Item box.
	itemBackgroundType?: 'color' | 'gradient';
	itemBackground?: ColorPair;
	itemBackgroundGradient?: ColorPair;
	// Foundational (shared inspector panels read these). `border` styles the
	// question and answer boxes individually.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * One social-icon entry — a platform key from the block's catalog, its link,
 * and how it is coloured: 'official' renders the full-colour brand artwork,
 * 'custom' renders the monochrome glyph tinted by this item's own `color`.
 */
export interface SocialIconItem {
	platform?: string;
	link?: LinkAttr;
	colorMode?: 'official' | 'custom';
	color?: ColorPair;
	/**
	 * The chosen icon when `platform === 'custom'` — a WordPress-library glyph or
	 * an uploaded SVG picked via the shared IconPicker. Unused for brand items,
	 * which render their catalog artwork instead.
	 */
	icon?: IconValue;
}

/**
 * Full Social Icons block attributes.
 *
 * The social-specific fields (the icon items, per-device size/gap/alignment,
 * the custom-tint colours and the hover motion) carry the block's identity.
 * The layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `responsiveVisibility`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface SocialIconAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: SocialIconItem[];
	// Layout.
	iconSize?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	// Hover motion.
	hoverEffect?: string;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * One share button — just the target network key from the shared catalog. The
 * destination URL is built at render time from the page (or the custom source),
 * never stored per item.
 */
export interface SocialShareItem {
	network?: string;
}

/**
 * Full Social Share block attributes.
 *
 * The share-specific fields (the network buttons, what URL/title/image they
 * share, the icon-only appearance — colour mode, tint, shape, button background
 * — and the row layout) carry the block's identity. The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `responsiveVisibility`)
 * reuse the same shapes as the Container so the shared inspector panels work
 * unchanged.
 */
export interface SocialShareAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content — which networks to share to.
	items?: SocialShareItem[];
	// What is shared: the current page, or a fixed URL/title/image.
	shareSource?: 'current' | 'custom';
	shareUrl?: string;
	shareTitle?: string;
	shareImage?: string;
	newTab?: boolean;
	// Layout.
	direction?: 'row' | 'column';
	iconSize?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	hoverEffect?: string;
	// Button appearance.
	colorMode?: 'official' | 'custom';
	tint?: ColorPair;
	shape?: 'bare' | 'rounded' | 'circle' | 'square';
	buttonBackground?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Grid block attributes.
 *
 * The grid-specific field is `layout` (CSS Grid tracks, gaps, item and track
 * alignment); the rest — container type + width, spacing, size, background,
 * border, box shadow, advanced layout, responsive visibility, custom HTML
 * attributes — reuse the same shapes as the Container so the shared inspector
 * panels work unchanged.
 */
export interface GridAttributes {
	blockId?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	variationSelected?: boolean;
	className?: string;
	layout?: ResponsiveValue< GridLayoutDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	border?: ResponsiveValue< BorderDevice >;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	size?: ResponsiveValue< SizeDevice >;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	background?: BackgroundAttr;
	boxShadow?: BoxShadowAttr;
	gridSpan?: ResponsiveValue< GridSpanDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Testimonial block attributes.
 *
 * The testimonial-specific fields (star rating, headline, quote and the author
 * block — avatar, name, role — plus their per-element typography / colours /
 * spacing, the content alignment and the inline-vs-stacked author layout) carry
 * the block's identity. The layout/style fields (`size`, `spacing`,
 * `background`, `border`, `boxShadow`, `advancedLayout`, `responsiveVisibility`)
 * reuse the same shapes as the Container so the shared inspector panels work
 * unchanged.
 */
export interface TestimonialAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	authorLayout?: 'inline' | 'stacked';
	// Rating.
	rating?: number;
	showRating?: boolean;
	ratingColor?: ColorPair;
	ratingSize?: ResponsiveValue< LengthValue >;
	ratingSpacing?: ResponsiveValue< LengthValue >;
	// Title.
	title?: string;
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	titleSpacing?: ResponsiveValue< LengthValue >;
	// Quote.
	quote?: string;
	quoteTypography?: ResponsiveValue< TypographyDevice >;
	quoteColor?: ColorPair;
	quoteSpacing?: ResponsiveValue< LengthValue >;
	// Author.
	showAvatar?: boolean;
	authorImage?: ImageMedia;
	authorImageSize?: ResponsiveValue< LengthValue >;
	authorGap?: ResponsiveValue< LengthValue >;
	authorName?: string;
	nameTypography?: ResponsiveValue< TypographyDevice >;
	nameColor?: ColorPair;
	authorJob?: string;
	jobTypography?: ResponsiveValue< TypographyDevice >;
	jobColor?: ColorPair;
	// Foundational (shared inspector panels read these).
	size?: ResponsiveValue< SizeDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Hover colour set (text / background) for the info-box CTA button. */
export interface InfoBoxButtonHoverAttr {
	text?: ColorPair;
	background?: ColorPair;
}

/**
 * Full Info Box block attributes.
 *
 * The info-box-specific fields (the icon-or-image media and its box styling, the
 * prefix / title / description text with per-element typography and colours, an
 * optional divider between title and description, and an optional CTA button)
 * carry the block's identity. Two layout modes — media above the text (column)
 * or beside it (row) — are chosen with `iconPosition`, with an optional stack
 * point for the row mode. The layout/style fields (`spacing`, `background`,
 * `border`, `boxShadow`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface InfoBoxAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Layout.
	iconPosition?: 'top' | 'left';
	alignment?: ResponsiveValue< string >;
	stackOn?: 'none' | 'tablet' | 'mobile';
	mediaGap?: ResponsiveValue< LengthValue >;
	contentGap?: ResponsiveValue< LengthValue >;
	// Media.
	showMedia?: boolean;
	mediaType?: 'icon' | 'image';
	icon?: IconValue;
	image?: ImageMedia;
	iconSize?: ResponsiveValue< LengthValue >;
	imageWidth?: ResponsiveValue< LengthValue >;
	iconColor?: ColorPair;
	mediaBackground?: ColorPair;
	mediaPadding?: ResponsiveValue< BoxValue >;
	mediaRadius?: ResponsiveValue< LengthValue >;
	// Prefix.
	showPrefix?: boolean;
	prefix?: string;
	prefixTypography?: ResponsiveValue< TypographyDevice >;
	prefixColor?: ColorPair;
	// Title.
	title?: string;
	titleTag?: string;
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	// Separator.
	showSeparator?: boolean;
	separatorWidth?: LengthValue;
	separatorWeight?: LengthValue;
	separatorStyle?: string;
	separatorColor?: ColorPair;
	// Description.
	description?: string;
	descriptionTypography?: ResponsiveValue< TypographyDevice >;
	descriptionColor?: ColorPair;
	// CTA button.
	showButton?: boolean;
	buttonText?: string;
	buttonUrl?: string;
	buttonTarget?: string;
	buttonRel?: string;
	buttonIcon?: ButtonIconAttr;
	buttonTextColor?: ColorPair;
	buttonBgColor?: ColorPair;
	buttonHover?: InfoBoxButtonHoverAttr;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Separator block attributes.
 *
 * The separator-specific fields (per-device line width / thickness / alignment,
 * the line style and its light/dark colour) carry the block's identity. The
 * layout fields (`spacing`, `responsiveVisibility`, `htmlAttributes`) reuse the
 * same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface SeparatorAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Line.
	alignment?: ResponsiveValue< string >;
	width?: ResponsiveValue< LengthValue >;
	weight?: ResponsiveValue< LengthValue >;
	lineStyle?: string;
	color?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Text block attributes.
 *
 * The text-specific fields (rich content, tag, per-device alignment, typography,
 * solid-or-gradient text colour, hover colour, text stroke / shadow effects,
 * blend mode and drop cap) carry the block's identity.
 * The layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged. Effects reuse the
 * Heading's `TextStrokeAttr` / `TextShadowAttr` shapes via the shared EffectsPanel.
 */
export interface TextAttributes {
	blockId?: string;
	className?: string;
	// Content.
	content?: string;
	htmlTag?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	// Typography + colours + effects.
	typography?: ResponsiveValue< TypographyDevice >;
	textType?: 'color' | 'gradient';
	textColor?: ColorPair;
	textGradient?: ColorPair;
	textColorHover?: ColorPair;
	textStroke?: TextStrokeAttr;
	textShadow?: TextShadowAttr;
	blendMode?: string;
	dropCap?: boolean;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/* -----------------------------------------------------------------------------
 * WooCommerce product blocks.
 *
 * These are dynamic single-product blocks: they read the current product on the
 * front end (name, price, gallery, rating, tabbed details) rather than
 * user-entered content. In the editor they preview representative placeholder
 * data. The layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`, `animation`) reuse
 * the same shapes as the Container so the shared inspector panels work unchanged.
 * -------------------------------------------------------------------------- */

/** Product Name — the product title, optionally linked to the product page. */
export interface ProductNameAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Content.
	linkToProduct?: boolean;
	linkTarget?: boolean;
	// Layout.
	alignment?: ResponsiveValue< string >;
	// Typography + colours + effects (shared text panels read these).
	typography?: ResponsiveValue< TypographyDevice >;
	textType?: 'color' | 'gradient';
	textColor?: ColorPair;
	textGradient?: ColorPair;
	textColorHover?: ColorPair;
	textStroke?: TextStrokeAttr;
	textShadow?: TextShadowAttr;
	blendMode?: string;
	// Foundation.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	animation?: AnimationAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Product Price — regular + sale price with optional text prefix/suffix. */
export interface ProductPriceAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	salePricePosition?: 'after' | 'before';
	// Strike-through of the original price when the product is on sale.
	strikethrough?: boolean;
	strikeColor?: ColorPair;
	strikeThickness?: number;
	// Regular price.
	regularColor?: ColorPair;
	regularTypography?: ResponsiveValue< TypographyDevice >;
	// Sale price.
	saleColor?: ColorPair;
	saleTypography?: ResponsiveValue< TypographyDevice >;
	// Foundation.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	animation?: AnimationAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Product Rating — the average star rating and optional review count. */
export interface ProductRatingAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	displayType?: 'stars' | 'stars-number' | 'number';
	showReviewCount?: boolean;
	showEmptyRating?: boolean;
	// Stars.
	starColor?: ColorPair;
	starEmptyColor?: ColorPair;
	starSize?: ResponsiveValue< LengthValue >;
	starGap?: ResponsiveValue< LengthValue >;
	// Numeric score (shown for the 'number' / 'stars-number' display types).
	numberColor?: ColorPair;
	numberTypography?: ResponsiveValue< TypographyDevice >;
	// Review count text.
	countColor?: ColorPair;
	countTypography?: ResponsiveValue< TypographyDevice >;
	// Foundation.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	animation?: AnimationAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Product Details — tabbed Description / Additional information / Reviews. */
export interface ProductDetailAttributes extends PaginationAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Which tabs to show.
	showDescriptionTab?: boolean;
	showAdditionalTab?: boolean;
	showReviewsTab?: boolean;
	// Reviews list pagination (uses the shared PaginationAttributes fields).
	reviewsPerPage?: number;
	reviewsLoadMore?: number;
	// Reviews tab — style the WooCommerce review elements individually.
	reviewsTitleColor?: ColorPair;
	reviewsTitleTypography?: ResponsiveValue< TypographyDevice >;
	reviewAuthorColor?: ColorPair;
	reviewDateColor?: ColorPair;
	reviewStarsColor?: ColorPair;
	reviewStarsSize?: LengthValue;
	reviewTextColor?: ColorPair;
	// Additional information tab — style the WooCommerce attributes table.
	additionalLabelColor?: ColorPair;
	additionalLabelTypography?: ResponsiveValue< TypographyDevice >;
	additionalValueColor?: ColorPair;
	additionalValueTypography?: ResponsiveValue< TypographyDevice >;
	additionalBorderColor?: ColorPair;
	additionalCellPadding?: ResponsiveValue< BoxValue >;
	// Tab title.
	tabTitleTypography?: ResponsiveValue< TypographyDevice >;
	tabTitleColor?: ColorPair;
	tabTitleBg?: ColorPair;
	tabActiveColor?: ColorPair;
	tabActiveBg?: ColorPair;
	tabTitlePadding?: ResponsiveValue< BoxValue >;
	tabGap?: ResponsiveValue< LengthValue >;
	// Content.
	contentTypography?: ResponsiveValue< TypographyDevice >;
	contentColor?: ColorPair;
	contentPadding?: ResponsiveValue< BoxValue >;
	// Foundation.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	animation?: AnimationAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Product Image — featured image gallery with a thumbnail strip. */
export interface ProductImageAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Gallery layout.
	galleryPosition?: 'bottom' | 'left' | 'right';
	showThumbnails?: boolean;
	thumbnailsPerView?: number;
	thumbnailGap?: ResponsiveValue< LengthValue >;
	// Autoplay — auto-advance the featured image through the gallery.
	autoplay?: boolean;
	autoplaySpeed?: number;
	// Featured image.
	imageScale?: 'none' | 'cover' | 'contain' | 'fill' | 'scale-down';
	imageHeight?: ResponsiveValue< LengthValue >;
	adaptiveHeight?: boolean;
	zoomOnHover?: boolean;
	imageRadius?: ResponsiveValue< LengthValue >;
	// Layout.
	alignment?: ResponsiveValue< string >;
	// Foundation.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	animation?: AnimationAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One counter stat's number — the animated numeric value plus optional affixes. */
export interface CounterNumber {
	value?: string;
	prefix?: string;
	suffix?: string;
}

/** A single counter entry — an animated number, a label and an optional icon. */
export interface CounterItem {
	number?: CounterNumber;
	label?: string;
	icon?: IconValue;
}

/**
 * Full Counter block attributes.
 *
 * The counter-specific fields (the stat items — each an animated number with
 * prefix/suffix, a label and an icon — the per-device column grid, the count-up
 * animation, the icon/number/label typography and colours, the number↔label
 * divider and the per-item box) carry the block's identity. The layout/style
 * fields (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface CounterAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: CounterItem[];
	// Layout.
	columns?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	contentGap?: ResponsiveValue< LengthValue >;
	// Number animation (view.ts reads these; they emit no CSS).
	countUp?: boolean;
	countDuration?: number;
	// Icon.
	showIcon?: boolean;
	iconPosition?: 'top' | 'inline';
	iconSize?: ResponsiveValue< LengthValue >;
	iconColor?: ColorPair;
	// Number.
	numberTypography?: ResponsiveValue< TypographyDevice >;
	numberColor?: ColorPair;
	// Label.
	labelTypography?: ResponsiveValue< TypographyDevice >;
	labelColor?: ColorPair;
	// Divider between the number and the label.
	showSeparator?: boolean;
	separatorStyle?: string;
	separatorColor?: ColorPair;
	separatorWidth?: ResponsiveValue< LengthValue >;
	separatorWeight?: ResponsiveValue< LengthValue >;
	// Item box.
	itemBackground?: ColorPair;
	itemPadding?: ResponsiveValue< BoxValue >;
	itemBorderRadius?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Which heading levels the table of contents collects (h1…h6 on/off). */
export interface TocHeadingsAttr {
	h1?: boolean;
	h2?: boolean;
	h3?: boolean;
	h4?: boolean;
	h5?: boolean;
	h6?: boolean;
}

/**
 * Full Table of Contents block attributes.
 *
 * The TOC-specific fields (the title, which heading levels to collect, the
 * bullet/number/none marker, smooth-scroll behaviour, the collapse toggle and
 * the title / link / marker typography and colours) carry the block's identity;
 * the actual entries are built from the page's headings at render time (front
 * end) and previewed live from the editor's heading blocks. The layout/style
 * fields (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface TableOfContentAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content + structure.
	title?: string;
	showTitle?: boolean;
	titleTag?: string;
	headings?: TocHeadingsAttr;
	markerType?: 'bullet' | 'number' | 'none';
	emptyText?: string;
	// Behaviour (view.ts reads these; smooth scroll + collapse emit no CSS).
	smoothScroll?: boolean;
	scrollOffset?: number;
	collapsible?: boolean;
	initialCollapsed?: boolean;
	// Title styling.
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	// Link + marker styling.
	linkTypography?: ResponsiveValue< TypographyDevice >;
	linkColor?: ColorPair;
	linkHoverColor?: ColorPair;
	markerColor?: ColorPair;
	itemGap?: ResponsiveValue< LengthValue >;
	indent?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	listMaxHeight?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * One comparison-table column (a plan). `values` for this column live on each
 * feature row (`ComparisonRow.values[colIndex]`), not here — a column only
 * carries its own header: title, subtitle, optional logo, spotlight flag, badge
 * text and a call-to-action.
 */
export interface ComparisonColumn {
	title?: string;
	subtitle?: string;
	imageUrl?: string;
	imageId?: number | null;
	highlighted?: boolean;
	badge?: string;
	ctaText?: string;
	ctaUrl?: string;
}

/**
 * One feature row. `values[i]` is the cell under column `i`; a value of `"true"`
 * or `"false"` renders a check / cross mark, anything else renders as plain text.
 */
export interface ComparisonRow {
	label?: string;
	values?: string[];
}

/**
 * Full Comparison Table block attributes.
 *
 * The block-specific fields (the plan columns, the feature rows, the highlighted
 * column accent, the check/cross marks, the sticky header, zebra striping, cell
 * alignment, header/cell typography and colours, and the row/column dividers)
 * carry the block's identity. The layout/style fields (`containerType`/width,
 * `spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged.
 */
export interface ComparisonTableAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	columns?: ComparisonColumn[];
	rows?: ComparisonRow[];
	// Width (boxed vs full-width, like the Container).
	containerType?: 'boxed' | 'full-width';
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Behaviour / layout.
	stickyHeader?: boolean;
	zebra?: boolean;
	zebraColor?: ColorPair;
	cellAlign?: ResponsiveValue< string >;
	cellPadding?: ResponsiveValue< BoxValue >;
	// Header styling.
	headerTypography?: ResponsiveValue< TypographyDevice >;
	headerColor?: ColorPair;
	headerBackground?: ColorPair;
	highlightColor?: ColorPair;
	// Spotlight accent bar (the inset top bar on the highlighted column header).
	spotlightBarColor?: ColorPair;
	spotlightBarWidth?: LengthValue;
	// Badge (the "Popular" ribbon). Background falls back to highlightColor when unset.
	badgeBackground?: ColorPair;
	badgeColor?: ColorPair;
	badgeTypography?: ResponsiveValue< TypographyDevice >;
	badgeRadius?: LengthValue;
	badgePadding?: BoxValue;
	// Body cell styling.
	cellTypography?: ResponsiveValue< TypographyDevice >;
	cellColor?: ColorPair;
	// Boolean marks.
	checkIcon?: 'check' | 'star' | 'dot';
	crossIcon?: 'cross' | 'dash' | 'minus';
	checkColor?: ColorPair;
	crossColor?: ColorPair;
	// Dividers.
	showRowDivider?: boolean;
	showColumnDivider?: boolean;
	dividerColor?: ColorPair;
	dividerWidth?: LengthValue;
	// Column call-to-action button (block-level — one style for every column CTA).
	// The background + hover background each offer a colour|gradient tab, so each
	// carries a `*Type` discriminator plus a matching `*Gradient` pair.
	buttonTextColor?: ColorPair;
	buttonTextColorHover?: ColorPair;
	buttonBackgroundType?: 'color' | 'gradient';
	buttonBackground?: ColorPair;
	buttonBackgroundGradient?: ColorPair;
	buttonBackgroundHoverType?: 'color' | 'gradient';
	buttonBackgroundHover?: ColorPair;
	buttonBackgroundHoverGradient?: ColorPair;
	buttonRadius?: LengthValue;
	buttonPadding?: BoxValue;
	buttonWidth?: 'auto' | 'full';
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One manual breadcrumb entry — a label and an optional link URL. */
export interface BreadcrumbItem {
	label?: string;
	url?: string;
}

/**
 * How the crumbs are separated. `type` is a token for the built-in glyphs
 * (slash `/`, chevron `›`, raquo `»`, dash `-`) or `custom`, in which case
 * `custom` holds the literal character(s) to print between crumbs.
 */
export interface BreadcrumbSeparatorAttr {
	type?: 'slash' | 'chevron' | 'raquo' | 'dash' | 'custom';
	custom?: string;
}

/**
 * Full Breadcrumb block attributes.
 *
 * The breadcrumb-specific fields (the auto/manual source and its manual items,
 * the home icon + text, the separator, the show-current / current-is-link
 * toggles, per-device alignment / item gap / typography, the link / current /
 * separator colours with hover, and the optional BreadcrumbList schema) carry
 * the block's identity. The layout/style fields (`spacing`, `background`,
 * `border`, `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the
 * same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface BreadcrumbAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	source?: 'auto' | 'manual';
	items?: BreadcrumbItem[];
	homeIcon?: boolean;
	homeIconValue?: IconValue;
	homeText?: string;
	separator?: BreadcrumbSeparatorAttr;
	showCurrent?: boolean;
	currentIsLink?: boolean;
	// Layout.
	alignment?: ResponsiveValue< string >;
	itemGap?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	// Typography + colours (solid light/dark pairs, plus hover).
	typography?: ResponsiveValue< TypographyDevice >;
	linkColor?: ColorPair;
	linkColorHover?: ColorPair;
	currentColor?: ColorPair;
	currentColorHover?: ColorPair;
	separatorColor?: ColorPair;
	separatorColorHover?: ColorPair;
	// SEO.
	enableSchema?: boolean;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Video Popup block attributes.
 *
 * The block-specific fields (the video source + URL, the trigger appearance —
 * thumbnail / button / text — the play icon and its size / colours, the closed
 * thumbnail scrim, the aspect ratio, autoplay, and the lightbox backdrop colour
 * and opacity) carry the block's identity. The layout/style fields (`maxWidth`,
 * `align`, `spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged. The thumbnail reuses
 * the Image block's `ImageMedia` shape and the play icon the shared `IconValue`.
 */
export interface VideoPopupAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	// Video.
	videoSource?: 'youtube' | 'vimeo' | 'mp4';
	videoUrl?: string;
	autoplay?: boolean;
	// Display: open in a popup/lightbox, or play inline in place.
	displayMode?: 'popup' | 'inline';
	// Trigger.
	trigger?: 'thumbnail' | 'button' | 'text';
	thumbnailImage?: ImageMedia;
	buttonText?: string;
	linkText?: string;
	scrimOpacity?: number;
	// Play icon.
	playIcon?: IconValue;
	playIconSize?: LengthValue;
	playIconColor?: ColorPair;
	playIconBackground?: ColorPair;
	// Appearance.
	aspectRatio?: '16:9' | '4:3' | '1:1';
	// Lightbox backdrop.
	overlayColor?: ColorPair;
	overlayOpacity?: number;
	// Size + alignment of the trigger.
	maxWidth?: ResponsiveValue< LengthValue >;
	align?: ResponsiveValue< string >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * One tab — a label, an optional icon (via the shared IconPicker) and its panel
 * content. `content` is a plain multiline string stored per tab and escaped on
 * render (a small inline whitelist), so the front end never trusts raw markup.
 */
export interface TabItem {
	label?: string;
	icon?: IconValue;
	content?: string;
}

/**
 * Full Tabs block attributes.
 *
 * The tab-specific fields (the repeatable tabs, the default-open tab, the tab
 * style — underline / pill / boxed — the tab alignment, the icon toggle, the
 * tab and content typography, and the tab / active / hover / content colours
 * with the active indicator and content background) carry the block's identity.
 * The layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged. On the
 * front end the horizontal tab bar collapses to an accordion on mobile.
 */
export interface TabsAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	htmlTag?: string;
	// Content.
	tabs?: TabItem[];
	activeTab?: number;
	// Appearance.
	tabStyle?: 'underline' | 'pill' | 'boxed';
	tabAlign?: 'left' | 'center' | 'right' | 'justify';
	showIcon?: boolean;
	// Layout.
	tabGap?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	tabPadding?: ResponsiveValue< BoxValue >;
	contentPadding?: ResponsiveValue< BoxValue >;
	iconSize?: ResponsiveValue< LengthValue >;
	iconGap?: ResponsiveValue< LengthValue >;
	// Typography.
	tabTypography?: ResponsiveValue< TypographyDevice >;
	contentTypography?: ResponsiveValue< TypographyDevice >;
	// Tab colours (solid light/dark pairs).
	tabColor?: ColorPair;
	tabHoverColor?: ColorPair;
	tabActiveColor?: ColorPair;
	// Active indicator (underline colour / pill or boxed background) — colour|gradient.
	tabActiveIndicatorType?: 'color' | 'gradient';
	tabActiveIndicator?: ColorPair;
	tabActiveIndicatorGradient?: ColorPair;
	// Content colours.
	contentColor?: ColorPair;
	contentBackgroundType?: 'color' | 'gradient';
	contentBackground?: ColorPair;
	contentBackgroundGradient?: ColorPair;
	// Foundational (shared inspector panels read these). `border` + `boxShadow`
	// style the content panel box.
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * One step in the vertical timeline — a title, a description and a status
 * (done / active / upcoming). Each step's marker follows the block's global
 * `markerType`, but a step may override it: a per-step `icon` (via the shared
 * IconPicker) or `image` (via MediaUpload) replaces the auto-incrementing number.
 */
export interface StepItem {
	title?: string;
	description?: string;
	status?: 'done' | 'active' | 'upcoming';
	/** Per-step marker override; '' / undefined inherits the block's markerType. */
	markerType?: '' | 'number' | 'icon' | 'image';
	icon?: IconValue;
	image?: ImageMedia;
}

/**
 * Full Steps block attributes.
 *
 * The steps-specific fields (the ordered step items, the marker — its type
 * number/icon/image, shape, size and light/dark colours plus per-status
 * accents — the connecting line, the marker↔content gap, the step gap and the
 * title/description typography and colours) carry the block's identity. The
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface StepsAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: StepItem[];
	// Marker.
	markerType?: 'number' | 'icon' | 'image';
	markerShape?: 'circle' | 'square' | 'rounded';
	markerSize?: ResponsiveValue< LengthValue >;
	markerColor?: ColorPair;
	markerTextColor?: ColorPair;
	// Per-status marker background accents (optional).
	doneColor?: ColorPair;
	activeColor?: ColorPair;
	upcomingColor?: ColorPair;
	// Connector line between markers.
	connectorShow?: boolean;
	connectorColor?: ColorPair;
	connectorWidth?: ResponsiveValue< LengthValue >;
	connectorStyle?: string;
	// Layout.
	markerGap?: ResponsiveValue< LengthValue >;
	itemGap?: ResponsiveValue< LengthValue >;
	contentAlign?: ResponsiveValue< string >;
	maxWidth?: ResponsiveValue< LengthValue >;
	// Title + description.
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	descriptionTypography?: ResponsiveValue< TypographyDevice >;
	descriptionColor?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Google Map embed mode — free public embed, or the Embed API with a key. */
export interface MapApiKeyAttr {
	enabled?: boolean;
	key?: string;
}

/**
 * Full Google Map block attributes.
 *
 * The map-specific fields (the searched `location`, the `zoom` level, the
 * per-device `height` and the free-embed vs. API-key mode) carry the block's
 * identity. The width (`containerType` + `widthBoxed`/`widthFullWidth`) and the
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface GoogleMapAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Map.
	location?: string;
	zoom?: number;
	height?: ResponsiveValue< LengthValue >;
	apiKey?: MapApiKeyAttr;
	// Width (boxed vs full-width, like the Container).
	containerType?: 'boxed' | 'full-width';
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One gallery image, resolved to render-ready data (its own caption included). */
export interface GalleryImage {
	id?: number | null;
	url?: string;
	alt?: string;
	caption?: string;
	width?: number | null;
	height?: number | null;
}

/** Colour or gradient overlay laid over each thumbnail, with rest + hover opacity. */
export interface GalleryOverlayAttr {
	type?: 'none' | 'color' | 'gradient';
	color?: ColorPair;
	gradient?: ColorPair;
	opacity?: number;
	hoverOpacity?: number;
}

/** Caption display settings — the text itself comes from each image. */
export interface GalleryCaptionAttr {
	show?: boolean;
	display?: 'overlay' | 'below';
	position?: 'top' | 'center' | 'bottom';
	visibility?: 'always' | 'hover';
	alignment?: 'left' | 'center' | 'right';
}

/** Lightbox appearance (overlay background + whether to show the caption). */
export interface GalleryLightboxAttr {
	background?: ColorPair;
	showCaption?: boolean;
}

/**
 * Full Images Gallery block attributes.
 *
 * The gallery-specific fields (the images, the grid/masonry/tiled layout with
 * per-device columns / gap / tiled row height, image size, aspect-ratio lock,
 * per-image radius and shadow, hover motion, colour/gradient overlay, captions
 * and the click action / lightbox) carry the block's identity. The layout/style
 * fields (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface ImagesGalleryAttributes {
	blockId?: string;
	isExamplePreview?: boolean;
	className?: string;
	// Content.
	images?: GalleryImage[];
	// Layout.
	galleryLayout?: 'grid' | 'masonry' | 'tiled';
	columns?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	tiledHeight?: ResponsiveValue< LengthValue >;
	imageSize?: string;
	aspectRatio?: AspectRatioAttr;
	imageRadius?: ResponsiveValue< LengthValue >;
	imageShadow?: BoxShadowAttr;
	lazyLoad?: boolean;
	// Effects.
	hoverEffect?: string;
	overlay?: GalleryOverlayAttr;
	// Caption.
	caption?: GalleryCaptionAttr;
	captionColor?: ColorPair;
	captionBackground?: ColorPair;
	captionTypography?: ResponsiveValue< TypographyDevice >;
	// Interaction.
	clickAction?: 'none' | 'lightbox' | 'media';
	lightbox?: GalleryLightboxAttr;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Hover colour set (text / background) for a promo call-to-action button. */
export interface PromoButtonHoverAttr {
	text?: ColorPair;
	background?: ColorPair;
}

/**
 * Shared "promo content" attribute shape — the heading, description, a primary
 * and an optional secondary call-to-action button, and the content alignment /
 * gap / max-width. Both the Banner and the CTA blocks extend this so the shared
 * promo panels + the `PromoContent` editor component + the Promo_CSS_Parts CSS
 * generator all read/write one identical shape (guide §4.5: block #2 touches it →
 * hoist once, never copy).
 */
export interface PromoContentAttributes {
	// Heading.
	heading?: string;
	headingTag?: string;
	headingTypography?: ResponsiveValue< TypographyDevice >;
	headingColor?: ColorPair;
	// Description.
	description?: string;
	descriptionTypography?: ResponsiveValue< TypographyDevice >;
	descriptionColor?: ColorPair;
	// Content layout of the text + buttons stack.
	contentAlign?: ResponsiveValue< string >;
	contentGap?: ResponsiveValue< LengthValue >;
	contentMaxWidth?: ResponsiveValue< LengthValue >;
	// Primary button.
	primaryText?: string;
	primaryUrl?: string;
	primaryTarget?: string;
	primaryRel?: string;
	primaryIcon?: ButtonIconAttr;
	primaryTextColor?: ColorPair;
	primaryBgColor?: ColorPair;
	primaryHover?: PromoButtonHoverAttr;
	// Secondary button (optional).
	showSecondary?: boolean;
	secondaryText?: string;
	secondaryUrl?: string;
	secondaryTarget?: string;
	secondaryRel?: string;
	secondaryIcon?: ButtonIconAttr;
	secondaryTextColor?: ColorPair;
	secondaryBgColor?: ColorPair;
	secondaryHover?: PromoButtonHoverAttr;
}

/**
 * Full Banner block attributes.
 *
 * A promotional banner / hero section: the shared promo content (heading,
 * description, primary + optional secondary CTA, content alignment) laid over an
 * emphasised background, with a colour/gradient overlay on top of the background
 * image, a boxed-or-full width and a banner min-height. The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`, `size`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the Container
 * so the shared inspector panels work unchanged.
 */
export interface BannerAttributes extends PromoContentAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Width (boxed vs full-width, like the Container).
	containerType?: 'boxed' | 'full-width';
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Max-width of the content box (wraps the content only, not the background) so a
	// full-bleed banner can align its content with the site grid. Empty = spans the banner.
	contentBoxWidth?: ResponsiveValue< LengthValue >;
	// Overlay laid over the background image (colour or gradient + opacity + blend).
	overlay?: ImageOverlayAttr;
	// Vertical placement of the content inside the banner min-height.
	verticalAlign?: string;
	// Foundational (shared inspector panels read these).
	size?: ResponsiveValue< SizeDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full CTA block attributes.
 *
 * A call-to-action banner: the shared promo content (heading, description,
 * primary + optional secondary CTA) arranged either centred (stacked) or split
 * (text on one side, buttons on the other). The layout/style fields
 * (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`, `size`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the Container
 * so the shared inspector panels work unchanged.
 */
export interface CtaAttributes extends PromoContentAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Arrangement of the text ↔ buttons stack. Named `arrangement` (not `layout`)
	// so it doesn't collide with the Container's flex `layout` object shape when the
	// shared foundational panels are typed against ContainerAttributes.
	arrangement?: 'centered' | 'split';
	// Width (boxed vs full-width, like the Container).
	containerType?: 'boxed' | 'full-width';
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	size?: ResponsiveValue< SizeDevice >;
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/* ---------------------------------------------------------------------------
 * Subscribe Form (flexa/subscribe-form) + its field children
 * (flexa/subscribe-form-email | -name | -phone | -textarea). The parent renders
 * a <form>, owns the submit button, the destination email + confirmation, and
 * the styling of every field / input / message; each child renders one labelled
 * input. All four children share one `SubscribeFieldAttributes` shape and one
 * shared editor panel + PHP render helper (guide §4.5).
 * ------------------------------------------------------------------------- */

/** Shared attribute shape for every subscribe-form field child. */
export interface SubscribeFieldAttributes {
	blockId?: string;
	className?: string;
	/** Visible label text. */
	label?: string;
	/** The submitted field key (`name`); empty → derived from the label. */
	fieldName?: string;
	placeholder?: string;
	required?: boolean;
	showLabel?: boolean;
	/** Field column width inside the form row. */
	width?: '100' | '50';
	/** Textarea row count (ignored by single-line fields). */
	rows?: number;
	/** Choices for the select / radio / checkbox fields (one label per entry). */
	options?: string[];
	/** Fixed value for the hidden field. */
	value?: string;
	/** Accepted file types for the upload field (an `accept` attribute string). */
	accept?: string;
	/** Allow selecting several files (upload field). */
	multiple?: boolean;
	/** Max upload size hint in MB (upload field; the server enforces its own cap). */
	maxSize?: number;
}

/**
 * Full Subscribe Form (parent) block attributes.
 *
 * The form-specific fields (submit label, destination email + subject, success /
 * error messages, confirmation mode, button alignment/width, and the field /
 * label / input / submit / message styling) carry the block's identity. The
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface SubscribeFormAttributes {
	blockId?: string;
	className?: string;
	// Submission.
	submitText?: string;
	toEmail?: string;
	emailSubject?: string;
	successMessage?: string;
	errorMessage?: string;
	confirmationType?: 'message' | 'url';
	redirectUrl?: string;
	// Submit button layout.
	buttonWidth?: 'auto' | 'full';
	buttonAlign?: 'flex-start' | 'center' | 'flex-end';
	// Field layout.
	fieldGap?: ResponsiveValue< LengthValue >;
	// Label + input styling.
	labelColor?: ColorPair;
	inputTextColor?: ColorPair;
	inputPlaceholderColor?: ColorPair;
	inputBackground?: ColorPair;
	inputBorder?: BorderDevice;
	inputPadding?: BoxValue;
	// Submit button styling.
	submitTextColor?: ColorPair;
	submitTextColorHover?: ColorPair;
	submitBackground?: ColorPair;
	submitBackgroundHover?: ColorPair;
	submitPadding?: BoxValue;
	submitRadius?: LengthValue;
	// Success / error message styling.
	successColor?: ColorPair;
	successBackground?: ColorPair;
	errorColor?: ColorPair;
	errorBackground?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Column width a post-filter child takes in the bar. Mirrors Filter_Fields::WIDTHS. */
export type FilterWidth = '100' | '50' | 'auto';

/**
 * Post Filter (parent) block attributes.
 *
 * `targetGridId` is the whole coupling to the grid: an empty value means "the first
 * Post Grid on the page", which is how a bar dropped next to a grid just works. It
 * is provided to the children as the `flexa/filterTarget` block context.
 *
 * All the styling lives here rather than on the children — one place to make the
 * whole bar match the theme, and three child blocks with almost no attributes.
 */
export interface PostFilterAttributes {
	blockId?: string;
	className?: string;
	isExamplePreview?: boolean;
	/** blockId of the Post Grid this bar filters; '' = the first grid on the page. */
	targetGridId?: string;
	/** Accessible name of the search region. */
	formLabel?: string;
	submitText?: string;
	/** Apply filters as they change. The submit button still renders for no-JS visitors. */
	// Layout.
	direction?: 'row' | 'column';
	gap?: ResponsiveValue< LengthValue >;
	align?: 'flex-start' | 'center' | 'flex-end' | 'stretch';
	// Label + control styling (applies to every child).
	labelTypography?: ResponsiveValue< TypographyDevice >;
	labelColor?: ColorPair;
	controlTypography?: ResponsiveValue< TypographyDevice >;
	controlColor?: ColorPair;
	controlPlaceholderColor?: ColorPair;
	controlBackground?: ColorPair;
	controlBorder?: BorderDevice;
	/** Border colour of the focused control. Falls back to the Button background. */
	controlBorderFocus?: ColorPair;
	controlPadding?: BoxValue;
	// Submit button + reset styling.
	buttonColor?: ColorPair;
	buttonBackground?: ColorPair;
	buttonColorHover?: ColorPair;
	buttonBackgroundHover?: ColorPair;
	buttonPadding?: BoxValue;
	buttonRadius?: LengthValue;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Search control (child of flexa/post-filter). */
export interface FilterSearchAttributes {
	blockId?: string;
	className?: string;
	label?: string;
	showLabel?: boolean;
	placeholder?: string;
	width?: FilterWidth;
	/**
	 * This field's own box — overrides the bar's Control panel for THIS field only
	 * (see Filter_Field_CSS / @shared/filter-field-style).
	 */
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
}

/** Taxonomy control (child of flexa/post-filter) — category, tag or any custom taxonomy. */
export interface FilterTaxonomyAttributes {
	blockId?: string;
	className?: string;
	taxonomy?: string;
	label?: string;
	showLabel?: boolean;
	/** `pills` renders the terms as chips that wrap onto as many rows as they need. */
	control?: 'dropdown' | 'checkbox' | 'pills';
	/** Multi-select. Checkbox mode is always multiple; pills default to single-choice. */
	multiple?: boolean;
	/** Label of the "no term selected" option (dropdown + single-choice pills). */
	allTermsLabel?: string;
	/** Append each term's post count. */
	showCount?: boolean;
	hideEmpty?: boolean;
	orderBy?: 'name' | 'count' | 'term_order';
	limit?: number;
	width?: FilterWidth;
	/**
	 * This field's own box — overrides the bar's Control panel for THIS field only
	 * (see Filter_Field_CSS / @shared/filter-field-style).
	 */
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
}

/** Reset control (child of flexa/post-filter). */
export interface FilterResetAttributes {
	blockId?: string;
	className?: string;
	text?: string;
	variant?: 'link' | 'button';
	width?: FilterWidth;
	/**
	 * This field's own box — overrides the bar's Control panel for THIS field only
	 * (see Filter_Field_CSS / @shared/filter-field-style).
	 */
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
}

/**
 * One pricing plan (a column). `features` is a newline-separated string (one
 * feature per line) or an array of lines. `priceYearly` / `periodYearly` are
 * only shown when the monthly/yearly billing toggle is on.
 */
export interface PricingPlan {
	name?: string;
	priceMonthly?: string;
	priceYearly?: string;
	periodMonthly?: string;
	periodYearly?: string;
	features?: string | string[];
	ctaText?: string;
	ctaUrl?: string;
	highlighted?: boolean;
	badge?: string;
}

/**
 * Full Pricing Table block attributes.
 *
 * The block-specific fields (the plan cards, the optional monthly/yearly billing
 * toggle, the column grid, the plan-name / price / period / feature typography
 * and colours, the highlighted-plan accent, the "most popular" badge and the CTA
 * button) carry the block's identity. The layout/style fields (`containerType`/
 * width, `spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged.
 */
export interface PricingTableAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: PricingPlan[];
	// Billing switch. Labels fall back to "Monthly" / "Yearly" when left empty;
	// `billingDefault` picks which period the table opens on.
	billingToggle?: boolean;
	billingMonthlyLabel?: string;
	billingYearlyLabel?: string;
	billingDefault?: 'monthly' | 'yearly';
	billingAlign?: 'flex-start' | 'center' | 'flex-end';
	billingTypography?: ResponsiveValue< TypographyDevice >;
	billingColor?: ColorPair;
	billingActiveColor?: ColorPair;
	billingBackground?: ColorPair;
	billingActiveBackground?: ColorPair;
	billingRadius?: LengthValue;
	// Width (boxed vs full-width, like the Container).
	containerType?: 'boxed' | 'full-width';
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Plan grid.
	columns?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	// Plan name.
	nameTypography?: ResponsiveValue< TypographyDevice >;
	nameColor?: ColorPair;
	// Price + period.
	priceTypography?: ResponsiveValue< TypographyDevice >;
	priceColor?: ColorPair;
	periodColor?: ColorPair;
	// Features.
	featureTypography?: ResponsiveValue< TypographyDevice >;
	featureColor?: ColorPair;
	// Plan card chrome — applies to every card, highlighted or not.
	planBorder?: BorderDevice;
	planBoxShadow?: BoxShadowAttr;
	// Highlighted plan accent + its own border / shadow overrides (each field
	// falls back to the plan-card value above when left empty).
	highlightColor?: ColorPair;
	highlightBorder?: BorderDevice;
	highlightBoxShadow?: BoxShadowAttr;
	// Badge (the "Most popular" ribbon). Background falls back to highlightColor when unset.
	badgeBackground?: ColorPair;
	badgeColor?: ColorPair;
	badgeTypography?: ResponsiveValue< TypographyDevice >;
	// Plan call-to-action button (block-level — one style for every plan CTA).
	// The background + hover background each offer a colour|gradient tab, so each
	// carries a `*Type` discriminator plus a matching `*Gradient` pair.
	buttonTextColor?: ColorPair;
	buttonTextColorHover?: ColorPair;
	buttonBackgroundType?: 'color' | 'gradient';
	buttonBackground?: ColorPair;
	buttonBackgroundGradient?: ColorPair;
	buttonBackgroundHoverType?: 'color' | 'gradient';
	buttonBackgroundHover?: ColorPair;
	buttonBackgroundHoverGradient?: ColorPair;
	buttonRadius?: LengthValue;
	buttonPadding?: BoxValue;
	buttonWidth?: 'auto' | 'full';
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One social link on a team-member card — a chosen icon (via the shared IconPicker) and its link. */
export interface TeamSocialItem {
	icon?: IconValue;
	link?: LinkAttr;
}

/**
 * Full Team Member block attributes.
 *
 * The block-specific fields (the photo and its position/shape/width, the name /
 * role / bio text with per-element typography, colours and bottom spacing, and a
 * row of linked social icons with shared size / gap / tint / hover colours) carry
 * the block's identity. Two layout modes — photo above the text (column) or beside
 * it left/right (row) — are chosen with `imagePosition`, with an optional stack
 * point for the beside modes. The layout/style fields (`spacing`, `background`,
 * `border`, `boxShadow`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface TeamMemberAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Layout.
	imagePosition?: 'top' | 'left' | 'right';
	imageShape?: 'circle' | 'rounded' | 'square';
	stackOn?: 'none' | 'tablet' | 'mobile';
	alignment?: ResponsiveValue< string >;
	mediaGap?: ResponsiveValue< LengthValue >;
	maxWidth?: ResponsiveValue< LengthValue >;
	// Photo.
	image?: ImageMedia;
	imageWidth?: ResponsiveValue< LengthValue >;
	// Name.
	name?: string;
	nameTag?: string;
	nameTypography?: ResponsiveValue< TypographyDevice >;
	nameColor?: ColorPair;
	nameSpacing?: ResponsiveValue< LengthValue >;
	// Role.
	role?: string;
	roleTypography?: ResponsiveValue< TypographyDevice >;
	roleColor?: ColorPair;
	roleSpacing?: ResponsiveValue< LengthValue >;
	// Bio.
	bio?: string;
	bioTypography?: ResponsiveValue< TypographyDevice >;
	bioColor?: ColorPair;
	bioSpacing?: ResponsiveValue< LengthValue >;
	// Social links.
	showSocial?: boolean;
	items?: TeamSocialItem[];
	socialSize?: ResponsiveValue< LengthValue >;
	socialGap?: ResponsiveValue< LengthValue >;
	socialColor?: ColorPair;
	socialHoverColor?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Post ordering field. */
export type PostGridOrderBy = 'date' | 'title' | 'menu_order' | 'rand' | 'comment_count';

/**
 * Full Post Grid block attributes.
 *
 * A DYNAMIC block: the grid is produced server-side by a WP_Query in render.php
 * (save returns null); the editor previews the query live via getEntityRecords.
 * The query fields (post type, count, order, taxonomy filter) and the element
 * toggles carry the block's identity; the layout/style fields (`containerType`/
 * width, `spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`, `animation`) reuse the same shapes as
 * the Container so the shared inspector panels work unchanged.
 */
/**
 * Shared pagination attributes — the subset read/written by the shared
 * `PaginationPanel` + `PaginationNav` (used by Post Grid and RSS). Per-page lives
 * on each block (Post Grid: `postsPerPage`; RSS: `perPage`) and is passed to the
 * panel separately, so it is not part of this shared shape.
 */
export interface PaginationAttributes {
	paginationType?: 'none' | 'numbered' | 'loadmore';
	loadMoreText?: string;
	paginationAlign?: 'left' | 'center' | 'right';
	prevLabel?: string;
	nextLabel?: string;
	paginationColor?: ColorPair;
	paginationActiveColor?: ColorPair;
	paginationBackground?: ColorPair;
	paginationActiveBackground?: ColorPair;
	paginationRadius?: LengthValue;
	paginationFontSize?: LengthValue;
	loadMoreColor?: ColorPair;
	loadMoreBackground?: ColorPair;
}

export interface PostGridAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	// Query.
	postType?: string;
	postsPerPage?: number;
	offset?: number;
	orderBy?: PostGridOrderBy;
	order?: 'asc' | 'desc';
	taxonomy?: string;
	terms?: string;
	excludeCurrent?: boolean;
	/**
	 * Where a visitor's search (from a Post Filter) looks. `title_excerpt` searches
	 * only what a human wrote; `all` is WordPress's own behaviour, which searches the
	 * raw post_content — block markup included, so "container" matches every post
	 * that merely contains a container block.
	 */
	searchScope?: 'title_excerpt' | 'all';
	/** Show the "N posts found" line. It is always rendered for screen readers. */
	showResultCount?: boolean;
	/** Wording of that line. `%s` is the number. Empty = "%s posts found". */
	resultCountText?: string;
	resultCountTypography?: ResponsiveValue< TypographyDevice >;
	resultCountColor?: ColorPair;
	resultCountAlign?: '' | 'left' | 'center' | 'right';
	// Layout.
	columns?: ResponsiveValue< LengthValue >;
	rowGap?: ResponsiveValue< LengthValue >;
	columnGap?: ResponsiveValue< LengthValue >;
	equalHeight?: boolean;
	contentAlign?: ResponsiveValue< string >;
	/** Inner padding of each card's text/body area (the image stays full-bleed). */
	contentPadding?: ResponsiveValue< BoxValue >;
	/** Vertical gap between meta / title / excerpt / button inside the card body. */
	contentGap?: ResponsiveValue< LengthValue >;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Element toggles.
	showImage?: boolean;
	showTitle?: boolean;
	titleTag?: string;
	showMeta?: boolean;
	showAuthor?: boolean;
	showAvatar?: boolean;
	avatarSize?: number;
	showDate?: boolean;
	showComments?: boolean;
	showTaxonomy?: boolean;
	showExcerpt?: boolean;
	excerptLength?: number;
	showReadMore?: boolean;
	readMoreText?: string;
	// Image.
	imageSize?: string;
	imageRatio?: string;
	// Pagination.
	paginationType?: 'none' | 'numbered' | 'loadmore';
	loadMoreText?: string;
	// Typography + colours.
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	metaTypography?: ResponsiveValue< TypographyDevice >;
	metaColor?: ColorPair;
	excerptTypography?: ResponsiveValue< TypographyDevice >;
	excerptColor?: ColorPair;
	cardBackground?: ColorPair;
	// Read-more button styling.
	buttonAlign?: 'left' | 'center' | 'right';
	buttonWidth?: 'auto' | 'full';
	buttonTextColor?: ColorPair;
	buttonBackground?: ColorPair;
	buttonTextColorHover?: ColorPair;
	buttonBackgroundHover?: ColorPair;
	buttonTypography?: ResponsiveValue< TypographyDevice >;
	buttonRadius?: LengthValue;
	buttonPadding?: BoxValue;
	// Pagination labels + styling.
	prevLabel?: string;
	nextLabel?: string;
	paginationAlign?: 'left' | 'center' | 'right';
	paginationColor?: ColorPair;
	paginationActiveColor?: ColorPair;
	paginationBackground?: ColorPair;
	paginationActiveBackground?: ColorPair;
	paginationRadius?: LengthValue;
	paginationFontSize?: LengthValue;
	loadMoreColor?: ColorPair;
	loadMoreBackground?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full RSS block attributes.
 *
 * The block-specific fields (the feed source + item count + cache + sort +
 * open-in-new-tab, the list/grid layout with per-device columns and gaps, the
 * item toggles for thumbnail/title/date/author/source/excerpt/read-more, and the
 * card typography and colours) carry the block's identity. The layout/style
 * fields (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged.
 */
export interface RssAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	// Source.
	feedUrl?: string;
	itemsToShow?: number;
	cacheTime?: number;
	sortNewestFirst?: boolean;
	openInNewTab?: boolean;
	// Layout.
	feedLayout?: 'grid' | 'list';
	columns?: ResponsiveValue< LengthValue >;
	rowGap?: ResponsiveValue< LengthValue >;
	columnGap?: ResponsiveValue< LengthValue >;
	equalHeight?: boolean;
	contentAlign?: ResponsiveValue< string >;
	/** Inner padding of each card's text/body area (the thumbnail stays full-bleed). */
	contentPadding?: ResponsiveValue< BoxValue >;
	/** Vertical gap between the meta / title / excerpt / button inside a card. */
	contentGap?: ResponsiveValue< LengthValue >;
	// Pagination (client-side over the fetched pool).
	paginationType?: 'none' | 'numbered' | 'loadmore';
	perPage?: number;
	loadMoreText?: string;
	paginationAlign?: 'left' | 'center' | 'right';
	prevLabel?: string;
	nextLabel?: string;
	paginationColor?: ColorPair;
	paginationActiveColor?: ColorPair;
	paginationBackground?: ColorPair;
	paginationActiveBackground?: ColorPair;
	paginationRadius?: LengthValue;
	paginationFontSize?: LengthValue;
	loadMoreColor?: ColorPair;
	loadMoreBackground?: ColorPair;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Item toggles.
	showImage?: boolean;
	imageRatio?: string;
	showTitle?: boolean;
	titleTag?: string;
	showMeta?: boolean;
	showDate?: boolean;
	showAuthor?: boolean;
	showSource?: boolean;
	showExcerpt?: boolean;
	excerptLength?: number;
	showReadMore?: boolean;
	readMoreText?: string;
	// Typography + colours.
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	metaTypography?: ResponsiveValue< TypographyDevice >;
	metaColor?: ColorPair;
	excerptTypography?: ResponsiveValue< TypographyDevice >;
	excerptColor?: ColorPair;
	cardBackground?: ColorPair;
	// Read-more button styling.
	buttonAlign?: 'left' | 'center' | 'right';
	buttonWidth?: 'auto' | 'full';
	buttonTextColor?: ColorPair;
	buttonBackground?: ColorPair;
	buttonTextColorHover?: ColorPair;
	buttonBackgroundHover?: ColorPair;
	buttonTypography?: ResponsiveValue< TypographyDevice >;
	buttonRadius?: LengthValue;
	buttonPadding?: BoxValue;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Progress Bar block attributes.
 *
 * The block-specific fields (layout, the value as a percentage or absolute
 * count, the title + counter readout, the bar geometry, the fill style and the
 * fill / track colours, plus the on-scroll fill animation) carry the block's
 * identity. The layout/style fields (`maxWidth`, `alignment`, `spacing`,
 * `background`, `border`, `boxShadow`, `advancedLayout`, `responsiveVisibility`,
 * `htmlAttributes`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface ProcessBarAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Layout.
	barType?: 'line' | 'circle' | 'semicircle';
	// Value.
	valueType?: 'percent' | 'absolute';
	value?: number;
	max?: number;
	// Title.
	showTitle?: boolean;
	title?: string;
	titleTag?: string;
	titlePosition?: 'above' | 'below';
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	// Counter readout.
	showCounter?: boolean;
	counterTypography?: ResponsiveValue< TypographyDevice >;
	counterColor?: ColorPair;
	dividerChar?: string;
	// Bar geometry.
	barHeight?: ResponsiveValue< LengthValue >;
	circleSize?: ResponsiveValue< LengthValue >;
	strokeWidth?: ResponsiveValue< LengthValue >;
	barRadius?: LengthValue;
	fillStyle?: 'solid' | 'striped' | 'striped-animated';
	lineCap?: 'round' | 'square' | 'butt';
	// Colours.
	fillColor?: ColorPair;
	trackColor?: ColorPair;
	// Fill animation (view.ts reads these; they emit no CSS).
	animateFill?: boolean;
	fillDuration?: number;
	// Foundational (shared inspector panels read these).
	maxWidth?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One timeline entry — a title, description, date, an optional image and a marker icon. */
export interface TimelineItem {
	title?: string;
	description?: string;
	date?: string;
	icon?: IconValue;
	image?: ImageMedia;
}

/**
 * Full Timeline block attributes.
 *
 * The timeline-specific fields (the chronological entries, the alternate/left/
 * right arrangement, the connector line, the marker node, the date/title/
 * description styling and the layout spacing) carry the block's identity. The
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface TimelineAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: TimelineItem[];
	// Layout.
	timelineLayout?: 'alternate' | 'left' | 'right';
	/** Where a per-entry image sits within its content box, relative to the text. */
	imagePosition?: 'top' | 'bottom';
	/** Horizontal alignment of the per-entry image within the card. */
	imageAlign?: 'left' | 'center' | 'right';
	/** Per-entry image display width (shared across entries). */
	imageWidth?: ResponsiveValue< LengthValue >;
	itemGap?: ResponsiveValue< LengthValue >;
	markerGap?: ResponsiveValue< LengthValue >;
	contentAlign?: ResponsiveValue< string >;
	maxWidth?: ResponsiveValue< LengthValue >;
	/** Inner padding of each event's card (where the border/shadow sit). */
	cardPadding?: ResponsiveValue< BoxValue >;
	// Connector line.
	connectorShow?: boolean;
	connectorColor?: ColorPair;
	connectorWidth?: ResponsiveValue< LengthValue >;
	connectorStyle?: 'solid' | 'dashed' | 'dotted';
	// Marker / node.
	markerShape?: 'circle' | 'square' | 'rounded';
	markerSize?: ResponsiveValue< LengthValue >;
	markerColor?: ColorPair;
	markerIconColor?: ColorPair;
	// Date.
	datePosition?: 'inline' | 'above';
	dateTypography?: ResponsiveValue< TypographyDevice >;
	dateColor?: ColorPair;
	// Title + description.
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	descriptionTypography?: ResponsiveValue< TypographyDevice >;
	descriptionColor?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/* ---------------------------------------------------------------------------
 * Icon (flexa/icon) — a single SVG glyph (library or uploaded) with an optional
 * frame (background / border / shape), hover colours, alignment and a link. The
 * icon resolves to render-ready data via the shared IconValue / IconPicker.
 * ------------------------------------------------------------------------- */

/**
 * Full Icon block attributes.
 *
 * The icon-specific fields (the chosen glyph, its per-device size, the
 * square/rounded/circle shape, the icon and frame colours with hover, the frame
 * padding/border and an optional link) carry the block's identity. The
 * layout/style fields (`spacing`, `background`,
 * `border`, `boxShadow`, `advancedLayout`, `responsiveVisibility`,
 * `htmlAttributes`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface IconAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Icon.
	icon?: IconValue;
	iconSize?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	// Frame.
	shape?: 'square' | 'rounded' | 'circle';
	iconColor?: ColorPair;
	iconColorHover?: ColorPair;
	iconBackground?: ColorPair;
	iconBackgroundHover?: ColorPair;
	iconBorderColor?: ColorPair;
	iconBorderColorHover?: ColorPair;
	iconBorderWidth?: ResponsiveValue< LengthValue >;
	iconPadding?: ResponsiveValue< LengthValue >;
	// Link.
	link?: LinkAttr;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One icon-list entry — a glyph, its text and an optional link. */
export interface IconListItem {
	id?: string;
	icon?: IconValue;
	text?: string;
	link?: LinkAttr;
}

/**
 * Full Icon List block attributes.
 *
 * The icon-list-specific fields (the list/grid items, the list-vs-grid layout
 * with per-device columns, the icon-before/after position, item and icon gaps,
 * the block-level icon styling — size, view, shape, colours with hover, frame —
 * and the text typography and colours) carry the block's identity. The
 * layout/style fields (`spacing`, `background`, `border`, `boxShadow`,
 * `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse the same
 * shapes as the Container so the shared inspector panels work unchanged.
 */
export interface IconListAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	items?: IconListItem[];
	// Layout.
	view?: 'list' | 'grid';
	columns?: ResponsiveValue< LengthValue >;
	iconPosition?: 'before' | 'after';
	alignment?: ResponsiveValue< string >;
	gap?: ResponsiveValue< LengthValue >;
	iconGap?: ResponsiveValue< LengthValue >;
	// Icon styling (block-level).
	iconSize?: ResponsiveValue< LengthValue >;
	iconView?: 'default' | 'stacked' | 'framed';
	iconShape?: 'square' | 'rounded' | 'circle';
	iconColor?: ColorPair;
	iconColorHover?: ColorPair;
	iconBackground?: ColorPair;
	iconBackgroundHover?: ColorPair;
	iconBorderColor?: ColorPair;
	iconPadding?: ResponsiveValue< LengthValue >;
	// Text.
	typography?: ResponsiveValue< TypographyDevice >;
	textColor?: ColorPair;
	textColorHover?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Star Rating block attributes.
 *
 * The rating-specific fields (the rating value out of a max range, the star
 * size and gap, the marked/unmarked colours, the inline-vs-stacked layout with
 * an optional title/label — its position, gap, typography and colour — and an
 * optional aggregateRating schema) carry the block's identity. The layout/style
 * fields (`spacing`, `background`, `border`, `boxShadow`, `advancedLayout`,
 * `responsiveVisibility`, `htmlAttributes`) reuse the same shapes as the
 * Container so the shared inspector panels work unchanged.
 */
export interface StarRatingAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Rating.
	rating?: number;
	maxRating?: number;
	starSize?: ResponsiveValue< LengthValue >;
	gap?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	color?: ColorPair;
	unmarkedColor?: ColorPair;
	// Layout + title.
	ratingLayout?: 'inline' | 'stacked';
	showTitle?: boolean;
	title?: string;
	titlePosition?: 'before' | 'after';
	titleGap?: ResponsiveValue< LengthValue >;
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	// SEO.
	enableSchema?: boolean;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Modal block attributes.
 *
 * The modal-specific fields (the trigger — button/text/image/icon — and its
 * styling, the modal box size / padding / background / radius, the overlay
 * colour, the close button, and the show-once / close-on-overlay / close-on-esc
 * behaviour) carry the block's identity. The modal content is InnerBlocks. The
 * style fields (`spacing`, `border`, `boxShadow`, `responsiveVisibility`,
 * `htmlAttributes`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged. `border`/`boxShadow` style the modal box.
 */
export interface ModalAttributes {
	blockId?: string;
	className?: string;
	// Trigger.
	triggerType?: 'button' | 'text' | 'image' | 'icon';
	triggerText?: string;
	triggerIcon?: IconValue;
	triggerImage?: ImageMedia;
	triggerAlign?: ResponsiveValue< string >;
	triggerTypography?: ResponsiveValue< TypographyDevice >;
	triggerTextColor?: ColorPair;
	triggerTextColorHover?: ColorPair;
	triggerBackground?: ColorPair;
	triggerBackgroundHover?: ColorPair;
	triggerPadding?: ResponsiveValue< BoxValue >;
	triggerRadius?: LengthValue;
	triggerIconSize?: LengthValue;
	// Modal box.
	modalWidth?: ResponsiveValue< LengthValue >;
	modalMaxHeight?: ResponsiveValue< LengthValue >;
	modalPadding?: ResponsiveValue< BoxValue >;
	modalBackground?: ColorPair;
	modalRadius?: LengthValue;
	// Overlay + close.
	overlayColor?: ColorPair;
	closeIconSize?: LengthValue;
	closeIconColor?: ColorPair;
	closePosition?: 'inside' | 'outside';
	// Behaviour (view.ts reads these).
	showOnce?: boolean;
	closeOnOverlay?: boolean;
	closeOnEsc?: boolean;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Lottie Animation block attributes.
 *
 * The lottie-specific fields (the JSON/.lottie source — uploaded file or remote
 * URL — the playback options loop/speed/reverse and the play trigger, the
 * per-device width/height and alignment, and a hover background tint) carry the
 * block's identity; playback is driven on the front end by lottie-web in
 * view.ts (these emit no CSS). The layout/style fields (`spacing`,
 * `background`, `border`, `boxShadow`, `advancedLayout`, `responsiveVisibility`,
 * `htmlAttributes`) reuse the same shapes as the Container so the shared
 * inspector panels work unchanged.
 */
export interface LottieAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Source.
	source?: 'url' | 'file';
	fileUrl?: string;
	fileId?: number | null;
	url?: string;
	// Playback (view.ts reads these; they emit no CSS).
	loop?: boolean;
	speed?: number;
	reverse?: boolean;
	playOn?: 'autoplay' | 'none' | 'hover' | 'click' | 'scroll' | 'viewport';
	// Size + alignment.
	width?: ResponsiveValue< LengthValue >;
	height?: ResponsiveValue< LengthValue >;
	alignment?: ResponsiveValue< string >;
	backgroundColorHover?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Notice block attributes.
 *
 * The notice-specific fields (the severity type preset, dismissible behaviour,
 * icon, title + message text, and the per-element typography / colours) carry the
 * block's identity. The layout/style fields (`spacing`, `background`, `border`,
 * `boxShadow`, `advancedLayout`, `responsiveVisibility`, `htmlAttributes`) reuse
 * the same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface NoticeAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Type preset (drives the accent + default icon).
	noticeType?: 'info' | 'success' | 'warning' | 'danger' | 'default';
	// Overrides the per-type accent bar (left border) colour when set.
	accentColor?: ColorPair;
	// Behaviour.
	dismissible?: boolean;
	rememberDismiss?: boolean;
	// Icon.
	showIcon?: boolean;
	icon?: IconValue;
	iconPosition?: 'left' | 'top';
	iconSize?: ResponsiveValue< LengthValue >;
	iconColor?: ColorPair;
	// Title.
	showTitle?: boolean;
	title?: string;
	titleTag?: string;
	titleTypography?: ResponsiveValue< TypographyDevice >;
	titleColor?: ColorPair;
	// Message body.
	content?: string;
	contentTypography?: ResponsiveValue< TypographyDevice >;
	contentColor?: ColorPair;
	// Layout.
	alignment?: ResponsiveValue< string >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Facebook Feed block attributes.
 *
 * The block-specific fields (the Page access token + id source, the grid / list /
 * masonry / carousel layout, the content toggles for profile / page name /
 * timestamp / message / reactions / comments / shares, the client-side pagination
 * over the fetched pool, and the card typography and colours) carry the block's
 * identity. The layout/style fields reuse the same shapes as the Container so the
 * shared inspector panels work unchanged. Posts are fetched server-side by
 * `Flexa\Block\Facebook_Feed`; the token never reaches the browser.
 */
export interface FacebookFeedAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	// Source — the access token lives in the admin-only server-side Feed_Tokens
	// store, NOT on the block, so it never enters post content.
	pageId?: string;
	numberOfPosts?: number;
	sortNewestFirst?: boolean;
	cacheTime?: number;
	// Layout.
	feedLayout?: 'grid' | 'list' | 'masonry' | 'carousel';
	columns?: ResponsiveValue< LengthValue >;
	itemGap?: ResponsiveValue< LengthValue >;
	/** Padding inside each post card. */
	contentPadding?: ResponsiveValue< BoxValue >;
	/** Vertical gap between the parts of a post card (header/image/message/actions). */
	contentGap?: ResponsiveValue< LengthValue >;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Content toggles.
	showAvatar?: boolean;
	showPageName?: boolean;
	showTimestamp?: boolean;
	showImage?: boolean;
	showMessage?: boolean;
	messageLimit?: number;
	showReactions?: boolean;
	showComments?: boolean;
	showShares?: boolean;
	enableLink?: boolean;
	openInNewTab?: boolean;
	// Pagination (client-side over the fetched pool — mirrors RSS).
	paginationType?: 'none' | 'numbered' | 'loadmore';
	perPage?: number;
	loadMoreText?: string;
	paginationAlign?: 'left' | 'center' | 'right';
	prevLabel?: string;
	nextLabel?: string;
	paginationColor?: ColorPair;
	paginationActiveColor?: ColorPair;
	paginationBackground?: ColorPair;
	paginationActiveBackground?: ColorPair;
	paginationRadius?: LengthValue;
	paginationFontSize?: LengthValue;
	loadMoreColor?: ColorPair;
	loadMoreBackground?: ColorPair;
	// Card typography + colours.
	headerTypography?: ResponsiveValue< TypographyDevice >;
	headerColor?: ColorPair;
	messageTypography?: ResponsiveValue< TypographyDevice >;
	messageColor?: ColorPair;
	metaTypography?: ResponsiveValue< TypographyDevice >;
	metaColor?: ColorPair;
	cardBackground?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Instagram Feed block attributes.
 *
 * The block-specific fields (the access-token source, the grid / overlay / card /
 * carousel layout, the square-thumbnail and caption / meta / profile toggles, the
 * hover overlay colour, and the caption typography and colours) carry the block's
 * identity. The layout/style fields reuse the same shapes as the Container so the
 * shared inspector panels work unchanged. Media is fetched server-side by
 * `Flexa\Block\Instagram_Feed`; the token never reaches the browser.
 */
export interface InstagramFeedAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	containerType?: 'boxed' | 'full-width';
	// Source — the access token lives in the admin-only server-side Feed_Tokens
	// store, NOT on the block, so it never enters post content.
	numberOfImages?: number;
	sortNewestFirst?: boolean;
	cacheTime?: number;
	// Layout.
	feedLayout?: 'grid' | 'overlay' | 'card' | 'carousel';
	columns?: ResponsiveValue< LengthValue >;
	itemGap?: ResponsiveValue< LengthValue >;
	squareThumbnail?: boolean;
	/** Padding inside each item's text area (caption/meta/profile). */
	contentPadding?: ResponsiveValue< BoxValue >;
	/** Vertical gap between the caption, meta and profile inside an item. */
	contentGap?: ResponsiveValue< LengthValue >;
	widthBoxed?: ResponsiveValue< LengthValue >;
	widthFullWidth?: ResponsiveValue< LengthValue >;
	// Content toggles.
	showCaption?: boolean;
	captionLimit?: number;
	showMeta?: boolean;
	showProfile?: boolean;
	enableLink?: boolean;
	openInNewTab?: boolean;
	// Overlay + card styling.
	overlayColor?: ColorPair;
	captionTypography?: ResponsiveValue< TypographyDevice >;
	captionColor?: ColorPair;
	metaTypography?: ResponsiveValue< TypographyDevice >;
	metaColor?: ColorPair;
	cardBackground?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/**
 * Full Taxonomy block attributes.
 *
 * The block-specific fields (which taxonomy to list, the list / inline / dropdown
 * / grid display, the count / empty / hierarchy toggles, the term limit + order,
 * the prefix/suffix affixes and separator, and the per-term / count / hover
 * styling) carry the block's identity. Terms are queried server-side at render
 * time. The layout/style fields reuse the same shapes as the Container so the
 * shared inspector panels work unchanged.
 */
export interface TaxonomyAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Source.
	taxonomy?: string;
	// Display.
	displayStyle?: 'list' | 'inline' | 'dropdown' | 'grid';
	columns?: ResponsiveValue< LengthValue >;
	orderBy?: 'name' | 'count' | 'slug';
	order?: 'asc' | 'desc';
	showCount?: boolean;
	showEmpty?: boolean;
	hierarchical?: boolean;
	limit?: number;
	// Affixes.
	prefixType?: 'none' | 'icon' | 'text';
	prefixIcon?: IconValue;
	prefixText?: string;
	suffixType?: 'none' | 'icon' | 'text';
	suffixIcon?: IconValue;
	suffixText?: string;
	showSeparator?: boolean;
	separator?: string;
	// Layout.
	alignment?: ResponsiveValue< string >;
	gap?: ResponsiveValue< LengthValue >;
	itemPadding?: ResponsiveValue< BoxValue >;
	itemBorderRadius?: LengthValue;
	// Typography + colours.
	typography?: ResponsiveValue< TypographyDevice >;
	itemColor?: ColorPair;
	itemBackground?: ColorPair;
	itemHoverColor?: ColorPair;
	itemHoverBackground?: ColorPair;
	countColor?: ColorPair;
	prefixColor?: ColorPair;
	suffixColor?: ColorPair;
	/** Prefix/suffix icon colour when the chip is hovered. */
	iconHoverColor?: ColorPair;
	separatorColor?: ColorPair;
	prefixIconSize?: ResponsiveValue< LengthValue >;
	suffixIconSize?: ResponsiveValue< LengthValue >;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** One data-table column header — its label and per-column text alignment. */
export interface DataTableColumn {
	label?: string;
	align?: 'left' | 'center' | 'right';
}

/**
 * One data-table body row — a flat list of cell values (index-aligned to columns).
 * The key is `values` (not `cells`) so the shared inline-editor 2-axis cell handler
 * — which writes `rows[row].values[col]` — can edit each cell on the front end.
 */
export interface DataTableRow {
	values?: string[];
}

/**
 * Full Data Table block attributes.
 *
 * The block-specific fields (the column headers + body rows, the header on/off +
 * styling, zebra striping and row hover, per-column alignment, the cell borders
 * and padding, the first-column highlight and the max width that makes wider
 * content scroll horizontally) carry the block's identity. The layout/style fields
 * reuse the same shapes as the Container so the shared inspector panels work unchanged.
 */
export interface DataTableAttributes {
	blockId?: string;
	className?: string;
	htmlTag?: string;
	// Content.
	columns?: DataTableColumn[];
	rows?: DataTableRow[];
	// Layout — caps the table's width; wider content scrolls horizontally.
	maxWidth?: LengthValue;
	// Header.
	showHeader?: boolean;
	headerBackground?: ColorPair;
	headerColor?: ColorPair;
	/** Header text colour when the header row is hovered. */
	headerColorHover?: ColorPair;
	headerTypography?: ResponsiveValue< TypographyDevice >;
	// Body.
	striped?: boolean;
	stripedColor?: ColorPair;
	hoverHighlight?: boolean;
	hoverColor?: ColorPair;
	cellTypography?: ResponsiveValue< TypographyDevice >;
	cellColor?: ColorPair;
	/** Body-cell text colour when its row is hovered. */
	cellColorHover?: ColorPair;
	cellPadding?: ResponsiveValue< BoxValue >;
	// Cell borders.
	showCellBorders?: boolean;
	cellBorderColor?: ColorPair;
	cellBorderWidth?: LengthValue;
	// First-column highlight.
	firstColumnHighlight?: boolean;
	firstColumnBackground?: ColorPair;
	firstColumnColor?: ColorPair;
	// Foundational (shared inspector panels read these).
	spacing?: ResponsiveValue< SpacingDevice >;
	background?: BackgroundAttr;
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	advancedLayout?: ResponsiveValue< AdvancedLayoutDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
	animation?: AnimationAttr;
	htmlAttributes?: HtmlAttributesAttr;
}

/** Props for a panel/control that edits block attributes. */
export interface PanelProps< T = ContainerAttributes > {
	attributes: T;
	setAttributes: ( attrs: Partial< T > ) => void;
	initialOpen?: boolean;
}

/** Props for the block edit component. */
export interface EditProps< T = ContainerAttributes > {
	attributes: T;
	setAttributes: ( attrs: Partial< T > ) => void;
	clientId: string;
	/** Values a parent block provides via `providesContext` (the filter children use
	 *  `flexa/filterTarget`). Absent for blocks that declare no `usesContext`. */
	context?: Record< string, unknown >;
}

/** An option in a segmented / select control. */
export interface ControlOption {
	value: string;
	label: string;
	icon?: unknown;
}
