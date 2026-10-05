/**
 * FormFlow suggestion banner — the in-plugin half of the cross-promotion.
 *
 * Sits above the dashboard shell and introduces Flexa FormFlow, our free form
 * builder. Whether it may render at all is decided in PHP (FormFlow_Promo), so
 * this component only honours `show` and never re-checks the capability, the
 * dismissal, or whether FormFlow is installed. Turning it down here also
 * retires the Dashboard notice: both write the same option.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';

const boot: FlexaBlockAdminData = window.flexaBlockAdmin || {};

/**
 * The promo card, or nothing at all.
 */
export function FormFlowBanner(): JSX.Element | null {
	const promo = boot.formFlow;
	const [ hidden, setHidden ] = useState( false );

	if ( ! promo || ! promo.show || hidden ) {
		return null;
	}

	// Hide first, persist second: the request is a one-way flag, and a failure
	// only means the banner is back on the next load.
	const onDismiss = () => {
		setHidden( true );
		apiFetch( { url: promo.dismissUrl, method: 'POST' } ).catch( () => {} );
	};

	return (
		<section className="flexa-promo">
			<img
				className="flexa-promo__icon"
				src={ promo.iconUrl }
				width="48"
				height="48"
				alt=""
				decoding="async"
			/>
			<div className="flexa-promo__body">
				<h2 className="flexa-promo__title">
					{ __(
						'Need a form on the pages you build?',
						'flexa-block'
					) }
					<span className="flexa-promo__pill">
						{ __( 'Free plugin', 'flexa-block' ) }
					</span>
				</h2>
				<p className="flexa-promo__text">
					{ __(
						'Flexa FormFlow is our free form builder, with a visual email designer and a workflow engine built in. Drag fields onto the canvas, design the emails each submission sends, then drop the finished form into any Flexa Block layout.',
						'flexa-block'
					) }
				</p>
				<div className="flexa-promo__actions">
					{ promo.installUrl !== '' && (
						<Button variant="primary" href={ promo.installUrl }>
							{ __( 'Install FormFlow', 'flexa-block' ) }
						</Button>
					) }
					<Button
						variant="secondary"
						href={ promo.learnMoreUrl }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Learn more', 'flexa-block' ) }
					</Button>
					<Button variant="tertiary" onClick={ onDismiss }>
						{ __( 'Not interested', 'flexa-block' ) }
					</Button>
				</div>
			</div>
			<button
				type="button"
				className="flexa-promo__close"
				onClick={ onDismiss }
			>
				<span
					className="dashicons dashicons-no-alt"
					aria-hidden="true"
				/>
				<span className="screen-reader-text">
					{ __( 'Hide this suggestion', 'flexa-block' ) }
				</span>
			</button>
		</section>
	);
}
