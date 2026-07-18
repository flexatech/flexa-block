/**
 * Taxonomy block front-end view script — dropdown navigation.
 *
 * When the block is shown as a dropdown, changing the native `<select>` sends the
 * browser to the chosen term's archive (the option value is the term link). With
 * JavaScript off the select is inert, so nothing ever breaks. `export {}` keeps
 * this a module so its symbols never collide with other blocks' view scripts.
 */

export {};

function navigate( event: Event ): void {
	const select = event.target as HTMLSelectElement | null;
	if ( ! select ) {
		return;
	}
	const url = select.value;
	if ( url ) {
		window.location.href = url;
	}
}

function init(): void {
	const selects = document.querySelectorAll< HTMLSelectElement >(
		'.flexa-taxonomy__select[data-flexa-taxonomy-nav]'
	);
	selects.forEach( ( select ) => {
		select.addEventListener( 'change', navigate );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
