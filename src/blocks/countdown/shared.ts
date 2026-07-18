/**
 * Countdown block — logic shared between the editor preview (edit.tsx) and the
 * front-end timer (view.ts). Keeping the unit maths in one place means the
 * preview and the live countdown always agree on rounding and roll-up rules.
 *
 * @package Flexa\Block
 */

import type { CountdownSeparatorAttr } from '../../types';

/** The four countdown units, largest first. */
export const UNIT_KEYS = [ 'days', 'hours', 'minutes', 'seconds' ] as const;

export type UnitKey = ( typeof UNIT_KEYS )[ number ];

/** Seconds contained in one of each unit. */
const UNIT_SECONDS: Record< UnitKey, number > = {
	days: 86400,
	hours: 3600,
	minutes: 60,
	seconds: 1,
};

/**
 * Break a remaining duration (in ms) into the visible units. A hidden higher
 * unit rolls its value into the next visible one (e.g. with days off, hours
 * counts the full remaining hours), so the largest shown unit never truncates.
 *
 * @param ms    Milliseconds remaining (negative clamps to zero).
 * @param shows Which units are visible.
 * @return Value per unit (0 for hidden units).
 */
export const remainingParts = (
	ms: number,
	shows: Record< UnitKey, boolean >
): Record< UnitKey, number > => {
	let rem = Math.max( 0, Math.floor( ms / 1000 ) );
	const out: Record< UnitKey, number > = { days: 0, hours: 0, minutes: 0, seconds: 0 };
	for ( const unit of UNIT_KEYS ) {
		if ( ! shows[ unit ] ) {
			continue;
		}
		if ( unit === 'seconds' ) {
			out.seconds = rem;
		} else {
			out[ unit ] = Math.floor( rem / UNIT_SECONDS[ unit ] );
			rem -= out[ unit ] * UNIT_SECONDS[ unit ];
		}
	}
	return out;
};

/**
 * Resolve a target date to a UTC epoch (ms), interpreting a bare wall-clock
 * string ("YYYY-MM-DDTHH:MM[:SS]") as being in the given UTC offset. A string
 * that already carries a timezone (or isn't wall-clock) falls back to
 * Date.parse so an ISO value with `Z`/offset still works.
 *
 * @param endDate     Target date string.
 * @param offsetHours UTC offset in hours the wall-clock is expressed in.
 * @return Epoch ms, or null when the date is empty / unparseable.
 */
export const targetEpoch = ( endDate: string | undefined, offsetHours: number ): number | null => {
	if ( ! endDate ) {
		return null;
	}
	const m = endDate.match( /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/ );
	if ( m ) {
		const utc = Date.UTC( Number( m[ 1 ] ), Number( m[ 2 ] ) - 1, Number( m[ 3 ] ), Number( m[ 4 ] ), Number( m[ 5 ] ), m[ 6 ] ? Number( m[ 6 ] ) : 0 );
		return utc - offsetHours * 3600000;
	}
	const parsed = Date.parse( endDate );
	return Number.isNaN( parsed ) ? null : parsed;
};

/**
 * Zero-pad a unit value to at least two digits (days may exceed two).
 *
 * @param value Numeric value.
 * @return Padded string.
 */
export const pad = ( value: number ): string => String( Math.max( 0, value ) ).padStart( 2, '0' );

/**
 * Resolve the character rendered between units for a separator setting.
 *
 * @param separator Separator attribute.
 * @return The separator string ('' when none).
 */
export const separatorChar = ( separator?: CountdownSeparatorAttr ): string => {
	const type = separator?.type || 'none';
	if ( 'colon' === type ) {
		return ':';
	}
	if ( 'dash' === type ) {
		return '-';
	}
	if ( 'custom' === type ) {
		return separator?.custom || '';
	}
	return '';
};
