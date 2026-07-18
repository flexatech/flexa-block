/**
 * General settings view — the Dark Mode and Performance cards.
 *
 * Extracted from the app shell so the sidebar "General" tab renders as one
 * self-contained panel. Each card carries an icon header + subtitle, and its
 * toggles are laid out as titled rows. All state lives in the parent; this
 * component only reads the current values and calls back on change.
 */

import { __ } from '@wordpress/i18n';

type Settings = Record< string, any >;

interface Props {
	settings: Settings;
	onDark: ( key: string, value: boolean ) => void;
	onPerf: ( key: string, value: boolean ) => void;
}

/**
 * The two setting cards shown under the General tab.
 * @param root0
 * @param root0.settings Current settings tree.
 * @param root0.onDark   Update a dark-mode flag.
 * @param root0.onPerf   Update a performance flag.
 */
export function GeneralSettings( {
	settings,
	onDark,
	onPerf,
}: Props ): JSX.Element {
	const darkMode = settings.dark_mode || {};
	const performance = settings.performance || {};
	const darkOn = darkMode.enabled !== false;

	return (
		<div className="flexa-admin__grid-two">
			<section className="flexa-admin-card flexa-setting-card">
				<CardHeader
					icon="admin-appearance"
					tint="#6366f1"
					title={ __( 'Dark Mode', 'flexa-block' ) }
					subtitle={ __(
						'Choose how dark colours are emitted and triggered.',
						'flexa-block'
					) }
				/>
				<div className="flexa-setting-card__body">
					<div className="flexa-rows">
						<SettingRow
							label={ __( 'Output dark mode CSS', 'flexa-block' ) }
							help={ __(
								'Master switch. Off = hide dark color pickers and skip dark CSS.',
								'flexa-block'
							) }
							checked={ darkOn }
							onChange={ ( v ) => onDark( 'enabled', v ) }
						/>
						{ darkOn && (
							<>
								<SettingRow
									label={ __(
										'System preference (prefers-color-scheme)',
										'flexa-block'
									) }
									help={ __(
										'Follow the visitor’s OS light/dark setting.',
										'flexa-block'
									) }
									checked={ darkMode.colorScheme !== false }
									onChange={ ( v ) =>
										onDark( 'colorScheme', v )
									}
								/>
								<SettingRow
									label={ __(
										'Data attribute ([data-theme="dark"])',
										'flexa-block'
									) }
									help={ __(
										'Also switch when the page sets [data-theme="dark"].',
										'flexa-block'
									) }
									checked={ darkMode.dataTheme === true }
									onChange={ ( v ) =>
										onDark( 'dataTheme', v )
									}
								/>
							</>
						) }
					</div>
				</div>
			</section>

			<section className="flexa-admin-card flexa-setting-card">
				<CardHeader
					icon="performance"
					tint="#0d9488"
					title={ __( 'Performance', 'flexa-block' ) }
					subtitle={ __(
						'Fine-tune the generated stylesheet output.',
						'flexa-block'
					) }
				/>
				<div className="flexa-setting-card__body">
					<div className="flexa-rows">
						<SettingRow
							label={ __( 'CSS specificity boost', 'flexa-block' ) }
							help={ __(
								'Prepend "body" to generated selectors so they reliably override theme styles.',
								'flexa-block'
							) }
							checked={ performance.specificityBoost === true }
							onChange={ ( v ) =>
								onPerf( 'specificityBoost', v )
							}
						/>
					</div>
				</div>
			</section>
		</div>
	);
}

/**
 * A single setting row: label + help on the left, an accessible switch right.
 * @param root0
 * @param root0.label    Setting name.
 * @param root0.help     Short explanation under the label.
 * @param root0.checked  Current on/off state.
 * @param root0.onChange Toggle handler.
 */
export function SettingRow( {
	label,
	help,
	checked,
	onChange,
}: {
	label: string;
	help?: string;
	checked: boolean;
	onChange: ( value: boolean ) => void;
} ): JSX.Element {
	return (
		<div className={ `flexa-setting-row${ checked ? ' is-on' : '' }` }>
			<span className="flexa-setting-row__text">
				<span className="flexa-setting-row__label">{ label }</span>
				{ help && (
					<span className="flexa-setting-row__help">{ help }</span>
				) }
			</span>
			<button
				type="button"
				role="switch"
				aria-checked={ checked }
				aria-label={ label }
				className={ `flexa-switch${ checked ? ' is-on' : '' }` }
				onClick={ () => onChange( ! checked ) }
			>
				<span className="flexa-switch__knob" />
			</button>
		</div>
	);
}

/**
 * A card header: a tinted icon badge next to a title and subtitle.
 * @param root0
 * @param root0.icon     Dashicon slug (without the `dashicons-` prefix).
 * @param root0.tint     Accent colour for the icon badge.
 * @param root0.title    Card title.
 * @param root0.subtitle Short helper line under the title.
 */
export function CardHeader( {
	icon,
	tint,
	title,
	subtitle,
}: {
	icon: string;
	tint: string;
	title: string;
	subtitle: string;
} ): JSX.Element {
	return (
		<div className="flexa-setting-card__header">
			<span
				className="flexa-setting-card__icon"
				style={ { color: tint, background: `${ tint }1a` } }
				aria-hidden="true"
			>
				<span className={ `dashicons dashicons-${ icon }` } />
			</span>
			<div>
				<h2 className="flexa-admin-card__title">{ title }</h2>
				<p className="flexa-setting-card__sub">{ subtitle }</p>
			</div>
		</div>
	);
}
