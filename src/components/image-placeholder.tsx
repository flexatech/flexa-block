/**
 * Shared "no image" placeholder — the editor mirror of
 * `HTML_Helpers::image_placeholder()`. A framed picture glyph shown in place of a
 * missing thumbnail so media cards (Post Grid, RSS) keep a consistent layout. The
 * glyph inherits the surrounding colour; structural styling comes from the shared
 * `.flexa-image-placeholder` CSS. Pass `style` (e.g. the card's `aspectRatio`) to
 * match the block's chosen image ratio.
 *
 * @package Flexa\Block
 */

import { cn, type CssProps } from '@utils';

type ImagePlaceholderProps = {
	className?: string;
	style?: CssProps;
};

/**
 * Neutral picture-glyph placeholder for a missing image.
 */
export const ImagePlaceholder = ( { className, style }: ImagePlaceholderProps ): JSX.Element => (
	<span className={ cn( 'flexa-image-placeholder', className ) } style={ style } aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" focusable="false">
			<rect x="3" y="3" width="18" height="18" rx="2" />
			<circle cx="8.5" cy="8.5" r="1.5" />
			<path d="m21 15-5-5L5 21" />
		</svg>
	</span>
);
