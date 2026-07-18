<?php
declare(strict_types=1);
/**
 * Block Manager — registers blocks and the editor category.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry and registration for Flexa blocks.
 */
class Block_Manager {

	/**
	 * Block registry. Order defines inserter order.
	 *
	 * @var array
	 */
	const BASE_BLOCKS = [
		[
			'slug'        => 'container',
			'name'        => 'flexa/container',
			'title'       => 'Container',
			'description' => 'Section wrapper with per-device flex layout, background, borders, spacing and light/dark colors.',
			'category'    => 'layout',
			'is_core'     => true,
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Container_CSS',
		],
		[
			'slug'        => 'grid',
			'name'        => 'flexa/grid',
			'title'       => 'Grid',
			'description' => 'CSS Grid section that arranges inner blocks into per-device columns and rows, with track gaps, item and track alignment, background, borders, spacing and light/dark colors.',
			'category'    => 'layout',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Grid_CSS',
		],
		[
			'slug'        => 'slides',
			'name'        => 'flexa/slides',
			'title'       => 'Slider',
			'description' => 'Carousel of slides with autoplay, loop, transition effects, arrow and dot navigation, per-device slides-per-view and light/dark styling.',
			'category'    => 'layout',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Slides_CSS',
		],
		[
			'slug'        => 'slide',
			'name'        => 'flexa/slide',
			'title'       => 'Slide',
			'description' => 'A single slide inside a Slider — holds any blocks, with its own background, padding, content max-width and content alignment.',
			'category'    => 'layout',
			'is_child'    => true,
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Slide_CSS',
		],
		[
			'slug'        => 'button',
			'name'        => 'flexa/button',
			'title'       => 'Button',
			'description' => 'Theme-aware call-to-action button with variants, sizes, icons, hover colours and per-device typography.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Button_CSS',
		],
		[
			'slug'        => 'heading',
			'name'        => 'flexa/heading',
			'title'       => 'Heading',
			'description' => 'Rich heading with optional subheading and separator, per-device typography, light/dark colours, gradient text and text effects.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Heading_CSS',
		],
		[
			'slug'        => 'image',
			'name'        => 'flexa/image',
			'title'       => 'Image',
			'description' => 'Media image with per-device width and alignment, aspect-ratio lock, object-fit, shape masks, colour/gradient overlay, hover motion, caption and link or lightbox.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Image_CSS',
		],
		[
			'slug'        => 'before-after',
			'name'        => 'flexa/before-after',
			'title'       => 'Before / After',
			'description' => 'Drag-to-reveal image comparison slider — pick a before and an after image, split them horizontally or vertically, and let visitors wipe between the two with a handle or on hover.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Before_After_CSS',
		],
		[
			'slug'        => 'countdown',
			'name'        => 'flexa/countdown',
			'title'       => 'Countdown',
			'description' => 'Live countdown to a target date with selectable units, separators, labels, per-device typography, light/dark colours and a completion action.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Countdown_CSS',
		],
		[
			'slug'        => 'faq',
			'name'        => 'flexa/faq',
			'title'       => 'FAQ',
			'description' => 'Expandable question-and-answer accordion with open-state icons, dividers, per-question and answer styling, light/dark colours and optional FAQ schema.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Faq_CSS',
		],
		[
			'slug'        => 'social-icon',
			'name'        => 'flexa/social-icon',
			'title'       => 'Social Icons',
			'description' => 'Linked row of social network icons — official brand artwork or custom light/dark tints per icon, with per-device size, gap, alignment and a hover motion.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Social_Icon_CSS',
		],
		[
			'slug'        => 'social-share',
			'name'        => 'flexa/social-share',
			'title'       => 'Social Share',
			'description' => 'Row of share buttons that post the current page to Facebook, X, LinkedIn or Pinterest — with per-device size, gap, alignment, a button shape, official brand or custom-tinted icons and a hover motion.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Social_Share_CSS',
		],
		[
			'slug'        => 'text',
			'name'        => 'flexa/text',
			'title'       => 'Text',
			'description' => 'Rich paragraph with per-device alignment, typography, solid or gradient text colour, hover colour, stroke and shadow effects, blend mode and a drop cap.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Text_CSS',
		],
		[
			'slug'        => 'separator',
			'name'        => 'flexa/separator',
			'title'       => 'Separator',
			'description' => 'Horizontal divider line with per-device width, thickness and alignment, a solid/dashed/dotted/double style and light/dark colour.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Separator_CSS',
		],
		[
			'slug'        => 'testimonial',
			'name'        => 'flexa/testimonial',
			'title'       => 'Testimonial',
			'description' => 'Single testimonial card - star rating, headline, quote and an author block (avatar, name, role) with per-device typography, spacing, alignment and light/dark colours.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Testimonial_CSS',
		],
		[
			'slug'        => 'counter',
			'name'        => 'flexa/counter',
			'title'       => 'Counter',
			'description' => 'Grid of stat counters that count up when scrolled into view — each with an icon, a prefixed/suffixed number and a label, plus per-device columns, typography, light/dark colours and a per-item box.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Counter_CSS',
		],
		[
			'slug'        => 'info-box',
			'name'        => 'flexa/info-box',
			'title'       => 'Info Box',
			'description' => 'Icon or image paired with a prefix, title and description, an optional divider and a call-to-action button. Media sits above or beside the text, with per-device typography, spacing and light/dark colours.',
			'category'    => 'design',
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Info_Box_CSS',
		],
			[
				'slug'        => 'table-of-content',
				'name'        => 'flexa/table-of-content',
				'title'       => 'Table of Contents',
				'description' => 'Auto-built list of the page\'s headings with jump links, chosen heading levels, bullet or numbered markers, smooth scroll, an optional collapse toggle and light/dark styling.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Table_Of_Content_CSS',
			],
			[
				'slug'        => 'comparison-table',
				'name'        => 'flexa/comparison-table',
				'title'       => 'Comparison Table',
				'description' => 'Side-by-side plan and feature comparison grid — list your columns (plans) and feature rows, mark each cell with a check, a cross or plain text, spotlight one column and let the table scroll on small screens.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Comparison_Table_CSS',
			],
			[
				'slug'        => 'breadcrumb',
				'name'        => 'flexa/breadcrumb',
				'title'       => 'Breadcrumb',
				'description' => 'Hierarchical breadcrumb trail — auto-built from the current page\'s ancestors or listed by hand, with a home icon, a chosen separator, link / current / separator colours, per-device typography and optional BreadcrumbList schema.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Breadcrumb_CSS',
			],
			[
				'slug'        => 'video-popup',
				'name'        => 'flexa/video-popup',
				'title'       => 'Video Popup',
				'description' => 'Play a YouTube, Vimeo or MP4 video in a modal lightbox — triggered by a thumbnail, a button or a text link, with a play icon, aspect ratio, autoplay and a click-to-close backdrop.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Video_Popup_CSS',
			],
			[
				'slug'        => 'tabs',
				'name'        => 'flexa/tabs',
				'title'       => 'Tabs',
				'description' => 'Organise content into a horizontal tab bar with optional icons, an underline, pill or boxed tab style, per-tab and content typography, light/dark colours, and a bar that collapses to an accordion on mobile.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Tabs_CSS',
			],
			[
				'slug'        => 'steps',
				'name'        => 'flexa/steps',
				'title'       => 'Steps',
				'description' => 'Vertical timeline of process steps — each with a numbered, icon or image marker, a connecting line, a title and description, per-status marker accents and light/dark styling.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Steps_CSS',
			],
			[
				'slug'        => 'google-map',
				'name'        => 'flexa/google-map',
				'title'       => 'Google Map',
				'description' => 'Embed a Google Map for any address or place — with a zoom level, per-device height, boxed or full width, background, border, radius, shadow and light/dark styling.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Google_Map_CSS',
			],
			[
				'slug'        => 'images-gallery',
				'name'        => 'flexa/images-gallery',
				'title'       => 'Images Gallery',
				'description' => 'Multi-image gallery in a grid, masonry or justified-tiled layout with per-device columns and gap, hover motion, colour or gradient overlay, captions and a prev/next lightbox.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Images_Gallery_CSS',
			],
			[
				'slug'        => 'banner',
				'name'        => 'flexa/banner',
				'title'       => 'Banner',
				'description' => 'Promotional banner / hero section — a heading, description and call-to-action buttons laid over a colour, gradient or image background with an overlay, a banner min-height and boxed or full width.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Banner_CSS',
			],
			[
				'slug'        => 'cta',
				'name'        => 'flexa/cta',
				'title'       => 'CTA',
				'description' => 'Call-to-action banner — a heading, description and primary plus optional secondary button, arranged centred or split (text one side, buttons the other), with background, border, shadow and light/dark styling.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Cta_CSS',
			],
			[
				'slug'        => 'subscribe-form',
				'name'        => 'flexa/subscribe-form',
				'title'       => 'Subscribe Form',
				'description' => 'Newsletter subscribe form built from field child blocks — collects the entry over AJAX and emails it to you, with a submit button, success / error messages, honeypot spam guard and styling for the fields, inputs, button and messages.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Subscribe_Form_CSS',
			],
			[
				'slug'        => 'subscribe-form-email',
				'name'        => 'flexa/subscribe-form-email',
				'title'       => 'Email',
				'description' => 'An email input for a Subscribe Form — with a label, placeholder, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-name',
				'name'        => 'flexa/subscribe-form-name',
				'title'       => 'Name',
				'description' => 'A text input for a Subscribe Form — with a label, placeholder, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-phone',
				'name'        => 'flexa/subscribe-form-phone',
				'title'       => 'Phone',
				'description' => 'A telephone input for a Subscribe Form — with a label, placeholder, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-textarea',
				'name'        => 'flexa/subscribe-form-textarea',
				'title'       => 'Message',
				'description' => 'A multi-line message input for a Subscribe Form — with a label, placeholder, row count, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-select',
				'name'        => 'flexa/subscribe-form-select',
				'title'       => 'Select',
				'description' => 'A dropdown select for a Subscribe Form — with a label, a prompt, a list of choices, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-radio',
				'name'        => 'flexa/subscribe-form-radio',
				'title'       => 'Radio',
				'description' => 'A single-choice radio group for a Subscribe Form — with a label, a list of choices, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-checkbox',
				'name'        => 'flexa/subscribe-form-checkbox',
				'title'       => 'Checkbox',
				'description' => 'A checkbox group for a Subscribe Form — one box for a consent opt-in, or several for a multi-choice list — with a label, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-date',
				'name'        => 'flexa/subscribe-form-date',
				'title'       => 'Date',
				'description' => 'A date picker for a Subscribe Form — with a label, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-url',
				'name'        => 'flexa/subscribe-form-url',
				'title'       => 'Website',
				'description' => 'A URL input for a Subscribe Form — with a label, placeholder, required toggle and column width.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-toggle',
				'name'        => 'flexa/subscribe-form-toggle',
				'title'       => 'Toggle',
				'description' => 'An on/off switch for a Subscribe Form — with a label, required toggle and column width. Submits "Yes" when switched on.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-upload',
				'name'        => 'flexa/subscribe-form-upload',
				'title'       => 'Upload',
				'description' => 'A file-upload input for a Subscribe Form — with a label, accepted file types, multiple-file and max-size options, required toggle and column width. Chosen files are attached to the notification email.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'subscribe-form-hidden',
				'name'        => 'flexa/subscribe-form-hidden',
				'title'       => 'Hidden',
				'description' => 'An invisible field for a Subscribe Form that submits a fixed value — handy for tagging entries with a source or campaign.',
				'category'    => 'design',
				'is_child'    => true,
			],
			[
				'slug'        => 'pricing-table',
				'name'        => 'flexa/pricing-table',
				'title'       => 'Pricing Table',
				'description' => 'Side-by-side pricing plans — each column carries a name, price, billing period, feature list, a highlighted "most popular" flag and a call-to-action, with an optional monthly/yearly toggle, per-device typography and light/dark colours.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Pricing_Table_CSS',
			],
			[
				'slug'        => 'team-member',
				'name'        => 'flexa/team-member',
				'title'       => 'Team Member',
				'description' => 'Profile card for one team member — a photo, name, role and bio with linked social icons, the photo placed above, left or right of the text, per-element typography, light/dark colours and hover accents.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Team_Member_CSS',
			],
			[
				'slug'        => 'post-grid',
				'name'        => 'flexa/post-grid',
				'title'       => 'Post Grid',
				'description' => 'Dynamic grid of posts pulled by a query — pick the post type, count, order and taxonomy filter, show or hide the featured image, title, meta, excerpt and read-more button, set per-device columns and image ratio, and page through the results.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Post_Grid_CSS',
			],
			[
				'slug'        => 'post-filter',
				'name'        => 'flexa/post-filter',
				'title'       => 'Post Filter',
				'description' => 'A filter bar for a Post Grid — drop in a search box, category and tag filters and a reset link, pick the grid they drive, and visitors narrow the results without leaving the page. Works with JavaScript off, and styles every control from one place.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Post_Filter_CSS',
			],
			[
				'slug'        => 'filter-search',
				'name'        => 'flexa/filter-search',
				'title'       => 'Filter: Search',
				'description' => 'A search box for a Post Filter — visitors type a keyword and the target Post Grid narrows to matching posts.',
				'category'    => 'design',
				'is_child'    => true,
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Filter_Field_CSS',
			],
			[
				'slug'        => 'filter-taxonomy',
				'name'        => 'flexa/filter-taxonomy',
				'title'       => 'Filter: Taxonomy',
				'description' => 'A category, tag or custom-taxonomy filter for a Post Filter — as a dropdown or a checkbox list. Add it twice to filter by two taxonomies at once.',
				'category'    => 'design',
				'is_child'    => true,
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Filter_Field_CSS',
			],
			[
				'slug'        => 'filter-reset',
				'name'        => 'flexa/filter-reset',
				'title'       => 'Filter: Reset',
				'description' => 'A reset control for a Post Filter — clears the search and every term filter applied to the target Post Grid.',
				'category'    => 'design',
				'is_child'    => true,
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Filter_Field_CSS',
			],
			[
				'slug'        => 'rss',
				'name'        => 'flexa/rss',
				'title'       => 'RSS Feed',
				'description' => 'Show the newest entries from any RSS or Atom feed URL as a list or grid — set how many items, cache time and sort order, toggle the thumbnail, title, date, author, source and excerpt, add a read-more link that can open in a new tab, and style the card typography, colours and button.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Rss_CSS',
			],
			[
				'slug'        => 'process-bar',
				'name'        => 'flexa/process-bar',
				'title'       => 'Progress Bar',
				'description' => 'Animated progress indicator in a line, circle or semi-circle layout — set the value as a percentage or absolute count, show a title and counter, choose fill and track colours (light/dark), a solid or striped fill and an on-scroll fill animation.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Process_Bar_CSS',
			],
			[
				'slug'        => 'timeline',
				'name'        => 'flexa/timeline',
				'title'       => 'Timeline',
				'description' => 'Chronological timeline of events — each entry with a title, description, date, an optional image and a marker icon, arranged down a connector line alternating left/right or on a single side, with per-element typography, marker and line styling and light/dark colours.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Timeline_CSS',
			],
			[
				'slug'        => 'icon',
				'name'        => 'flexa/icon',
				'title'       => 'Icon',
				'description' => 'A single SVG glyph — chosen from the icon library or uploaded — with per-device size and alignment, a default, stacked or framed view in a square, rounded or circle shape, icon and frame colours with hover, and an optional link.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Icon_CSS',
			],
			[
				'slug'        => 'icon-list',
				'name'        => 'flexa/icon-list',
				'title'       => 'Icon List',
				'description' => 'A list or grid of icon-and-text rows — each with its own glyph, label and link — with the icon before or after the text, per-device columns and gaps, block-level icon styling (size, view, shape, colours with hover, frame) and text typography and colours.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Icon_List_CSS',
			],
			[
				'slug'        => 'star-rating',
				'name'        => 'flexa/star-rating',
				'title'       => 'Star Rating',
				'description' => 'A star rating shown out of a chosen maximum — set the value and range, the star size, gap and marked/unmarked colours, lay it out inline or stacked with an optional title, and expose an aggregateRating schema.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Star_Rating_CSS',
			],
			[
				'slug'        => 'modal',
				'name'        => 'flexa/modal',
				'title'       => 'Modal',
				'description' => 'A pop-up dialog opened by a button, text, image or icon trigger and filled with any inner blocks — set the modal width, max-height, padding, background and radius, the overlay colour, the close button, and show-once, click-overlay and press-escape behaviour.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Modal_CSS',
			],
			[
				'slug'        => 'lottie',
				'name'        => 'flexa/lottie',
				'title'       => 'Lottie Animation',
				'description' => 'Play a Lottie animation from an uploaded JSON/.lottie file or a remote URL — with per-device width, height and alignment, loop, speed and reverse options and a play trigger of autoplay, hover, click, scroll or on-view.',
				'category'    => 'design',
				'generator'   => 'Flexa\\Block\\CSS_Generators\\Lottie_CSS',
			],
				[
					'slug'        => 'notice',
					'name'        => 'flexa/notice',
					'title'       => 'Notice',
					'description' => 'Highlight an announcement, tip or warning — pick an info, success, warning, danger or neutral style, show an icon beside or above the text, add a title and message, align it, and let visitors dismiss it (optionally remembering the dismissal), with per-element typography and light/dark colours.',
					'category'    => 'design',
					'generator'   => 'Flexa\\Block\\CSS_Generators\\Notice_CSS',
				],
				[
					'slug'        => 'facebook-feed',
					'name'        => 'flexa/facebook-feed',
					'title'       => 'Facebook Feed',
					'description' => 'Show the latest posts from a Facebook Page via the Graph API (token kept server-side, cached) — in a grid, list, masonry or carousel, toggle the profile picture, page name, timestamp, post image, message, reactions, comments and shares, link posts back to Facebook, page through the results, and style the card typography and colours.',
					'category'    => 'design',
					'generator'   => 'Flexa\\Block\\CSS_Generators\\Facebook_Feed_CSS',
				],
				[
					'slug'        => 'instagram-feed',
					'name'        => 'flexa/instagram-feed',
					'title'       => 'Instagram Feed',
					'description' => 'Display recent Instagram media from an access token (fetched server-side and cached) — in a grid, hover-overlay, card or carousel layout with per-device columns, square or original thumbnails, an optional caption, date and profile name, links that open the post, and caption typography and colours.',
					'category'    => 'design',
					'generator'   => 'Flexa\\Block\\CSS_Generators\\Instagram_Feed_CSS',
				],
				[
					'slug'        => 'taxonomy',
					'name'        => 'flexa/taxonomy',
					'title'       => 'Taxonomy',
					'description' => 'List the terms of any taxonomy (categories, tags or custom) as a list, inline row, dropdown or grid — show the post count, include empty terms or the full hierarchy, limit and order them, add an icon or text prefix/suffix and a separator, and style each term chip with light/dark colours, hover, gap and padding.',
					'category'    => 'design',
					'generator'   => 'Flexa\\Block\\CSS_Generators\\Taxonomy_CSS',
				],
				[
					'slug'        => 'data-table',
					'name'        => 'flexa/data-table',
					'title'       => 'Data Table',
					'description' => 'Build a responsive data table from your own columns and rows — toggle and style the header, stripe alternating rows, highlight rows on hover and the first column, align each column, set cell borders, padding and typography, and cap the table width so wider content scrolls horizontally.',
					'category'    => 'design',
					'generator'   => 'Flexa\\Block\\CSS_Generators\\Data_Table_CSS',
				],
				[
					'slug'        => 'product-name',
					'name'        => 'flexa/product-name',
					'title'       => 'Product Name',
					'description' => 'Show the current WooCommerce product’s title on a single-product page — pick the heading tag, optionally link it to the product, and style typography, colour or gradient, hover colour, stroke, shadow, spacing, background, border, shadow and light/dark colours.',
					'category'    => 'woocommerce',
					'is_woo'      => true,
					'generator'   => 'Flexa\Block\CSS_Generators\Product_Name_CSS',
				],
				[
					'slug'        => 'product-price',
					'name'        => 'flexa/product-price',
					'title'       => 'Product Price',
					'description' => 'Show the current WooCommerce product’s price on a single-product page — regular price, sale price and currency are pulled straight from WooCommerce (no manual input); you only style it, with a configurable sale position, alignment and separate colour and typography for the regular and sale amounts.',
					'category'    => 'woocommerce',
					'is_woo'      => true,
					'generator'   => 'Flexa\Block\CSS_Generators\Product_Price_CSS',
				],
				[
					'slug'        => 'product-rating',
					'name'        => 'flexa/product-rating',
					'title'       => 'Product Rating',
					'description' => 'Show the current WooCommerce product’s average star rating on a single-product page — display stars, a numeric score or both, an optional review count, and style the star size, gap, filled and empty star colours, the numeric-score colour and typography, and the review-count typography and colours.',
					'category'    => 'woocommerce',
					'is_woo'      => true,
					'generator'   => 'Flexa\Block\CSS_Generators\Product_Rating_CSS',
				],
				[
					'slug'        => 'product-detail',
					'name'        => 'flexa/product-detail',
					'title'       => 'Product Details',
					'description' => 'Show the current WooCommerce product’s details on a single-product page as tabs — toggle the Description, Additional information and Reviews tabs, and style the tab titles (normal and active colours, typography, padding, gap) and the content area (typography, colour, padding), with a paginated Reviews list (numbered or load-more), per-element Reviews styling (title, author, date, stars, text) and Additional-information table styling (label/value colours + typography, cell borders and padding).',
					'category'    => 'woocommerce',
					'is_woo'      => true,
					'generator'   => 'Flexa\Block\CSS_Generators\Product_Detail_CSS',
				],
				[
					'slug'        => 'product-image',
					'name'        => 'flexa/product-image',
					'title'       => 'Product Image',
					'description' => 'Show the current WooCommerce product’s images on a single-product page — the featured image with a thumbnail carousel (bottom, left or right) showing a set number of thumbnails per view, a per-device gap, image scale, height or adaptive height, zoom-on-hover, optional autoplay, radius and alignment.',
					'category'    => 'woocommerce',
					'is_woo'      => true,
					'generator'   => 'Flexa\Block\CSS_Generators\Product_Image_CSS',
				],
	];

	/**
	 * Cached block list (BASE_BLOCKS after filtering).
	 *
	 * @var array|null
	 */
	private static $blocks = null;

	/**
	 * Registered block results.
	 *
	 * @var array
	 */
	private static $registered = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 5 );
		add_filter( 'block_categories_all', [ __CLASS__, 'register_category' ], 10, 1 );
	}

	/**
	 * Add the "Flexa" inserter category.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public static function register_category( $categories ) {
		$prepend = [
			[
				'slug'  => 'flexa',
				'title' => __( 'Flexa', 'flexa-block' ),
				'icon'  => null,
			],
		];

		// The product-* blocks use the "woocommerce" category. WooCommerce
		// registers it, but register it ourselves too (deduped) so the blocks are
		// grouped correctly even if our filter runs before WooCommerce's.
		$has_woo = false;
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'woocommerce' === $category['slug'] ) {
				$has_woo = true;
				break;
			}
		}
		if ( ! $has_woo && class_exists( 'WooCommerce' ) ) {
			$prepend[] = [
				'slug'  => 'woocommerce',
				'title' => __( 'WooCommerce', 'flexa-block' ),
				'icon'  => null,
			];
		}

		return array_merge( $prepend, $categories );
	}

	/**
	 * Get the block catalog (BASE_BLOCKS, filterable by add-ons).
	 *
	 * Add-ons can register their own blocks:
	 *   add_filter( 'flexa_block_blocks', function ( $blocks ) {
	 *       $blocks[] = [
	 *           'slug'        => 'hero',
	 *           'title'       => 'Hero',
	 *           'description' => '…',
	 *           'category'    => 'layout',
	 *           'path'        => FLEXA_BLOCK_PRO_DIR . 'build/blocks/hero',
	 *       ];
	 *       return $blocks;
	 *   } );
	 *
	 * @return array
	 */
	public static function get_blocks() {
		if ( null === self::$blocks ) {
			self::$blocks = apply_filters( 'flexa_block_blocks', self::BASE_BLOCKS );
		}
		return self::$blocks;
	}

	/**
	 * Register all blocks from the blocks/ directory.
	 */
	public static function register_blocks() {
		$build_dir  = FLEXA_BLOCK_DIR . 'build/blocks/';
		$blocks_dir = is_dir( $build_dir ) ? $build_dir : FLEXA_BLOCK_DIR . 'src/blocks/';

		foreach ( self::get_blocks() as $block ) {
			if ( empty( $block['slug'] ) ) {
				continue;
			}

			// WooCommerce-only blocks (product-*) are hidden entirely unless
			// WooCommerce is active, so they never appear in the inserter without it.
			if ( ! empty( $block['is_woo'] ) && ! class_exists( 'WooCommerce' ) ) {
				continue;
			}

			// Add-ons may register from their own directory via an explicit 'path'.
			$path = ! empty( $block['path'] ) ? $block['path'] : $blocks_dir . $block['slug'];

			if ( file_exists( $path . '/block.json' ) ) {
				$result = register_block_type( $path );
				if ( $result ) {
					self::$registered[ $block['slug'] ] = $result;
					self::set_block_translations( $result );
				}
			}
		}

		do_action( 'flexa_block_blocks_registered', self::$registered );
	}

	/**
	 * Load JS translations for a block's editor script(s).
	 *
	 * register_block_type() enqueues the editor script but does not wire up
	 * `wp.i18n` translations — do it here so strings in edit.tsx get localized.
	 *
	 * @param \WP_Block_Type $block_type Registered block type.
	 */
	private static function set_block_translations( $block_type ): void {
		if ( ! function_exists( 'wp_set_script_translations' ) ) {
			return;
		}
		$handles = $block_type->editor_script_handles;
		foreach ( (array) $handles as $handle ) {
			wp_set_script_translations( $handle, 'flexa-block', FLEXA_BLOCK_DIR . 'languages' );
		}
	}

	/**
	 * Get the catalog metadata (slug/title/description/category) for all blocks.
	 *
	 * @return array
	 */
	public static function get_block_catalog() {
		return self::get_blocks();
	}

	/**
	 * Get registered block slugs.
	 *
	 * @return array
	 */
	public static function get_registered_blocks() {
		return array_keys( self::$registered );
	}
}
