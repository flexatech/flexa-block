/**
 * CTA block — editor component.
 *
 * Assembles the CTA-specific layout panel (./panels) with the shared promo panels
 * (content / heading / description / buttons) and the shared foundational panels
 * (@components). The canvas renders the CTA wrapper — centred (stacked) or split
 * (text one side, buttons the other) — with the shared PromoContent edited inline.
 * Inline styles mirror the PHP CSS generator; nothing is styled by default so the
 * CTA inherits the theme + the base style.scss until the user picks a value.
 *
 * @package Flexa\Block
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	PromoContentPanel,
	PromoHeadingPanel,
	PromoDescriptionPanel,
	PromoButtonsPanel,
	PromoContent,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	type CssProps,
} from '@utils';
import { CtaLayoutPanel } from './panels';
import type { CtaAttributes, DeviceKey, EditProps } from '../../types';

/** CTA wrapper preview: background, border, shadow and sizing. */
const buildWrapperStyle = ( attributes: CtaAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const isBoxed = attributes.containerType !== 'full-width';

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	const width = effective( isBoxed ? attributes.widthBoxed : attributes.widthFullWidth, device );
	if ( width.value ) {
		const wu = width.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( width.value, wu );
	}

	const minHeight = effective( attributes.size, device ).minHeight;
	if ( minHeight?.value ) s.minHeight = withUnit( minHeight.value, minHeight.unit || 'px' );

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, rawDevice( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/**
 * CTA edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< CtaAttributes > ): JSX.Element {
	const { arrangement, containerType, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const isSplit = arrangement === 'split';

	// Hover state — inline styles can't express `:hover`, so mirror the shared promo
	// button hover rules in a scoped <style> (light values, only what's set).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-cta-${ blockId } .flexa-promo__button--primary:hover`, prop: 'color', value: attributes.primaryHover?.text?.light },
				{ selector: `.flexa-cta-${ blockId } .flexa-promo__button--primary:hover`, prop: 'background-color', value: attributes.primaryHover?.background?.light },
				{ selector: `.flexa-cta-${ blockId } .flexa-promo__button--secondary:hover`, prop: 'color', value: attributes.secondaryHover?.text?.light },
				{ selector: `.flexa-cta-${ blockId } .flexa-promo__button--secondary:hover`, prop: 'background-color', value: attributes.secondaryHover?.background?.light },
		  ] )
		: '';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-cta',
			`flexa-cta--${ arrangement || 'centered' }`,
			`flexa-cta--${ containerType || 'boxed' }`,
			blockId && `flexa-cta-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-cta-inspector">
					<InspectorTabs
						layout={
							<>
								<CtaLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PromoContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<PromoHeadingPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PromoDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PromoButtonsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<PositionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<PromoContent attributes={ attributes } setAttributes={ setAttributes } applyAlign={ ! isSplit } />
			</div>
		</>
	);
}
