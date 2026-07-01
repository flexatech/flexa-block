=== Flexa Block ===
Contributors: flexatech
Tags: blocks, gutenberg, fse, container, layout
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A collection of lightweight, customizable Gutenberg blocks for building modern WordPress websites.

== Description ==
Flexa Block is a growing collection of modern Gutenberg blocks designed to help you build beautiful WordPress websites faster.

Instead of installing multiple block plugins, Flexa Block focuses on providing carefully crafted, lightweight, and highly customizable blocks that integrate naturally with the WordPress Block Editor.

**Features**

* Lightweight and performance-focused
* Native Gutenberg experience
* Responsive by default
* Clean and modern design
* Highly customizable
* No unnecessary bloat
* Regular updates

Whether you're building landing pages, business websites, blogs, or eCommerce stores, Flexa Block helps you create professional layouts with less effort.

More blocks and advanced features will be added in future releases.

**Source code for compiled JavaScript and CSS**

The plugin ships with minified/compiled JavaScript and CSS in `build/`. The human-readable source code for these assets is **publicly available** and maintained at:

https://github.com/flexatech/flexa-block

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
Version 1.0.0 ships with the **Container** block — a powerful layout wrapper that supports boxed and full-width layouts, custom backgrounds (including lazy-loaded images), dark mode, and responsive controls. More blocks will be added in future releases.

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

= 1.0.0 =
* Initial release with the Container block.
