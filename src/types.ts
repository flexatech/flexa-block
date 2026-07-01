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
	zIndex?: string;
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
	responsiveVisibility?: ResponsiveVisibilityAttr;
}

/** Full Button block attributes. */
export interface ButtonAttributes {
	blockId?: string;
	className?: string;
	text?: string;
	url?: string;
	linkTarget?: string;
	rel?: string;
	variant?: 'fill' | 'outline' | 'ghost';
	sizeVariant?: 'sm' | 'md' | 'lg';
	align?: 'left' | 'center' | 'right';
	iconName?: string;
	iconPosition?: 'left' | 'right';
	textColor?: ColorPair;
	bgColor?: ColorPair;
	hoverTextColor?: ColorPair;
	hoverBgColor?: ColorPair;
	radius?: LengthValue;
	spacing?: ResponsiveValue< SpacingDevice >;
	responsiveVisibility?: ResponsiveVisibilityAttr;
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
}

/** An option in a segmented / select control. */
export interface ControlOption {
	value: string;
	label: string;
	icon?: unknown;
}
