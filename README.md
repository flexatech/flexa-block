# Flexa Block

A responsive Gutenberg block collection with **dark mode** support and **save-time CSS generation**, **front-end editing** (change text, links, images and icons right on the page, with an optional review step), and an optional **MCP module** for AI clients. Ships the **Container** block as the canonical architecture reference for all future blocks.

- **Version:** 2.0.2
- **Requires:** WordPress 6.4+, PHP 7.4+ (the MCP module needs WordPress 7.0+)
- **License:** GPL-2.0-or-later

---

## Table of Contents
1. [Running the plugin](#1-running-the-plugin)
2. [Building](#2-building)
3. [Testing](#3-testing)
4. [Directory structure](#4-directory-structure)
5. [How it works](#5-how-it-works)

---

## 1. Running the plugin

The plugin works like any WordPress plugin. Two cases:

### A. Use only (no code changes)
The `build/` directory already contains compiled assets, so just:
1. Place the `flexa-block/` folder in `wp-content/plugins/`.
2. Go to **WordPress Admin → Plugins → Flexa Block → Activate**.
3. Open the editor (post/page or Site Editor), insert the **Container** block (Flexa group).

### B. Use and develop
Install dependencies and rebuild assets before activating - see [section 2](#2-building).

> **Environment note:** this project runs on **Local by Flywheel**. Local bundles PHP/MySQL but does **not expose `php`/`composer` on the system PATH**. `npm` commands work normally; `composer`/`phpunit` commands need extra setup - see [section 3](#3-testing).

---

## 2. Building

Requires: **Node.js** (≥ 18 recommended) and **npm**.

```bash
# From the plugin directory: wp-content/plugins/flexa-block

npm install          # install dependencies (first time only)

npm run build        # production build → outputs to build/
npm run start        # dev build + watch (rebuilds on src/ changes)
```

> **`npm run build` also generates the icon catalog.** It first runs
> `npm run icons` (`scripts/build-icon-catalog.cjs`), which writes
> `includes/icons/catalog.php`: the SVG of every icon the block editor's
> IconPicker offers (the glyphs in `src/shared/builtin-icons.json` and the
> `@wordpress/icons` library), rendered exactly as the picker renders them. The
> front-end editor never stores SVG sent by a browser; it looks icons up in this
> file. The file is committed, so a checkout works without a build. Re-run
> `npm run icons` after changing the builtin glyphs or upgrading
> `@wordpress/icons`.

> **Load-bearing: the `sideEffects` field in `package.json`.**
> Every block entry imports from the `@components` barrel, which `export *`s a
> dozen modules. Without that field webpack must assume all of them matter and
> keeps the lot — a 33-line block such as `subscribe-form-hidden` built to
> 180 KB, and the editor loaded 14 MB of block scripts on every post, since
> WordPress enqueues the editor script of *every* registered block whether or not
> it is used. Declaring only stylesheets side-effectful halves that to 7.2 MB
> while leaving all 264 built CSS files and every `view.js` byte-for-byte
> identical.
>
> `src/components/index.ts` is listed explicitly because that barrel opens with
> `import './editor.scss'` — the shared inspector design system. Webpack resolves
> a re-export straight to its source module and would otherwise never evaluate
> the barrel, silently dropping that stylesheet from all 66 editor bundles.
>
> **When adding a module-level side effect, add it to that list.** A top-level
> `addFilter`, `registerStore` or `domReady` in anything other than a block entry
> would be shaken out with no error and no failing test — the breakage only shows
> up in the editor. The audit:
> ```bash
> grep -rn "^import '[^']*';" src --include=*.ts --include=*.tsx
> grep -rnE "^[[:space:]]*(addFilter|addAction|registerPlugin|registerStore|domReady)\(" src --include=*.ts --include=*.tsx
> ```
> The first should return only stylesheets; the second, nothing.

Other commands:

```bash
npm run format       # auto-format code to WordPress standards
npm run lint:js      # run ESLint
npm run lint:css     # run Stylelint
npm run typecheck    # TypeScript type check (tsc --noEmit, no output files)
npm run check        # typecheck + JS tests
```

> **Source is TypeScript** (`.tsx`/`.ts`). Babel (via `@wordpress/scripts`) strips types at build time so `build/` still produces plain `.js`; type checking is a separate step with `npm run typecheck`. Attribute types live in [`src/types.ts`](src/types.ts); WP package declarations are loosely typed in [`src/global.d.ts`](src/global.d.ts).

Built assets are placed in `build/blocks/container/` and loaded by PHP at runtime (only on pages that actually use the block).

---

## 3. Testing

The plugin has **two test layers**:

| Layer | What it tests | Tool | Ready to run? |
|---|---|---|---|
| **JS** | Editor utils: responsive cascade, value formatting (`effective`, `withUnit`, `parseUnit`, `spacingShorthand`) | Jest (via `@wordpress/scripts`) | ✅ Yes (Node already available) |
| **PHP** | CSS generation core: `CSS_Builder`, `CSS_Helpers`, `Container_CSS` (selectors, responsive, dark mode) | PHPUnit | ⚙️ Requires PHP + Composer |

### 3.1. JS tests - run immediately

```bash
npm install            # if not already installed
npm test               # run all JS tests
npm run test:watch     # watch mode
```

Test files live alongside source: `src/**/*.test.ts` (e.g. `src/utils/index.test.ts`). They are excluded from `tsc` (run by Jest instead), so `@types/jest` is not required.

### 3.2. PHP tests

PHP tests **do not require** a WordPress install or MySQL. `tests/test-init.php` stubs the necessary WordPress functions so tests run in isolation, very fast.

The machine has **PHP 8.2 + Composer installed globally** (at `%USERPROFILE%\php`, added to PATH). Run directly:

```bash
composer install     # install PHPUnit + PHPStan into vendor/ (first time only)
composer test        # run all PHP tests (= phpunit)

vendor/bin/phpunit                                   # or call phpunit directly
vendor/bin/phpunit --filter test_box_shadow_rendered # run a single test
```

### 3.3. Static analysis (PHPStan)

Beyond tests, the plugin uses **strict typing** (`declare(strict_types=1)` in every PHP file) and **static analysis** via PHPStan (level 5, with WordPress stubs). PHPStan reads code without executing it, catching type errors, wrong parameter types, and missing array keys early.

```bash
composer analyse     # run PHPStan (configured in phpstan.neon.dist)
composer check       # run PHPStan + PHPUnit together
```

> **Note:** PHP is installed at `%USERPROFILE%\php` (separate from Local's bundled PHP). Extension config is at `%USERPROFILE%\php\php.ini`. If your terminal was open before the install, **open a new terminal** to pick up the updated PATH.

### 3.4. Adding tests for a new block

Tests are designed for **reuse**:
- `CssBuilderTest` and `CssHelpersTest` cover the shared core **all blocks use** - no need to rewrite them.
- For a new block: copy `tests/php/ContainerCssTest.php`, extend `CssTestCase`, use the built-in `genCss()` + `assertCssHas()` helpers, then swap in the new generator and attributes. Add one `require_once` for the new generator to `tests/test-init.php`.

---

## 4. Directory structure

```
flexa-block/
├── flexa-block.php              # Plugin entry point
├── includes/                    # PHP backend (namespace Flexa\Block)
│   ├── class-css-builder.php        # Fluent CSS builder + media queries + minify
│   ├── class-css-helpers.php        # Attributes → CSS (spacing, border, background, shadow, dark)
│   ├── class-css-generator-service.php
│   ├── class-dark-mode-settings.php # Dark mode settings + per-site defaults on install/update
│   ├── class-global-styles.php      # Design tokens (:root --flexa-* + dark overrides)
│   ├── class-block-patcher.php      # Rewrites one block's attributes in post content, nothing else
│   ├── class-inline-editor.php      # Front-end editing: registry, markers, toolbar, save route
│   ├── class-inline-revision-session.php # One restore point per editing session
│   ├── class-inline-edit-log.php    # Edit log (post meta, 30 days) + editor panel + admin list
│   ├── class-inline-review.php      # Review queue, approve/discard stamps, Heartbeat count
│   ├── class-inline-cache.php       # Empties page caches when shared parts change
│   ├── class-link-url.php           # The one rule for acceptable link addresses
│   ├── class-image-source.php       # Media library ID → what a block stores for an image
│   ├── class-icon-source.php        # Icon name / upload ID → what a block stores for an icon
│   ├── icons/catalog.php            # GENERATED icon catalog (npm run icons)
│   ├── admin/
│   │   └── class-admin.php           # Settings page + REST API + menu
│   ├── mcp/                          # Optional MCP module (AI agents); class-working-copy.php is shared with review
│   └── css-generators/
│       └── class-container-css.php  # CSS generator for the Container block
├── src/                         # Frontend/editor source (React + TypeScript)
│   ├── types.ts                     # Attribute types (shared across all blocks)
│   ├── global.d.ts                  # Ambient declarations: @wordpress/* (loose), *.scss, window.flexaBlock*
│   ├── admin/                        # Settings app: index.tsx, log-panel.tsx (Edit log), review-panel.tsx (Review)
│   ├── editor/                       # Block editor panels: working-copy.tsx, inline-edit-log.tsx
│   ├── frontend/                     # Front-end scripts, incl. inline-editor.ts / .scss
│   ├── shared/                       # Shared code, incl. text-diff.tsx and builtin-icons.json
│   ├── blocks/container/             # Container block (block.json, edit.tsx, render.php, view.ts, …)
│   ├── components/                   # Shared panels & controls (.tsx)
│   ├── hooks/  ·  utils/             # Hooks + utils (includes utils/index.test.ts)
├── scripts/build-icon-catalog.cjs   # Generates includes/icons/catalog.php
├── docs/frontend-editing-roadmap.md # Front-end editing: design notes, what was built and tested
├── build/                       # Compiled assets (generated by npm)
├── tsconfig.json                # TypeScript config (strict, tsc --noEmit)
├── tests/                       # PHP tests
│   ├── test-init.php                 # PHPUnit bootstrap (defines ABSPATH + stubs WP functions)
│   ├── phpstan-bootstrap.php         # Declares plugin constants for PHPStan
│   ├── CssTestCase.php               # Shared base class for block CSS tests
│   └── php/                          # CssBuilderTest, CssHelpersTest, ContainerCssTest
├── composer.json                # Dev deps: phpunit, phpstan
├── phpunit.xml.dist             # PHPUnit config
├── phpstan.neon.dist            # PHPStan config (level 5 + WP stubs)
├── package.json                 # JS build/lint/test scripts
├── README.md                    # This file
└── readme.txt                   # WordPress.org readme
```

---

## 5. How it works

- **Save-time CSS:** when a post or template is saved, the plugin parses the content, generates CSS once, and stores it in the `_flexa_block_css` post meta. The frontend prints this inline CSS only on pages that actually use the block - no CSS is generated on each page load.
- **Responsive cascade:** attributes follow a `desktop / tablet / mobile` structure; tablet inherits from desktop, mobile inherits from tablet (cascade down). Breakpoints: tablet `max-width: 1024px`, mobile `max-width: 767px`.
- **Dark mode:** each color is a `{ light, dark }` pair. Dark CSS is emitted via `@media (prefers-color-scheme: dark)` and/or `[data-theme="dark"]`, depending on the `Dark_Mode_Settings` configuration.
- **Design tokens (global styles):** `Global_Styles` prints a shared set of CSS variables on `:root` (`--flexa-color-*`, `--flexa-space-*`, `--flexa-font-size-*`, `--flexa-radius-*`) plus a dark override block. Blocks reference `var(--flexa-...)` for consistency. Customizable via the `flexa_block_design_tokens` (light) and `flexa_block_design_tokens_dark` (dark) filters.
- **Lazy background images:** enable the "Lazy load image" toggle in the Background panel; `view.js` uses `IntersectionObserver` to load the image only as it approaches the viewport (the URL is gated behind the `.flexa-bg-loaded` class).

### Front-end editing

People allowed to edit a page can change it where they read it: click a text, a
link, an image or an icon and a toolbar or a dialog opens. Everything is loaded
only for logged-in users who may edit the page; visitors get the plain page.

- **What can be edited** is declared per block in `Inline_Editor::EDITABLE`
  (`fields` for text, with an optional `link`; `images`; `icons`) and can be
  extended with the `flexa_block_inline_editable` filter. Where a link or an
  icon could be but is not, the toolbar offers "Add link" / "Add icon".
- **One block per save.** A save rewrites only the attributes of the block it is
  about (`Block_Patcher`), so `{}` and `[]` elsewhere on the page are never
  re-encoded. Text in a template part or synced pattern is saved to that part.
- **Nothing is taken on trust.** Text goes through the field's allowlist (and
  pasting is cut down to the same allowlist in the browser); links through
  `Link_Url` (http, https, mailto, tel and paths only); images are accepted as a
  media library ID only (`Image_Source`); icons as a name from the generated
  catalog or a media ID (`Icon_Source`).
- **No lost or overwritten work.** A save carries the content version it was
  made against and is refused if the page changed since or is open in the block
  editor; leaving with unsaved text asks first; each save offers Undo for
  10 seconds.
- **History.** Saves in one session share a single "before" revision
  (`Inline_Revision_Session`). Every change is logged with who, when, the
  block, and the text before and after (`Inline_Edit_Log`, kept 30 days, filter
  `flexa_block_inline_edit_log_retention_days`), shown in a "Front-end edits"
  panel in the block editor and on the **Edit log** tab.
- **Review (optional, off by default).** With "Send changes to published pages
  for review" on, changes to a published page go to a draft working copy;
  editors see the copy on the page, visitors keep the live page. Copies are
  applied or discarded on the **Review** tab (or in the block editor); by
  default only administrators apply them, optionally never their own. The log
  records who approved or discarded each change and when.
- **Page caches.** Saving a page lets the cache plugin clear that page. Saving a
  template part or synced pattern empties the whole page cache
  (`Inline_Cache`: Flexa Cache, WP Fastest Cache, WP Rocket, LiteSpeed,
  W3 Total Cache, WP Super Cache, SiteGround, Cache Enabler, Breeze, and the
  `flexa_block_inline_purge_all` action for others).
- **Keyboard, phones, screen readers.** Editable things are reached with Tab,
  named for screen readers and opened with Enter or Space; dialogs keep focus
  inside; on phones the toolbar sits at the top and wraps.

### AI agents (MCP)

An optional module, off by default, lets an AI client read the site's design,
draft pages from presets and edit the text of existing pages through the MCP
Adapter plugin (WordPress 7.0+). Agents never publish: changes to a published
page go to a working copy that a person applies. See `readme.txt` for details.

### Settings page

Go to **WordPress Admin → Flexa Block** to:
- Enable/disable **dark mode** and choose the activation method (`prefers-color-scheme` / `[data-theme="dark"]`). New sites start with dark mode off; sites updating keep whatever they had.
- Enable **CSS specificity boost** (prepends `body ` to selectors to reliably override theme styles).
- **Enable/disable individual blocks** (core blocks are always on).
- **Editing:** turn front-end editing on or off, choose which roles may use it and which blocks are editable, and set up review (who applies changes, and whether people may apply their own).
- **Review:** apply or discard changes waiting for review (shown while review is on or anything waits).
- **Edit log:** every front-end change for the last 30 days, searchable and filterable.
- **AI agents:** the MCP module.

Settings are stored in the `flexa_block_settings` option via REST (`/wp-json/flexa-block/v1/settings`, requires `manage_options` capability).

---

## 6. Troubleshooting

### 6.1. CSS missing or stale on the frontend

CSS is generated **at save time** and cached in the `_flexa_block_css` post meta (alongside `_flexa_block_css_version`). If the frontend shows no CSS or stale CSS:

1. **Re-open the post and click Update** - this is the most reliable way to regenerate CSS.
2. CSS is only printed on pages **that actually contain the block** - verify the block is still in the content.
3. After a **plugin version update**, old CSS is automatically treated as expired (`needs_regeneration()` compares versions) and regenerated on the next save.
4. To clear the cache manually: delete the `_flexa_block_css` and `_flexa_block_css_version` post meta entries for that post (or simply re-save the post).

### 6.2. One block errors but others are fine

Each block's CSS generation is **isolated**: if a generator throws an error, the plugin skips that block and continues generating CSS for the rest - **without breaking the page or blocking the save**.

To see which block failed, enable `WP_DEBUG` in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Errors are written to `wp-content/debug.log` in the format:

```
[Flexa Block] CSS generation failed for block "flexa/...": <error message> in <file>:<line>
```

### 6.3. Dark mode not showing

See the conditions in [How it works](#5-how-it-works): the block must have a **Dark color value** set, the post must have been **saved**, and the dark state must be active (`@media (prefers-color-scheme: dark)` when the OS is in dark mode, or `[data-theme="dark"]` present on the page).

### 6.4. No edit outlines on the front end

Front-end editing shows only to logged-in users who may edit that page. Check, in **Flexa Block → Editing**: the master switch is on, the user's role is allowed (administrators always are), and the block type is enabled. Blocks in a template part that still comes from the theme's files (never customised in the Site Editor) cannot be saved from the front end and are not outlined.

### 6.5. Visitors still see the old text

That is a page cache. A saved page is cleared by the cache plugin itself; a template part or synced pattern makes Flexa Block empty the whole cache of the plugins listed in [Front-end editing](#front-end-editing). With another cache plugin, hook `flexa_block_inline_purge_all` or clear it by hand.

### 6.6. A change was saved but the page did not change

If **Send changes to published pages for review** is on, changes to a published page wait in a copy. Apply them on **Flexa Block → Review**. The person who made them sees them on the page (with an amber "Waiting for review" outline); visitors do not until they are applied.
