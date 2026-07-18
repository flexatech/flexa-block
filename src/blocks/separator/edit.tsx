/**
 * Separator block — editor component.
 *
 * Assembles the separator-specific panel with the shared inspector panels
 * (@components) for spacing and visibility. The canvas renders a single line
 * whose inline preview mirrors the PHP CSS generator; only values the user picks
 * produce CSS, so the untouched line inherits the theme's text colour.
 *
 * @package Flexa\Block
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import { InspectorTabs, SpacingPanel, VisibilityPanel, AnimationPanel, useBlockId, useDevice, ExamplePreviewSkeleton } from '@components';
import { cn, visibilityClasses, effective, withUnit, spacingShorthand, CONTENT_ALIGN_TO_FLEX } from '@utils';
import type { CssProps } from '@utils';
import { SeparatorPanel, SeparatorStylePanel, SeparatorAdvancedPanel } from './panels';
import type { DeviceKey, EditProps, SeparatorAttributes } from '../../types';

/** Wrapper preview: alignment (justify-content) + spacing (padding / margin). */
const buildWrapperStyle = ( attributes: SeparatorAttributes, device: DeviceKey ): CssProps => {
	const { alignment, spacing } = attributes;
	const sp = effective( spacing, device );
	const s: CssProps = {};

	const align = alignment?.[ device ] || '';
	if ( align && CONTENT_ALIGN_TO_FLEX[ align ] ) {
		s.justifyContent = CONTENT_ALIGN_TO_FLEX[ align ];
	}
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	return s;
};

/** Line preview: width + thickness (border-top-width) + style + colour. */
const buildLineStyle = ( attributes: SeparatorAttributes, device: DeviceKey ): CssProps => {
	const { width, weight, lineStyle, color } = attributes;
	const w = effective( width, device );
	const wt = effective( weight, device );
	const s: CssProps = {};

	if ( w.value ) s.width = withUnit( w.value, w.unit || '%' );
	if ( wt.value ) s.borderTopWidth = withUnit( wt.value, wt.unit || 'px' );
	if ( lineStyle ) s.borderTopStyle = lineStyle;
	if ( color?.light ) s.borderTopColor = color.light;

	return s;
};

/**
 * Separator edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SeparatorAttributes > ): JSX.Element {
	const { htmlTag, className, responsiveVisibility } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	const Tag: any = htmlTag || 'div';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-separator',
			blockId && `flexa-separator-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real line.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="separator" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-separator-inspector">
					<InspectorTabs
						layout={
							<>
								<SeparatorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={ <SeparatorStylePanel attributes={ attributes } setAttributes={ setAttributes } /> }
						advanced={
							<>
								<SeparatorAdvancedPanel attributes={ attributes } setAttributes={ setAttributes } />
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<Tag { ...blockProps }>
				<hr className="flexa-separator__line" style={ buildLineStyle( attributes, device ) } />
			</Tag>
		</>
	);
}
