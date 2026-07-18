/**
 * Shared block icons — the single source of truth for every Flexa block glyph.
 *
 * Both the block registrations (`src/blocks/<slug>/index.tsx`) and the admin
 * dashboard import from here, so the icon shown in the inserter always matches
 * the one shown in the settings screen. Add a block's icon here, keyed by slug.
 */

interface BlockIcon {
	src: JSX.Element;
	foreground: string;
}

const FG = '#2563eb';

/** Slug → icon. Keep entries in the same order as the block registry. */
export const BLOCK_ICONS: Record< string, BlockIcon > = {
	container: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="16"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="3"
					y1="9"
					x2="21"
					y2="9"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	grid: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="16"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="12"
					y1="4"
					x2="12"
					y2="20"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="3"
					y1="12"
					x2="21"
					y2="12"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	slides: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="6"
					y="6"
					width="12"
					height="12"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M3.5 9v6M20.5 9v6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	slide: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="4"
					y="6"
					width="16"
					height="12"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	button: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="7"
					width="18"
					height="10"
					rx="5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="8"
					y1="12"
					x2="16"
					y2="12"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	heading: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M5 5v14M13 5v14M5 12h8"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<path
					d="M17 10h3v9"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	image: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="16"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle cx="8.5" cy="9" r="1.6" fill="currentColor" />
				<path
					d="M4 17l4.5-4.5 3.5 3.5 3-3L20 16.5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'before-after': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="5"
					width="18"
					height="14"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path d="M12 3v18" stroke="currentColor" strokeWidth="1.6" />
				<circle cx="12" cy="12" r="2.4" fill="currentColor" />
				<path
					d="M8 12H6M18 12h-2"
					stroke="currentColor"
					strokeWidth="1.4"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	countdown: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle
					cx="12"
					cy="13"
					r="8"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M12 9v4l2.5 2.5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="9"
					y1="2.5"
					x2="15"
					y2="2.5"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	faq: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="4.5"
					rx="1.2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<rect
					x="3"
					y="11.5"
					width="18"
					height="4.5"
					rx="1.2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M17.5 6.25h.01M17.5 13.75h.01"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'social-icon': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle
					cx="6"
					cy="12"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="16.5"
					cy="5.5"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="16.5"
					cy="18.5"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M8.3 10.7l5.9-3.7M8.3 13.3l5.9 3.7"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'social-share': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle
					cx="18"
					cy="5.5"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="6"
					cy="12"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="18"
					cy="18.5"
					r="2.6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M8.3 10.7l7.4-4.2M8.3 13.3l7.4 4.2"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	text: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<line
					x1="4"
					y1="6"
					x2="20"
					y2="6"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
				<line
					x1="4"
					y1="10"
					x2="20"
					y2="10"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
				<line
					x1="4"
					y1="14"
					x2="14"
					y2="14"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
				<line
					x1="4"
					y1="18"
					x2="17"
					y2="18"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	separator: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<line
					x1="3"
					y1="12"
					x2="21"
					y2="12"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<circle cx="6" cy="12" r="0.6" fill="currentColor" />
				<circle cx="18" cy="12" r="0.6" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	testimonial: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M4 4h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9l-4 4v-4H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinejoin="round"
				/>
				<path
					d="m12 7 1.1 2.3 2.4.3-1.8 1.7.5 2.4-2.2-1.2-2.2 1.2.5-2.4L8.5 9.6l2.4-.3L12 7Z"
					fill="currentColor"
				/>
			</svg>
		),
		foreground: FG,
	},
	counter: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M4 20V10M12 20V4M20 20v-7"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'info-box': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="16"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="8"
					cy="9"
					r="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="12"
					y1="8"
					x2="18"
					y2="8"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="6"
					y1="14"
					x2="18"
					y2="14"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="6"
					y1="17"
					x2="15"
					y2="17"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	'table-of-content': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<line
					x1="4"
					y1="6"
					x2="6"
					y2="6"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<line
					x1="9"
					y1="6"
					x2="20"
					y2="6"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<line
					x1="4"
					y1="12"
					x2="6"
					y2="12"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<line
					x1="9"
					y1="12"
					x2="20"
					y2="12"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<line
					x1="4"
					y1="18"
					x2="6"
					y2="18"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
				<line
					x1="9"
					y1="18"
					x2="20"
					y2="18"
					stroke="currentColor"
					strokeWidth="1.8"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'comparison-table': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="18"
					height="16"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="3"
					y1="9"
					x2="21"
					y2="9"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="9"
					y1="9"
					x2="9"
					y2="20"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="15"
					y1="9"
					x2="15"
					y2="20"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	breadcrumb: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M3 6h6M3 12h6M3 18h6"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
				<path
					d="M13 7l3 5-3 5M18 7l3 5-3 5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'video-popup': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="5"
					width="18"
					height="14"
					rx="2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path d="M10 9.2v5.6l5-2.8z" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	tabs: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="5"
					width="18"
					height="14"
					rx="1.4"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<path
					d="M3 9h18"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<rect
					x="4.5"
					y="6.2"
					width="4.5"
					height="1.6"
					rx="0.8"
					fill="currentColor"
				/>
			</svg>
		),
		foreground: FG,
	},
	steps: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle
					cx="6"
					cy="6"
					r="2.5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<circle
					cx="6"
					cy="18"
					r="2.5"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="6"
					y1="8.5"
					x2="6"
					y2="15.5"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<line
					x1="11"
					y1="6"
					x2="20"
					y2="6"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
				<line
					x1="11"
					y1="18"
					x2="20"
					y2="18"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinecap="round"
				/>
			</svg>
		),
		foreground: FG,
	},
	'google-map': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
					strokeLinejoin="round"
				/>
				<circle
					cx="12"
					cy="11"
					r="2.2"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
	'images-gallery': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect
					x="3"
					y="4"
					width="8"
					height="8"
					rx="1.4"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<rect
					x="13"
					y="4"
					width="8"
					height="8"
					rx="1.4"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<rect
					x="3"
					y="14"
					width="8"
					height="6"
					rx="1.4"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
				<rect
					x="13"
					y="14"
					width="8"
					height="6"
					rx="1.4"
					fill="none"
					stroke="currentColor"
					strokeWidth="1.6"
				/>
			</svg>
		),
		foreground: FG,
	},
		banner: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="7" y1="10" x2="17" y2="10" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
					<line x1="9" y1="13" x2="15" y2="13" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
					<rect x="9.5" y="15.2" width="5" height="2.2" rx="1.1" fill="currentColor" />
				</svg>
			),
			foreground: FG,
		},
		cta: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<line x1="6" y1="7" x2="18" y2="7" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
					<line x1="8" y1="10.5" x2="16" y2="10.5" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
					<rect x="7" y="13.5" width="10" height="4.5" rx="2.25" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="10" y1="15.75" x2="14" y2="15.75" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
	'subscribe-form': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="4" width="18" height="16" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="7" y1="9" x2="17" y2="9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="7" y1="12" x2="14" y2="12" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<rect x="7" y="15" width="6" height="2.6" rx="1.3" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-email': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<path d="M4 8l8 5 8-5" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-name': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="8" width="18" height="8" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="6" y1="12" x2="7" y2="12" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-phone': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="7" y="3" width="10" height="18" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="10.5" y1="18" x2="13.5" y2="18" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-textarea': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="6" y1="9" x2="18" y2="9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="6" y1="12" x2="18" y2="12" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="6" y1="15" x2="13" y2="15" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-select': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="7" width="18" height="10" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<path d="M14 11l2 2 2-2" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-radio': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle cx="7" cy="8" r="2.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<circle cx="7" cy="8" r="0.9" fill="currentColor" />
				<circle cx="7" cy="16" r="2.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="12" y1="8" x2="19" y2="8" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="12" y1="16" x2="19" y2="16" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-checkbox': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="4" y="5.5" width="6.5" height="6.5" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<path d="M5.5 8.8l1.4 1.4 2.2-2.4" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
				<line x1="13" y1="8.5" x2="20" y2="8.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="4" y1="16.5" x2="20" y2="16.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-date': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" strokeWidth="1.6" />
				<line x1="8" y1="3" x2="8" y2="6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="16" y1="3" x2="16" y2="6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<rect x="7" y="12" width="3" height="3" rx="0.6" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-url': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M10 14a3.5 3.5 0 0 0 5 0l2.5-2.5a3.5 3.5 0 0 0-5-5L11 8" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M14 10a3.5 3.5 0 0 0-5 0l-2.5 2.5a3.5 3.5 0 0 0 5 5L13 16" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-toggle': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="2" y="7" width="20" height="10" rx="5" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<circle cx="16" cy="12" r="3" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-upload': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M12 15V5" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<path d="M8.5 8.5L12 5l3.5 3.5" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M5 15v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'subscribe-form-hidden': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6Z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
				<circle cx="12" cy="12" r="2.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="4" y1="20" x2="20" y2="4" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'pricing-table': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="4" width="5.5" height="16" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="9.25" y="4" width="5.5" height="16" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="15.5" y="4" width="5.5" height="16" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="10.5" y1="8" x2="13.5" y2="8" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'team-member': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<path d="M5.5 19a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'post-grid': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="4" width="8" height="7" rx="1.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="13" y="4" width="8" height="7" rx="1.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="3" y="13" width="8" height="7" rx="1.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="13" y="13" width="8" height="7" rx="1.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
			</svg>
		),
		foreground: FG,
	},
	'post-filter': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'filter-search': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<circle cx="11" cy="11" r="6" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="15.5" y1="15.5" x2="20" y2="20" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
	'filter-taxonomy': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<path d="m8 10.5 2.5 2.5L16 8" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'filter-reset': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M20 12a8 8 0 1 1-2.6-5.9" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<path d="M20 4v4h-4" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		foreground: FG,
	},
	'process-bar': {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<rect x="3" y="9" width="18" height="6" rx="3" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<rect x="5" y="11" width="9" height="2" rx="1" fill="currentColor" />
			</svg>
		),
		foreground: FG,
	},
	timeline: {
		src: (
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<line x1="12" y1="3" x2="12" y2="21" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<circle cx="12" cy="7" r="2.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<circle cx="12" cy="16" r="2.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
				<line x1="14.5" y1="7" x2="20" y2="7" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				<line x1="4" y1="16" x2="9.5" y2="16" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
			</svg>
		),
		foreground: FG,
	},
		icon: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="m12 3 2.6 5.6 6.1.7-4.5 4.1 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7L12 3Z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
				</svg>
			),
			foreground: FG,
		},
		"icon-list": {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<circle cx="5" cy="6" r="1.8" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<circle cx="5" cy="12" r="1.8" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<circle cx="5" cy="18" r="1.8" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="10" y1="6" x2="20" y2="6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
					<line x1="10" y1="12" x2="20" y2="12" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
					<line x1="10" y1="18" x2="16" y2="18" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		"star-rating": {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="m6 8 .9 2 2.1.2-1.6 1.4.5 2.1L6 12.6 4.1 13.7l.5-2.1L3 10.2l2.1-.2L6 8Z" fill="currentColor" />
					<path d="m12 8 .9 2 2.1.2-1.6 1.4.5 2.1-1.9-1.1-1.9 1.1.5-2.1L9 10.2l2.1-.2L12 8Z" fill="currentColor" />
					<path d="m18 8 .9 2 2.1.2-1.6 1.4.5 2.1-1.9-1.1-1.9 1.1.5-2.1L15 10.2l2.1-.2L18 8Z" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round" />
				</svg>
			),
			foreground: FG,
		},
		modal: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="2" y="4" width="20" height="16" rx="2" fill="none" stroke="currentColor" strokeWidth="1.4" opacity="0.4" />
					<rect x="6" y="7" width="12" height="10" rx="1.6" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="8.5" y1="10.5" x2="15.5" y2="10.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
					<line x1="8.5" y1="13.5" x2="13" y2="13.5" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		lottie: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<path d="M10 8.5v7l6-3.5-6-3.5Z" fill="currentColor" />
				</svg>
			),
			foreground: FG,
		},
		notice: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="12" y1="8" x2="12" y2="8.01" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
					<line x1="12" y1="11" x2="12" y2="16" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		'facebook-feed': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<path d="M14 8h-1.5c-.8 0-1.5.7-1.5 1.5V11H9.5v2H11v5h2v-5h1.6l.4-2H13V9.8c0-.4.2-.8.7-.8H14V8Z" fill="currentColor" />
				</svg>
			),
			foreground: FG,
		},
		'instagram-feed': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<circle cx="12" cy="12" r="3.6" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<circle cx="16.6" cy="7.4" r="1.1" fill="currentColor" />
				</svg>
			),
			foreground: FG,
		},
		taxonomy: {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h6.7c.4 0 .8.16 1.06.44l6.3 6.3a1.5 1.5 0 0 1 0 2.12l-6.7 6.7a1.5 1.5 0 0 1-2.12 0l-6.3-6.3A1.5 1.5 0 0 1 4 12.2V5.5Z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
					<circle cx="8.5" cy="8.5" r="1.3" fill="currentColor" />
				</svg>
			),
			foreground: FG,
		},
		'data-table': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3" y="4" width="18" height="16" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" strokeWidth="1.6" />
					<line x1="9" y1="9" x2="9" y2="20" stroke="currentColor" strokeWidth="1.6" />
					<line x1="3" y1="14.5" x2="21" y2="14.5" stroke="currentColor" strokeWidth="1.4" />
				</svg>
			),
			foreground: FG,
		},
		'product-name': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M4 7h16" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
					<path d="M4 12h11" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
					<path d="M4 17h7" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		'product-price': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="8.2" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<path d="M14.5 9.2c-.6-.8-1.6-1.2-2.6-1.2-1.5 0-2.6.9-2.6 2.1 0 2.9 5.4 1.6 5.4 4.4 0 1.3-1.2 2.2-2.8 2.2-1.1 0-2.2-.5-2.8-1.4" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
					<path d="M12 6.2v11.6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		'product-rating': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 3.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L12 16.77 6.79 19.5l.99-5.8-4.21-4.1 5.82-.85L12 3.5z" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
				</svg>
			),
			foreground: FG,
		},
		'product-detail': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3" y="4" width="18" height="16" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" strokeWidth="1.6" />
					<rect x="3" y="4" width="7" height="5" rx="2" fill="currentColor" opacity="0.18" stroke="none" />
					<line x1="6" y1="13" x2="18" y2="13" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
					<line x1="6" y1="16.5" x2="14" y2="16.5" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
				</svg>
			),
			foreground: FG,
		},
		'product-image': {
			src: (
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<rect x="3" y="3.5" width="18" height="13" rx="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
					<path d="M6 13.5l3.2-3.6 2.6 2.7 2.4-2.1 3.8 3.9" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
					<circle cx="9" cy="7.5" r="1.3" fill="currentColor" />
					<rect x="4" y="19" width="4.5" height="2.5" rx="0.8" fill="currentColor" opacity="0.5" stroke="none" />
					<rect x="9.8" y="19" width="4.5" height="2.5" rx="0.8" fill="currentColor" opacity="0.25" stroke="none" />
					<rect x="15.6" y="19" width="4.5" height="2.5" rx="0.8" fill="currentColor" opacity="0.25" stroke="none" />
				</svg>
			),
			foreground: FG,
		},
};
