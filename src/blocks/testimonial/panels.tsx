/**
 * Testimonial block — testimonial-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to a testimonial card: the layout & alignment, the
 * star rating, the headline, the quote and the author block (avatar + name +
 * role). Following the "prefer theme styles" rule nothing is styled by default —
 * only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, ToggleControl, BaseControl, Flex, Button } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import { Segmented, SliderUnit, DualColor, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import {
	rawDevice,
	patchDevice,
	LENGTH_UNITS,
	SPACING_UNITS,
} from '@utils';
import type { ControlOption, ImageMedia, LengthValue, PanelProps, TestimonialAttributes, TypographyDevice } from '../../types';

type TPanelProps = PanelProps< TestimonialAttributes >;

/** How the avatar sits relative to the name/role (few values → Segmented). */
const AUTHOR_LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'stacked', label: __( 'Stacked', 'flexa-block' ) },
];

/**
 * Layout panel — content alignment, author layout and an optional min height.
 */
export const TestimonialLayoutPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, authorLayout, size } = attributes;
	const minHeight = rawDevice( size, device ).minHeight || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || ( device === 'desktop' ? 'left' : '' ) || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<Segmented
				label={ __( 'Author layout', 'flexa-block' ) }
				value={ authorLayout || 'inline' }
				onChange={ ( v ) => setAttributes( { authorLayout: v as TestimonialAttributes[ 'authorLayout' ] } ) }
				options={ AUTHOR_LAYOUT_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Min height', 'flexa-block' ) }
				value={ minHeight }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 800, vh: 100, '%': 100, rem: 60, em: 60 } }
				onChange={ ( v: LengthValue ) => setAttributes( { size: patchDevice( size, device, { minHeight: v } ) } ) }
			/>
		</PanelBody>
	);
};

/**
 * Rating panel — show toggle, star count, colour, size and spacing below.
 */
export const TestimonialRatingPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showRating, rating, ratingColor, ratingSize, ratingSpacing } = attributes;

	return (
		<PanelBody title={ __( 'Rating', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show rating', 'flexa-block' ) }
				checked={ showRating !== false }
				onChange={ ( v: boolean ) => setAttributes( { showRating: v } ) }
			/>
			{ showRating !== false && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Stars', 'flexa-block' ) }
						value={ typeof rating === 'number' ? rating : 5 }
						min={ 0 }
						max={ 5 }
						step={ 1 }
						onChange={ ( v: number | undefined ) => setAttributes( { rating: v ?? 0 } ) }
					/>
					<DualColor label={ __( 'Star colour', 'flexa-block' ) } value={ ratingColor || {} } onChange={ ( v ) => setAttributes( { ratingColor: v } ) } />
					<SliderUnit
						label={ __( 'Star size', 'flexa-block' ) }
						value={ ratingSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 80, em: 6, rem: 6, '%': 200 } }
						onChange={ ( v: LengthValue ) => setAttributes( { ratingSize: { ...ratingSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Spacing below', 'flexa-block' ) }
						value={ ratingSpacing?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 120, em: 12, rem: 12, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { ratingSpacing: { ...ratingSpacing, [ device ]: v } } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Author panel — avatar image, its size, and the gap to the name/role text.
 */
export const TestimonialAuthorPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showAvatar, authorImage, authorImageSize, authorGap } = attributes;
	const img: ImageMedia = authorImage || {};

	return (
		<PanelBody title={ __( 'Author', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show avatar', 'flexa-block' ) }
				checked={ showAvatar !== false }
				onChange={ ( v: boolean ) => setAttributes( { showAvatar: v } ) }
			/>
			{ showAvatar !== false && (
				<>
					<BaseControl __nextHasNoMarginBottom label={ __( 'Avatar', 'flexa-block' ) }>
						<MediaUploadCheck>
							<MediaUpload
								allowedTypes={ [ 'image' ] }
								value={ img.id ?? undefined }
								onSelect={ ( media: { id: number; url: string; alt?: string } ) => setAttributes( { authorImage: { id: media.id, url: media.url, alt: media.alt || '' } } ) }
								render={ ( { open }: { open: () => void } ) => (
									<>
										{ img.url && (
											<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace avatar', 'flexa-block' ) }>
												<img src={ img.url } alt="" />
											</button>
										) }
										<Flex gap={ 2 } justify="flex-start">
											<Button variant="secondary" onClick={ open }>
												{ img.url ? __( 'Replace', 'flexa-block' ) : __( 'Select avatar', 'flexa-block' ) }
											</Button>
											{ img.url && (
												<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { authorImage: { id: null, url: '', alt: '' } } ) }>
													{ __( 'Remove', 'flexa-block' ) }
												</Button>
											) }
										</Flex>
									</>
								) }
							/>
						</MediaUploadCheck>
					</BaseControl>
					<SliderUnit
						label={ __( 'Avatar size', 'flexa-block' ) }
						value={ authorImageSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 160, em: 12, rem: 12, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { authorImageSize: { ...authorImageSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Gap to text', 'flexa-block' ) }
						value={ authorGap?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 80, em: 8, rem: 8, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { authorGap: { ...authorGap, [ device ]: v } } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Title panel — headline typography, colour and spacing below.
 */
export const TestimonialTitlePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing below', 'flexa-block' ) }
				value={ attributes.titleSpacing?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { titleSpacing: { ...attributes.titleSpacing, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Quote panel — quote typography, colour and spacing below.
 */
export const TestimonialQuotePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.quoteTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { quoteTypography: patchDevice( attributes.quoteTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Quote', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.quoteColor || {} } onChange={ ( v ) => setAttributes( { quoteColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing below', 'flexa-block' ) }
				value={ attributes.quoteSpacing?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { quoteSpacing: { ...attributes.quoteSpacing, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Author name panel — name typography and colour.
 */
export const TestimonialNamePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.nameTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { nameTypography: patchDevice( attributes.nameTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Author name', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.nameColor || {} } onChange={ ( v ) => setAttributes( { nameColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Author role panel — role/company typography and colour.
 */
export const TestimonialJobPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.jobTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { jobTypography: patchDevice( attributes.jobTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Author role', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.jobColor || {} } onChange={ ( v ) => setAttributes( { jobColor: v } ) } />
		</PanelBody>
	);
};
