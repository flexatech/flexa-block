
=== Flexa Block – Blocks & Page Builder for Gutenberg ===
Contributors: flexatech
Tags: blocks, block editor, fse, container, layout
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A collection of lightweight, customizable blocks for building modern WordPress websites with the block editor.

== Description ==
Flexa Block is a growing collection of modern Gutenberg blocks designed to help you build beautiful WordPress websites faster.

Instead of installing multiple block plugins, Flexa Block focuses on providing carefully crafted, lightweight, and highly customizable blocks that integrate naturally with the WordPress Block Editor.

📌 [**DEMO**](https://flexablock.com/)

**Features**

* Lightweight and performance-focused
* Native Gutenberg experience
* Front-end inline editing — click text on the live page and edit it in place
* Responsive by default
* Clean and modern design
* Highly customizable
* No unnecessary bloat
* Regular updates

**Front-end inline editing**

Fixing a typo no longer means opening the Block Editor. On the live page, logged-in users who can edit the post simply click the text, type, and save — the change is written straight back to the block, so your layout, styling and generated CSS stay exactly as they were. Every edit is recorded as a note on the post, and you decide from the settings page whether inline editing is on, which roles may use it, and which blocks it applies to.

Whether you're building landing pages, business websites, blogs, or eCommerce stores, Flexa Block helps you create professional layouts with less effort.

More blocks and advanced features will be added in future releases.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/flexa-block`, or install
   through the WordPress **Plugins** screen.
2. Activate the plugin through the **Plugins** screen.
3. Open **Flexa Block** from the admin sidebar (below Settings).

== Frequently Asked Questions ==

= Do I need to know how to code to use Flexa Block? =
No. Everything is controlled through the WordPress Block Editor. Just insert a block, adjust settings in the sidebar, and publish.

= Which WordPress themes does Flexa Block support? =
Flexa Block works with any theme that supports the Block Editor, including classic themes and Full Site Editing (FSE) themes.

= What blocks are included? =
Version 1.0.4 ships with a growing library of blocks, including: Container, Button, Heading, Image, Countdown, FAQ (with optional FAQPage schema), Social Icons, Grid, Testimonial, Separator, Text, Counter, Info Box, Slides, Social Share, Table of Contents, Before / After, Breadcrumb, Steps, Tabs, Video Popup, Comparison Table, Google Map, Images Gallery, Banner, CTA, a Subscribe Form, Pricing Table, Team Member, Post Grid, Post Filter, Progress Bar, Timeline, Icon, Icon List, Lottie Animation, Modal, Star Rating, Notice, Data Table, Taxonomy, RSS Feed, Facebook Feed, Instagram Feed and five WooCommerce single-product blocks. All blocks include skeleton inserter previews and JavaScript translations. More blocks will be added in future releases.

= Can I edit content directly on the live page? =
Yes. When you are logged in and allowed to edit the post, Flexa's editable blocks become click-to-edit on the front end: click a heading, paragraph, button label, list item or table cell, type your change, and press Save in the toolbar that appears. The edit is saved into the block itself — markup, block settings and generated CSS are preserved — and logged as a note on the post.

= Which blocks can be edited from the front end? =
Thirty-five blocks, covering every block whose text you type in yourself: Heading, Text, Button, Table of Contents, Progress Bar, Before / After, Star Rating, Modal, Notice, Banner, CTA, Info Box, Testimonial, Team Member, Countdown labels, all eleven Subscribe Form field labels, FAQ, Tabs, Steps, Timeline, Pricing Table, Counter, Icon List, Comparison Table and Data Table. Blocks that show generated or fetched content (Post Grid, Breadcrumb, WooCommerce product blocks, feeds) are not inline-editable, since their text does not live in the block.

= Who is allowed to edit on the front end, and can I turn it off? =
Administrators always can; Editors are allowed by default. Open **Flexa Block → Editing** to switch inline editing off entirely, change which roles may use it, or disable it for individual blocks. Users still need the normal WordPress permission to edit that particular post.

= Will Flexa Block slow down my site? =
No. CSS is generated once at save time and cached per post. Only the styles actually used on a given page are loaded — there are no large catch-all stylesheets.

= Does Flexa Block support dark mode? =
Yes. The Container block integrates with a site-wide dark mode toggle, which you can enable or disable from the **Flexa Block** settings page in the admin.

= How do I disable a block I don't need? =
Go to **Flexa Block** in the admin sidebar and open the **Blocks** tab. You can toggle individual blocks off — disabled blocks are removed from the Block Editor inserter.

= Where are the plugin settings? =
In the WordPress admin sidebar, click **Flexa Block** (located below Settings). From there you can control dark mode, CSS specificity, and which blocks are active.

= Is there a Pro version? =
Yes. Flexa Block Pro adds additional blocks and advanced features. It works alongside this free plugin.

== Changelog ==

= 1.0.5 =
* Banner: added a Content Box Width control that caps the width of the content box while keeping the background full-bleed, so a full-width banner can align its content with the site grid.
* Reworded the plugin name, description and tags around the WordPress block editor, and added a demo link to the readme.
* Plugin-check / WordPress.org review cleanups: output-escaping and input-sanitization tidy-ups in the Post Filter fields and the Filter: Reset, Search and Taxonomy blocks.

= 1.0.4 =
* Added five WooCommerce single-product blocks (grouped under a new WooCommerce category, shown only when WooCommerce is active): Product Name, Product Price (regular/sale price + configurable strike-through), Product Rating (stars / number / count with per-part styling), Product Image (thumbnail carousel with arrows, positions, autoplay and a fixed image height) and Product Details (Description / Additional information / Reviews tabs with a sliding indicator, real WooCommerce reviews, numbered/load-more pagination and per-tab styling).
* Added a Post Filter block with Search, Taxonomy and Reset children: a filter bar that drives a chosen Post Grid — search as you type, narrow by one or more taxonomies (dropdown or checkbox list) and clear everything with a reset link that appears only once there is something to clear. Each field has its own background, border, shadow and focus colour.
* Added a Notice block (info / success / warning / danger / neutral callout with an icon, title, message and an optional dismiss that can be remembered).
* Added a Data Table block (own columns and rows, styled header, striped rows, hover and first-column highlights, per-column alignment, cell borders, padding and typography, horizontal scroll past a max width).
* Added a Taxonomy block (terms of any taxonomy as a list, inline row, dropdown or grid, with counts, hierarchy, prefix/suffix, separator and styled term chips).
* Added an RSS Feed block (entries from any RSS/Atom URL as a list or grid, with item count, cache time, sort order, thumbnail, date, author, source, excerpt and a read-more link).
* Added Facebook Feed and Instagram Feed blocks — posts and media fetched server-side and cached (tokens never reach the browser), in grid, list, masonry, carousel, hover-overlay or card layouts.
* Post Grid: a styleable result count, aligned read-more buttons, a blockId that survives a reload, one typography panel per element, pagination that previews the active state on hover, and an empty state that no longer repeats itself.
* Block CSS is now generated for the current block-theme template (e.g. the WooCommerce single-product template), so blocks placed directly in a site-editor template render their styles on the front end.
* Front-end inline editing extended to five more blocks — Star Rating, Modal, Notice, Icon List and Data Table — bringing the total to 35 editable blocks.

= 1.0.3 =
* Added five new blocks: Pricing Table, Team Member, Post Grid, Progress Bar and Timeline.
* Front-end inline editor: logged-in users with edit permission can click text on the live page and edit it in place — changes save per block (markup and generated CSS preserved) and are recorded as post notes.
* Editable blocks (30): Heading, Text, Button, Table of Contents, Progress Bar, Before / After, Banner, CTA, Info Box, Testimonial, Team Member, Countdown, the Subscribe Form fields (Name, Email, Phone, Message, Website, Date, Select, Radio, Checkbox, Toggle, Upload), FAQ, Tabs, Steps, Timeline, Pricing Table, Counter and Comparison Table.
* New Editing settings tab: toggle the inline editor, choose which roles may edit (administrators always allowed, Editor on by default) and which blocks are editable.
* Admin dashboard redesign: a left sidebar with General / Blocks / Editing sections; blocks shown as a searchable, group-filtered card grid with previews and enable/disable-all controls.

= 1.0.2 =
* Added many new blocks: Counter, Info Box, Slides (with Slide), Social Share, Table of Contents, Before / After, Breadcrumb, Steps, Tabs, Video Popup, Comparison Table, Google Map, Images Gallery, Banner, CTA and an AJAX Subscribe Form with field child blocks.
* Container: added position and offset controls; Table of Contents supports a scrollable list.
* Video Popup: added an inline play mode and MP4 upload; Comparison Table gained dedicated badge styling.
* Grid layout refinements and a refreshed admin dashboard.

= 1.0.1 =
* Added ten new blocks: Button, Heading, Image, Countdown, FAQ (with optional FAQPage schema), Social Icons, Grid, Testimonial, Separator and Text.
* Added JavaScript translations and skeleton inserter previews for all blocks.
* Security: hardened CSS value sanitization — colour, gradient, enum, position and numeric attributes are validated against allow-lists at generation time before being printed inline.
* Fixed the admin menu position and the text-domain load hook.

= 1.0.0 =
* Initial release with the Container block.
