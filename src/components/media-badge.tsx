/**
 * Media-type badge — a small chip laid over a feed thumbnail marking a video or an
 * album / carousel. Shared by the Facebook and Instagram feed editors; mirrors the
 * PHP `HTML_Helpers::media_badge()` used on the front end. Renders nothing for a
 * plain single image.
 *
 * @package Flexa\Block
 */

import { cn } from '@utils';

interface MediaBadgeProps {
	/** Normalised media type: 'video' | 'album' | anything else (no badge). */
	type?: string;
	/** Extra class(es) for positioning within the item. */
	className?: string;
}

/**
 * A play / album glyph chip, or null for a single image.
 */
export const MediaBadge = ( { type, className }: MediaBadgeProps ): JSX.Element | null => {
	if ( type === 'video' ) {
		return (
			<span className={ cn( 'flexa-media-badge', className ) } aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M8 5v14l11-7z" /></svg>
			</span>
		);
	}
	if ( type === 'album' ) {
		return (
			<span className={ cn( 'flexa-media-badge', className ) } aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" focusable="false">
					<rect x="8" y="8" width="12" height="12" rx="2" />
					<path d="M4 16V6a2 2 0 0 1 2-2h10" />
				</svg>
			</span>
		);
	}
	return null;
};
