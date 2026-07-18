/**
 * Block thumbnail — an illustrative preview tile for a block card.
 *
 * There are no real block screenshots, so each card gets a lightweight,
 * self-contained "visual": a group-tinted gradient banner with faint layout
 * hints and the block's own glyph on a floating white chip. The gradient and
 * accent come from the block's group (see block-groups), so related blocks read
 * as a family and the grid gains colour without shipping any image assets.
 */

import { BLOCK_ICONS } from '@shared/block-icons';
import { groupMeta, groupOf } from './block-groups';

/**
 * A decorative, non-interactive preview banner for one block.
 * @param root0
 * @param root0.block The block to illustrate.
 */
export function BlockThumb( {
	block,
}: {
	block: FlexaBlockAdminBlock;
} ): JSX.Element {
	const meta = groupMeta( groupOf( block ) );
	const icon = BLOCK_ICONS[ block.slug ];

	return (
		<div
			className="flexa-thumb"
			style={ {
				background: `linear-gradient(135deg, ${ meta.from } 0%, ${ meta.to } 100%)`,
			} }
			aria-hidden="true"
		>
			<span className="flexa-thumb__glow" />
			{ /* Faint bars that read as "content" behind the glyph. */ }
			<span className="flexa-thumb__bar flexa-thumb__bar--a" />
			<span className="flexa-thumb__bar flexa-thumb__bar--b" />
			<span className="flexa-thumb__chip" style={ { color: meta.accent } }>
				{ icon ? (
					icon.src
				) : (
					<span className="dashicons dashicons-screenoptions" />
				) }
			</span>
		</div>
	);
}
