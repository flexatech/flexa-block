/**
 * Subscribe Form — Email field editor (child of flexa/subscribe-form).
 *
 * A thin wrapper over the shared SubscribeFieldEdit; only the input `kind` and
 * fallback field name differ between the field children.
 *
 * @package Flexa\Block
 */

import { SubscribeFieldEdit } from '@components';
import type { EditProps, SubscribeFieldAttributes } from '../../types';

export default function Edit( props: EditProps< SubscribeFieldAttributes > ): JSX.Element {
	return <SubscribeFieldEdit kind="email" fallbackName="email" { ...props } />;
}
