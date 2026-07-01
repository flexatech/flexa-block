/**
 * Inspector tab strip (Layout / Style / Advanced) — shared across blocks.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';

interface InspectorTabsProps {
	layout: JSX.Element;
	style: JSX.Element;
	advanced: JSX.Element;
}

/**
 * Three-tab inspector layout.
 */
export const InspectorTabs = ( { layout, style, advanced }: InspectorTabsProps ): JSX.Element => {
	const tabs = [
		{ name: 'layout', title: __( 'Layout', 'flexa-block' ) },
		{ name: 'style', title: __( 'Style', 'flexa-block' ) },
		{ name: 'advanced', title: __( 'Advanced', 'flexa-block' ) },
	];
	const content: Record< string, JSX.Element > = { layout, style, advanced };

	return (
		<TabPanel className="flexa-tabs" tabs={ tabs } initialTabName="layout">
			{ ( tab: { name: string } ) => <div className="flexa-tab__content">{ content[ tab.name ] }</div> }
		</TabPanel>
	);
};
