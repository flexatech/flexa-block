/**
 * Social Icons block — platform catalog (editor side).
 *
 * The brand/mono catalog now lives in the shared store (`@utils` →
 * social-platforms) so any block that renders brand marks reuses one source.
 * This block adds only the reserved "custom" key for a user-picked icon.
 *
 * @package Flexa\Block
 */

export { SOCIAL_PLATFORMS, getPlatform, MonoIcon } from '@utils';
export type { SocialPlatform } from '@utils';

/**
 * Reserved platform key for a user-picked icon (WordPress library or uploaded
 * SVG) rather than one of the built-in brand marks. Such an item stores its
 * chosen glyph in `item.icon` and always renders tinted (currentColor).
 */
export const CUSTOM_KEY = 'custom';
