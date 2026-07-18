/**
 * IconPicker — reusable icon chooser (shared across blocks).
 *
 * Three sources:
 *   • builtin  — a small curated set of stroked glyphs (dependency-free).
 *   • library  — icons from `@wordpress/icons` (WordPress's own library).
 *   • upload   — an SVG picked from the media library.
 *
 * Whatever the source, the value resolves to render-ready data: builtin/library
 * icons are serialised to a normalised inline-SVG string (`markup`) so the front
 * end can print them without knowing the catalog; uploads keep `url`/`id`.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { renderToString, useState, isValidElement } from '@wordpress/element';
import { Button, BaseControl, Dropdown, Icon, TabPanel, SearchControl } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import * as wpIcons from '@wordpress/icons';
import type { IconValue } from '../types';

/** Curated, dependency-free stroked glyphs. Class + 1em sizing baked in. */
const strokeSvg = ( inner: JSX.Element ): JSX.Element => (
	<svg
		className="flexa-icon"
		viewBox="0 0 24 24"
		width="1em"
		height="1em"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{ inner }
	</svg>
);

const BUILTIN: Array< { name: string; svg: JSX.Element } > = [
	{ name: 'arrow-right', svg: strokeSvg( <path d="M5 12h13M13 6l6 6-6 6" /> ) },
	{ name: 'arrow-left', svg: strokeSvg( <path d="M19 12H6M11 6l-6 6 6 6" /> ) },
	{ name: 'chevron-right', svg: strokeSvg( <path d="M9 6l6 6-6 6" /> ) },
	{
		name: 'external',
		svg: strokeSvg(
			<>
				<path d="M15 4h5v5" />
				<path d="M20 4l-8 8" />
				<path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5" />
			</>
		),
	},
	{
		name: 'download',
		svg: strokeSvg(
			<>
				<path d="M12 4v11m0 0l-4-4m4 4l4-4" />
				<path d="M5 19h14" />
			</>
		),
	},
	{ name: 'check', svg: strokeSvg( <path d="M5 12l4 4L19 6" /> ) },
];

/**
 * Render a resolved IconValue for editor previews — mirrors the PHP
 * `HTML_Helpers::icon_html()` front-end output. Uploads render as an <img>;
 * builtin/library icons print their stored inline SVG markup. Returns null when
 * the icon is unset / `none` / empty. Shared by every block that shows a single
 * glyph (icon, icon-list, modal trigger) so the resolve logic lives once.
 */
export const IconMarkup = ( {
	icon,
	className = 'flexa-icon',
}: {
	icon?: IconValue;
	className?: string;
} ): JSX.Element | null => {
	const source = icon?.source ?? 'none';
	if ( source === 'upload' && icon?.url ) {
		return <img className={ className } src={ icon.url } alt="" width="24" height="24" />;
	}
	if ( source !== 'none' && icon?.markup ) {
		return <span className="flexa-icon-markup" dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	return null;
};

/**
 * The whole WordPress icon library. `@wordpress/icons` exports each icon as a
 * ready-to-render SVG element alongside a few non-icon helpers (the `Icon`
 * wrapper, the library `Icon`/utilities); we keep only the valid elements so the
 * full set is available without hand-listing ~290 names. `key` is the lowercased
 * export name, used for the search filter.
 */
const LIBRARY: Array< { name: string; key: string; icon: unknown } > = Object.entries( wpIcons )
	.filter( ( [ , icon ] ) => isValidElement( icon ) )
	.map( ( [ exportName, icon ] ) => ( { name: 'wp-' + exportName, key: exportName.toLowerCase(), icon } ) );

/**
 * Normalise a WordPress-library SVG string: add our class, size it to 1em and
 * make it inherit the text colour (library glyphs are filled, not stroked).
 */
const normalizeLibrary = ( raw: string ): string => {
	let svg = raw.trim();
	svg = svg.replace( /\swidth="[^"]*"/i, '' ).replace( /\sheight="[^"]*"/i, '' );
	svg = svg.replace( /^<svg\b/i, '<svg class="flexa-icon" width="1em" height="1em"' );
	if ( ! /^<svg\b[^>]*\sfill=/i.test( svg ) ) {
		svg = svg.replace( /^<svg\b/i, '<svg fill="currentColor"' );
	}
	return svg;
};

interface IconPickerProps {
	label: string;
	value?: IconValue;
	onChange: ( value: IconValue ) => void;
}

interface IconButtonProps {
	key?: string;
	active: boolean;
	onClick: () => void;
	children?: JSX.Element;
}

const IconButton = ( { active, onClick, children }: IconButtonProps ): JSX.Element => (
	<button type="button" className={ 'flexa-iconpicker__btn' + ( active ? ' is-active' : '' ) } onClick={ onClick }>
		{ children }
	</button>
);

/**
 * Icon chooser control.
 */
export const IconPicker = ( { label, value = {}, onChange }: IconPickerProps ): JSX.Element => {
	const source = value.source || 'none';
	const [ query, setQuery ] = useState( '' );

	// Filter by the lowercased export name; builtin glyphs match on their slug.
	const q = query.trim().toLowerCase();
	const builtinShown = q ? BUILTIN.filter( ( b ) => b.name.includes( q ) ) : BUILTIN;
	const libraryShown = q ? LIBRARY.filter( ( l ) => l.key.includes( q ) ) : LIBRARY;

	const pickBuiltin = ( name: string, svg: JSX.Element ) =>
		onChange( { source: 'builtin', name, markup: renderToString( svg ), url: '', id: null } );

	const pickLibrary = ( name: string, iconDef: unknown ) =>
		onChange( {
			source: 'library',
			name,
			markup: normalizeLibrary( renderToString( <Icon icon={ iconDef } size={ 24 } /> ) ),
			url: '',
			id: null,
		} );

	const pickUpload = ( media: { id: number; url: string } ) =>
		onChange( { source: 'upload', name: '', markup: '', url: media.url, id: media.id } );

	const clear = () => onChange( { source: 'none', name: '', markup: '', url: '', id: null } );

	const preview =
		source === 'upload' && value.url ? (
			<img className="flexa-iconpicker__preview" src={ value.url } alt="" />
		) : value.markup ? (
			<span className="flexa-iconpicker__preview" dangerouslySetInnerHTML={ { __html: value.markup } } />
		) : (
			<span className="flexa-iconpicker__preview is-empty" />
		);

	return (
		<BaseControl __nextHasNoMarginBottom label={ label } className="flexa-iconpicker">
			<div className="flexa-iconpicker__row">
				<Dropdown
					className="flexa-iconpicker__dropdown"
					contentClassName="flexa-iconpicker__popover"
					popoverProps={ { placement: 'bottom-start' } }
					renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
						<Button
							variant="secondary"
							className="flexa-iconpicker__toggle"
							onClick={ onToggle }
							aria-expanded={ isOpen }
						>
							{ preview }
							<span>{ source === 'none' ? __( 'Select icon', 'flexa-block' ) : __( 'Change', 'flexa-block' ) }</span>
						</Button>
					) }
					renderContent={ () => (
						<div className="flexa-iconpicker__panel">
							<TabPanel
								className="flexa-iconpicker__tabs"
								tabs={ [
									{ name: 'library', title: __( 'Icons', 'flexa-block' ) },
									{ name: 'upload', title: __( 'Upload SVG', 'flexa-block' ) },
								] }
							>
								{ ( tab: { name: string } ) =>
									tab.name === 'library' ? (
										<>
											<SearchControl
												__nextHasNoMarginBottom
												className="flexa-iconpicker__search"
												value={ query }
												onChange={ setQuery }
												placeholder={ __( 'Search icons…', 'flexa-block' ) }
											/>
											<div className="flexa-iconpicker__grid">
												{ builtinShown.map( ( b ) => (
													<IconButton
														key={ b.name }
														active={ source === 'builtin' && value.name === b.name }
														onClick={ () => pickBuiltin( b.name, b.svg ) }
													>
														{ b.svg }
													</IconButton>
												) ) }
												{ libraryShown.map( ( l ) => (
													<IconButton
														key={ l.name }
														active={ source === 'library' && value.name === l.name }
														onClick={ () => pickLibrary( l.name, l.icon ) }
													>
														<Icon icon={ l.icon } size={ 22 } />
													</IconButton>
												) ) }
												{ 0 === builtinShown.length && 0 === libraryShown.length && (
													<p className="flexa-iconpicker__empty">{ __( 'No icons found.', 'flexa-block' ) }</p>
												) }
											</div>
										</>
									) : (
										<div className="flexa-iconpicker__upload">
											<MediaUploadCheck>
												<MediaUpload
													allowedTypes={ [ 'image' ] }
													value={ value.id ?? undefined }
													onSelect={ pickUpload }
													render={ ( { open }: { open: () => void } ) => (
														<Button variant="secondary" onClick={ open }>
															{ value.url ? __( 'Replace SVG', 'flexa-block' ) : __( 'Choose / upload SVG', 'flexa-block' ) }
														</Button>
													) }
												/>
											</MediaUploadCheck>
											<p className="flexa-iconpicker__hint">
												{ __( 'Pick an SVG from the media library. (SVG uploads must be enabled on your site.)', 'flexa-block' ) }
											</p>
										</div>
									)
								}
							</TabPanel>
						</div>
					) }
				/>
				{ source !== 'none' && (
					<Button isDestructive variant="tertiary" onClick={ clear }>
						{ __( 'Remove', 'flexa-block' ) }
					</Button>
				) }
			</div>
		</BaseControl>
	);
};
