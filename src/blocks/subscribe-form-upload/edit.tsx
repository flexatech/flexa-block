/**
 * Subscribe Form — Upload field editor (child of flexa/subscribe-form).
 *
 * @package Flexa\Block
 */

import { SubscribeFieldEdit } from '@components';
import type { EditProps, SubscribeFieldAttributes } from '../../types';

export default function Edit( props: EditProps< SubscribeFieldAttributes > ): JSX.Element {
	return <SubscribeFieldEdit kind="upload" fallbackName="file" { ...props } />;
}
