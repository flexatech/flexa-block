/**
 * Modal block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components). The canvas mirrors the front-end trigger (button / text /
 * image / icon) and shows the modal in its OPEN state so the inner blocks can be
 * edited inline — opening / closing itself is a front-end concern (view.ts). The
 * modal content is InnerBlocks, wired with useInnerBlocksProps so the box is a
 * real editable region.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BorderPanel,
	ShadowPanel,
	VisibilityPanel,
	AnimationPanel,
	IconMarkup,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	visibilityClasses,
	applyTypography,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
} from '@utils';
import { TriggerPanel, TriggerStylePanel, ModalBoxPanel, OverlayClosePanel, BehaviourPanel } from './panels';
import type { DeviceKey, EditProps, ModalAttributes } from '../../types';

type CssProps = Record< string, string >;

/** Trigger preview: typography + text/background colour + padding + radius + icon size. */
const buildTriggerStyle = ( attributes: ModalAttributes, device: DeviceKey ): CssProps => {
	const { triggerTypography, triggerTextColor, triggerBackground, triggerPadding, triggerRadius, triggerIconSize } = attributes;
	const s: CssProps = {};
	applyTypography( s, rawDevice( triggerTypography, device ) );
	if ( triggerTextColor?.light ) s.color = triggerTextColor.light;
	if ( triggerBackground?.light ) s.background = triggerBackground.light;
	const padding = spacingShorthand( rawDevice( triggerPadding, device ) );
	if ( padding ) s.padding = padding;
	if ( triggerRadius?.value ) s.borderRadius = withUnit( triggerRadius.value, triggerRadius.unit || 'px' );
	if ( triggerIconSize?.value ) s[ '--flexa-modal-icon-size' ] = withUnit( triggerIconSize.value, triggerIconSize.unit || 'px' );
	return s;
};

/** Modal box preview: width, max-height, padding, background, radius, border, shadow. */
const buildBoxStyle = ( attributes: ModalAttributes, device: DeviceKey ): CssProps => {
	const { modalWidth, modalMaxHeight, modalPadding, modalBackground, modalRadius, border, boxShadow } = attributes;
	const s: CssProps = {};
	const w = rawDevice( modalWidth, device );
	if ( w.value ) s.width = withUnit( w.value, w.unit || 'px' );
	const mh = rawDevice( modalMaxHeight, device );
	if ( mh.value ) s.maxHeight = withUnit( mh.value, mh.unit || 'px' );
	const padding = spacingShorthand( rawDevice( modalPadding, device ) );
	if ( padding ) s.padding = padding;
	if ( modalBackground?.light ) s.background = modalBackground.light;
	if ( modalRadius?.value ) s.borderRadius = withUnit( modalRadius.value, modalRadius.unit || 'px' );
	applyBorderPreview( s, effective( border, device ) );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/**
 * Hover CSS for the editor — inline styles can't express `:hover`, so mirror the
 * generator's trigger hover rules in a scoped <style>. Light values only (the
 * editor previews light mode); selector mirrors Modal_CSS exactly. Only the
 * button/text trigger renders in the editor, but the rule is emitted regardless —
 * it applies whenever the trigger element exists.
 */
const buildHoverCss = ( attributes: ModalAttributes, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}
	const trigger = `.flexa-modal-${ blockId } .flexa-modal__trigger:hover`;
	return editorCss( [
		{ selector: trigger, prop: 'color', value: attributes.triggerTextColorHover?.light },
		{ selector: trigger, prop: 'background', value: attributes.triggerBackgroundHover?.light },
	] );
};

/**
 * Modal edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ModalAttributes > ): JSX.Element {
	const { triggerType, triggerText, triggerIcon, triggerImage, triggerAlign, overlayColor, closeIconColor, closeIconSize, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const type = triggerType || 'button';

	useBlockId( clientId, blockId, setAttributes );

	const wrapperStyle: CssProps = {};
	const alignVal = triggerAlign?.[ device ] || '';
	if ( alignVal ) wrapperStyle.textAlign = alignVal;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-modal',
			`flexa-modal--${ type }`,
			blockId && `flexa-modal-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	const triggerStyle = buildTriggerStyle( attributes, device );
	const boxStyle = buildBoxStyle( attributes, device );
	const hoverCss = buildHoverCss( attributes, blockId );
	const overlayStyle: CssProps = overlayColor?.light ? { backgroundColor: overlayColor.light } : {};
	const closeStyle: CssProps = {};
	if ( closeIconColor?.light ) closeStyle.color = closeIconColor.light;
	if ( closeIconSize?.value ) closeStyle.fontSize = withUnit( closeIconSize.value, closeIconSize.unit || 'px' );

	// Inner blocks fill the modal box — a real editable region shown open in the editor.
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-modal__content' },
		{ renderAppender: InnerBlocks.ButtonBlockAppender }
	);

	const triggerContent =
		type === 'icon' ? (
			<IconMarkup icon={ triggerIcon } />
		) : type === 'image' ? (
			triggerImage?.url ? (
				<img className="flexa-modal__trigger-image" src={ triggerImage.url } alt={ triggerImage.alt || '' } />
			) : (
				<span className="flexa-modal__trigger-placeholder">{ __( 'Select an image', 'flexa-block' ) }</span>
			)
		) : (
			<span className="flexa-modal__trigger-text">{ triggerText ?? 'Open' }</span>
		);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-modal-inspector">
					<InspectorTabs
						layout={
							<>
								<TriggerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ModalBoxPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BehaviourPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TriggerStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<OverlayClosePanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<span className={ cn( 'flexa-modal__trigger', type === 'button' && 'wp-element-button' ) } style={ triggerStyle }>
					{ triggerContent }
				</span>

				{ /* Editor-only: the modal shown open so inner content is editable. */ }
				<div className="flexa-modal__preview">
					<div className="flexa-modal__overlay" style={ overlayStyle } />
					<div className="flexa-modal__box" style={ boxStyle }>
						<button type="button" className="flexa-modal__close" style={ closeStyle } aria-label={ __( 'Close', 'flexa-block' ) }>
							&times;
						</button>
						<div { ...innerBlocksProps } />
					</div>
				</div>
			</div>
		</>
	);
}
