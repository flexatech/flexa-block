/**
 * Reuse audit — fails the build when a block copies a helper/option-set that
 * already lives in the shared kho (@utils / @components / CSS_Helpers) instead of
 * importing it. Enforces docs/huong-dan-nhan-ban-block.md §4.1–§4.4 for the
 * pattern-detectable cases: copied helpers (cn, applyTypography, apply* preview,
 * add_typography/open_device/close_device …) and copied alignment option arrays
 * (icon: Align*Icon inside src/blocks → import CONTENT_ALIGN_OPTIONS /
 * TEXT_ALIGN_OPTIONS instead).
 *
 * NOT covered here (no reliable text signal): whole-panel duplication that only
 * differs by name (ColorsPanel↔TextColorsPanel …). That stays a MANUAL §4.4
 * review step — list your `export const *Panel` next to same-role panels in other
 * blocks before finishing.
 *
 * Run via `npm run lint:reuse` (also part of `npm run check`). Exit 1 on any hit.
 *
 * @package Flexa\Block
 */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';

const ROOT = process.cwd();

/**
 * Each rule scans one directory tree for one banned pattern. `why` explains the
 * fix so a contributor sees what to import instead of re-deriving it.
 */
const RULES = [
	{
		dir: 'src/blocks',
		exts: [ '.ts', '.tsx' ],
		why: 'Import cn / apply* preview builders from @utils — do not redeclare them per block (guide §4.1–4.4).',
		patterns: [
			/const cn = \( \.\.\.parts/,
			/const (applyTypography|applyBgFill|applyTextFill|applyBorderPreview|applyBackgroundPreview) =/,
		],
	},
	{
		dir: 'src/blocks',
		exts: [ '.ts', '.tsx' ],
		why: 'Use applyBorderPreview / applyBackgroundPreview / boxShadowPreview from @utils instead of inlining border/background/shadow in the preview (guide §4.2 rule 4).',
		patterns: [
			/s\.borderStyle = b\.style/,
			/s\.backgroundColor = background/,
			/s\.boxShadow = `\$\{ boxShadow\.inset/,
		],
	},
	{
		dir: 'src/blocks',
		exts: [ '.ts', '.tsx' ],
		// The content/text alignment sets are the ones with a CENTRE entry
		// (left/center/right[/justify]); their copies always contain an
		// `icon: AlignCenterIcon` row. Keying on the centre icon avoids false
		// positives on genuinely-different 2-value sets that reuse the left/right
		// arrows (e.g. FAQ's icon-side left/right). The shared exports live in
		// src/components (out of this scan), so a centre-align icon inside
		// src/blocks is a copy — the drift the name-based rules above miss
		// (ALIGN_OPTIONS ↔ ALIGNMENT_OPTIONS renames slip through).
		why: 'Import CONTENT_ALIGN_OPTIONS (left/center/right) or TEXT_ALIGN_OPTIONS (adds justify) from @components — do not re-declare an alignment option array per block (guide §4.4 / §6.4a).',
		patterns: [ /icon:\s*AlignCenterIcon\b/ ],
	},
	{
		dir: 'src/blocks',
		exts: [ '.ts', '.tsx' ],
		// Value-level duplicates found by the 2026-07 audit: option/unit/map sets
		// copied under different names. Each has a clean text signature; the shared
		// version lives in src/components or src/utils (outside this scan). Compare
		// by VALUE, not by variable name (guide §4.4).
		why: 'Import the shared set instead of re-declaring it by value: px-only units → WEIGHT_UNITS (@utils); border styles minus "None" → LINE_STYLE_OPTIONS (@utils); boxed/full-width → CONTAINER_WIDTH_OPTIONS (@components); left/center/right→flex map → CONTENT_ALIGN_TO_FLEX (@utils). (guide §4.4)',
		patterns: [
			/\[\s*\{\s*value: 'px', label: 'px'\s*\}\s*\]/, // px-only unit array
			/BORDER_STYLE_OPTIONS\.filter\(/,              // line styles = border minus empty
			/value: 'boxed', label:/,                      // boxed/full-width Segmented option
			/center: 'center', right: 'flex-end'/,         // align-keyword → flex map
		],
	},
	{
		dir: 'includes/css-generators',
		exts: [ '.php' ],
		why: 'These helpers live in CSS_Helpers — call CSS_Helpers::… ; do not declare a private copy in a generator (guide §4.1).',
		patterns: [
			/private static function (add_typography|open_device|close_device|box_shadow|add_border|add_background|text_shadow|add_text_stroke)\b/,
		],
	},
	{
		dir: 'includes/css-generators',
		exts: [ '.php' ],
		why: 'Call CSS_Helpers::open_device / close_device / add_typography — not self:: (guide §4.3).',
		patterns: [ /self::(open_device|close_device|add_typography)\(/ ],
	},
];

/** Recursively list files under `dir` whose extension is in `exts`. */
function walk( dir, exts ) {
	const out = [];
	let entries;
	try {
		entries = readdirSync( dir );
	} catch {
		return out; // Directory missing → nothing to scan.
	}
	for ( const name of entries ) {
		const full = join( dir, name );
		if ( statSync( full ).isDirectory() ) {
			out.push( ...walk( full, exts ) );
		} else if ( exts.some( ( e ) => name.endsWith( e ) ) ) {
			out.push( full );
		}
	}
	return out;
}

const violations = [];

for ( const rule of RULES ) {
	const base = join( ROOT, ...rule.dir.split( '/' ) );
	for ( const file of walk( base, rule.exts ) ) {
		const lines = readFileSync( file, 'utf8' ).split( /\r?\n/ );
		lines.forEach( ( line, i ) => {
			if ( rule.patterns.some( ( re ) => re.test( line ) ) ) {
				violations.push( { file: relative( ROOT, file ).split( sep ).join( '/' ), line: i + 1, text: line.trim(), why: rule.why } );
			}
		} );
	}
}

if ( violations.length === 0 ) {
	// eslint-disable-next-line no-console
	console.log( '✓ reuse audit clean — no copied helpers/panels found.' );
	process.exit( 0 );
}

// eslint-disable-next-line no-console
console.error( `\n✗ reuse audit found ${ violations.length } copied helper(s) that must import from the shared kho:\n` );
for ( const v of violations ) {
	// eslint-disable-next-line no-console
	console.error( `  ${ v.file }:${ v.line }\n    ${ v.text }\n    → ${ v.why }\n` );
}
// eslint-disable-next-line no-console
console.error( 'Fix: import the shared version (see docs/huong-dan-nhan-ban-block.md §4). Panels that only differ by name must be hoisted into components/panels.tsx.\n' );
process.exit( 1 );
