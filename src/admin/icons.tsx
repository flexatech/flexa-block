/**
 * Small glyphs shared by the dashboard's block cards.
 *
 * They live here rather than beside their first use because both `blocks-panel`
 * and `block-thumb` need them, and `blocks-panel` already imports `block-thumb`
 * — putting them in either file would make the two import each other.
 *
 * Both are decorative: the card's badge names where a block comes from and the
 * lock's wrapper carries a title attribute, so the glyphs are hidden from
 * assistive tech rather than repeating that.
 */

/**
 * Crown — marks a block contributed by a paid add-on.
 * @param root0
 * @param root0.className Optional class, used to size it per context.
 */
export function CrownIcon( {
	className,
}: {
	className?: string;
} ): JSX.Element {
	return (
		<svg
			className={ className }
			viewBox="0 0 24 24"
			fill="currentColor"
			aria-hidden="true"
			focusable="false"
		>
			<path d="M2.6 8.1a1.2 1.2 0 0 1 1.9-.2L7.4 11l3.1-5.4a1.7 1.7 0 0 1 3 0L16.6 11l2.9-3.1a1.2 1.2 0 0 1 2 1.1l-1.8 8.1a1.6 1.6 0 0 1-1.6 1.3H5.9a1.6 1.6 0 0 1-1.6-1.3L2.5 9a1.2 1.2 0 0 1 .1-.9Z" />
		</svg>
	);
}

/**
 * Padlock — stands where a switch would be on a card that cannot be switched.
 * @param root0
 * @param root0.className Optional class, used to size it per context.
 */
export function LockIcon( {
	className,
}: {
	className?: string;
} ): JSX.Element {
	return (
		<svg
			className={ className }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.8"
			strokeLinecap="round"
			aria-hidden="true"
			focusable="false"
		>
			<rect x="4.5" y="10.5" width="15" height="10" rx="2.2" />
			<path d="M8 10.5V7.6a4 4 0 0 1 8 0v2.9" />
		</svg>
	);
}
