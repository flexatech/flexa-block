/**
 * Skeleton primitives for the inserter hover-preview.
 *
 * Every block declares an `example` whose only attribute is `isExamplePreview:
 * true`. When a block's Edit sees that flag it renders <ExamplePreviewSkeleton>
 * instead of its real content, so the inserter preview shows a faint grey
 * mock-up of the layout (bars / circles / boxes with a shimmer sweep) rather
 * than exposing concrete values (countdown digits, heading copy, brand icons).
 *
 * The skeleton is real block markup, so it renders wherever the preview is
 * drawn — inside or outside the editor iframe. The grey + shimmer look comes
 * from the `.flexa-skel` rule in editor.scss; layout is inline here so each
 * variant stays self-contained.
 *
 * @package Flexa\Block
 */

type Box = Record< string, string | number >;

const cn = ( ...parts: Array< string | false | null | undefined > ): string => parts.filter( Boolean ).join( ' ' );

/**
 * Base fill is inlined (not left to the `.flexa-skel` rule) on purpose: the
 * inserter preview can render in an iframe that doesn't load our editorStyle, so
 * relying on CSS alone would leave the bars transparent. Inline guarantees solid
 * grey; the `.flexa-skel` class only adds the shimmer sweep (a bonus overlay).
 */
const BASE: Box = { display: 'block', background: '#dfe3e8', borderRadius: 5, position: 'relative', overflow: 'hidden' };

/** A grey shimmer bar (default: full-width, 12px tall). */
export function SkeletonBar( {
	w = '100%',
	h = 12,
	className = '',
	style = {},
}: {
	w?: string | number;
	h?: string | number;
	className?: string;
	style?: Box;
} ): JSX.Element {
	return <span className={ cn( 'flexa-skel', className ) } style={ { ...BASE, width: w, height: h, ...style } } />;
}

/** A grey shimmer circle (icons / avatars). */
export function SkeletonCircle( { size = 40, style = {} }: { size?: number; style?: Box } ): JSX.Element {
	return <span className="flexa-skel flexa-skel--circle" style={ { ...BASE, borderRadius: '50%', width: size, height: size, ...style } } />;
}

/** A grey shimmer box (images / number tiles / buttons). */
export function SkeletonBox( {
	w = '100%',
	h = 80,
	style = {},
}: {
	w?: string | number;
	h?: string | number;
	style?: Box;
} ): JSX.Element {
	return <span className="flexa-skel flexa-skel--box" style={ { ...BASE, borderRadius: 8, width: w, height: h, ...style } } />;
}

/**
 * Shared frame: a grey rounded border with the skeleton centred both ways, so
 * every block's preview reads as one tidy mock-up card (matches the reference).
 */
const FRAME: Box = {
	display: 'flex',
	alignItems: 'center',
	justifyContent: 'center',
	boxSizing: 'border-box',
	width: '100%',
	// Tall enough that the bordered card fills the preview viewport vertically —
	// so the grey border reads as the big outer frame, not a box hugging the
	// bars. Bars stay vertically centred inside. Bump this if a gap remains.
	minHeight: 350,
	padding: '48px 32px',
	border: '1px solid #dfe3e8',
	borderRadius: 10,
	background: '#fff',
};

/** Vertical stack, centred horizontally — the default inner layout. */
const COL: Box = { display: 'flex', flexDirection: 'column', gap: 12, alignItems: 'center', width: '100%' };

/** Horizontal row, centred — for icon/number/button layouts. */
const ROW: Box = { display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 16, width: '100%' };

function HeadingSkeleton(): JSX.Element {
	return (
		<div style={ COL }>
			<SkeletonBar w="70%" h={ 16 } />
			<SkeletonBar w="90%" h={ 9 } />
			<SkeletonBar w="82%" h={ 9 } />
			<SkeletonBar w="55%" h={ 9 } />
		</div>
	);
}

function CountdownSkeleton(): JSX.Element {
	return (
		<div style={ ROW }>
			{ [ 0, 1, 2, 3 ].map( ( i ) => (
				<div key={ i } style={ { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 } }>
					<SkeletonBox w={ 44 } h={ 44 } />
					<SkeletonBar w={ 30 } h={ 8 } />
				</div>
			) ) }
		</div>
	);
}

function CounterSkeleton(): JSX.Element {
	return (
		<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 20, width: '100%' } }>
			{ [ 0, 1, 2 ].map( ( i ) => (
				<div key={ i } style={ { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 } }>
					<SkeletonCircle size={ 34 } />
					<SkeletonBar w={ 54 } h={ 20 } />
					<SkeletonBar w={ 40 } h={ 8 } />
				</div>
			) ) }
		</div>
	);
}

function SocialIconSkeleton(): JSX.Element {
	return (
		<div style={ ROW }>
			{ [ 0, 1, 2 ].map( ( i ) => (
				<SkeletonCircle key={ i } size={ 40 } />
			) ) }
		</div>
	);
}

function ButtonSkeleton(): JSX.Element {
	return (
		<div style={ ROW }>
			<SkeletonBox w={ 128 } h={ 42 } style={ { borderRadius: 999 } } />
		</div>
	);
}

function ImageSkeleton(): JSX.Element {
	return (
		<div style={ COL }>
			<SkeletonBox w="100%" h={ 130 } />
			<SkeletonBar w="50%" h={ 9 } />
		</div>
	);
}

function FaqSkeleton(): JSX.Element {
	return (
		<div style={ { ...COL, gap: 10 } }>
			{ [ 0, 1, 2 ].map( ( i ) => (
				<div key={ i } style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, width: '90%' } }>
					<SkeletonBar w={ i === 0 ? '62%' : '48%' } h={ 12 } />
					<SkeletonBox w={ 16 } h={ 16 } style={ { borderRadius: 4, flex: '0 0 auto' } } />
				</div>
			) ) }
		</div>
	);
}

function TestimonialSkeleton(): JSX.Element {
	return (
		<div style={ { ...COL, gap: 14 } }>
			<div style={ { display: 'flex', gap: 4 } }>
				{ [ 0, 1, 2, 3, 4 ].map( ( i ) => (
					<SkeletonBox key={ i } w={ 14 } h={ 14 } style={ { borderRadius: 3 } } />
				) ) }
			</div>
			<SkeletonBar w="66%" h={ 14 } />
			<SkeletonBar w="90%" h={ 9 } />
			<SkeletonBar w="80%" h={ 9 } />
			<div style={ { display: 'flex', alignItems: 'center', gap: 12, marginTop: 6 } }>
				<SkeletonCircle size={ 44 } />
				<div style={ { display: 'flex', flexDirection: 'column', gap: 6 } }>
					<SkeletonBar w={ 90 } h={ 9 } />
					<SkeletonBar w={ 64 } h={ 8 } />
				</div>
			</div>
		</div>
	);
}

function TextSkeleton(): JSX.Element {
	return (
		<div style={ { ...COL, gap: 9, alignItems: 'stretch' } }>
			<SkeletonBar w="100%" h={ 9 } />
			<SkeletonBar w="100%" h={ 9 } />
			<SkeletonBar w="94%" h={ 9 } />
			<SkeletonBar w="98%" h={ 9 } />
			<SkeletonBar w="60%" h={ 9 } />
		</div>
	);
}

function SeparatorSkeleton(): JSX.Element {
	return (
		<div style={ { ...ROW, justifyContent: 'center' } }>
			<SkeletonBar w="72%" h={ 4 } style={ { borderRadius: 2 } } />
		</div>
	);
}

function GridSkeleton(): JSX.Element {
	return (
		<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, width: '100%' } }>
			{ [ 0, 1, 2, 3 ].map( ( i ) => (
				<SkeletonBox key={ i } w="100%" h={ 64 } />
			) ) }
		</div>
	);
}

function InfoBoxSkeleton(): JSX.Element {
	return (
		<div style={ { ...COL, gap: 12 } }>
			<SkeletonCircle size={ 48 } />
			<SkeletonBar w="60%" h={ 14 } />
			<SkeletonBar w="90%" h={ 9 } />
			<SkeletonBar w="80%" h={ 9 } />
			<SkeletonBox w={ 116 } h={ 38 } style={ { borderRadius: 999, marginTop: 6 } } />
		</div>
	);
}

function BeforeAfterSkeleton(): JSX.Element {
	return (
		<div style={ { position: 'relative', width: '100%' } }>
			<SkeletonBox w="100%" h={ 150 } />
			{ /* A vertical divider + grip to read as a compare slider. */ }
			<span style={ { position: 'absolute', top: 0, bottom: 0, left: '52%', width: 3, background: '#c4cad2' } } />
			<span
				style={ {
					position: 'absolute',
					top: '50%',
					left: '52%',
					width: 34,
					height: 34,
					marginTop: -17,
					marginLeft: -17,
					borderRadius: '50%',
					background: '#c4cad2',
					border: '3px solid #fff',
				} }
			/>
		</div>
	);
}

function ContainerSkeleton(): JSX.Element {
	return (
		<div
			style={ {
				width: '100%',
				border: '2px dashed #dfe3e8',
				borderRadius: 8,
				padding: 18,
				display: 'flex',
				flexDirection: 'column',
				alignItems: 'center',
				gap: 10,
			} }
		>
			<SkeletonBar w="40%" h={ 14 } />
			<SkeletonBar w="88%" h={ 9 } />
			<SkeletonBar w="76%" h={ 9 } />
		</div>
	);
}

function variant( kind: string ): JSX.Element {
	switch ( kind ) {
		case 'countdown':
			return <CountdownSkeleton />;
		case 'counter':
			return <CounterSkeleton />;
		case 'social-icon':
			return <SocialIconSkeleton />;
		case 'button':
			return <ButtonSkeleton />;
		case 'image':
			return <ImageSkeleton />;
		case 'faq':
			return <FaqSkeleton />;
		case 'testimonial':
			return <TestimonialSkeleton />;
		case 'info-box':
			return <InfoBoxSkeleton />;
		case 'container':
			return <ContainerSkeleton />;
		case 'grid':
			return <GridSkeleton />;
		case 'separator':
			return <SeparatorSkeleton />;
		case 'before-after':
			return <BeforeAfterSkeleton />;
		case 'text':
			return <TextSkeleton />;
		case 'heading':
		default:
			return <HeadingSkeleton />;
	}
}

/**
 * Render the skeleton mock-up for a block's inserter preview, wrapped in the
 * shared bordered + centred frame.
 *
 * @param props.kind Block slug (e.g. 'heading', 'countdown'); unknown → heading.
 */
export function ExamplePreviewSkeleton( { kind }: { kind: string } ): JSX.Element {
	return <div style={ FRAME }>{ variant( kind ) }</div>;
}
