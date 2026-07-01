# Flexa Block

A responsive Gutenberg block collection with **dark mode** support and **save-time CSS generation**. Ships the **Container** block as the canonical architecture reference for all future blocks.

- **Version:** 1.0.0
- **Requires:** WordPress 6.4+, PHP 7.4+
- **License:** GPLv3

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
│   ├── class-dark-mode-settings.php
│   ├── class-global-styles.php      # Design tokens (:root --flexa-* + dark overrides)
│   ├── admin/
│   │   └── class-admin.php           # Settings page + REST API + menu
│   └── css-generators/
│       └── class-container-css.php  # CSS generator for the Container block
├── src/                         # Frontend/editor source (React + TypeScript)
│   ├── types.ts                     # Attribute types (shared across all blocks)
│   ├── global.d.ts                  # Ambient declarations: @wordpress/* (loose), *.scss, window.flexaBlock*
│   ├── admin/                        # React app for the settings page (index.tsx)
│   ├── blocks/container/             # Container block (block.json, edit.tsx, render.php, view.ts, …)
│   ├── components/                   # Shared panels & controls (.tsx)
│   ├── hooks/  ·  utils/             # Hooks + utils (includes utils/index.test.ts)
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

### Settings page

Go to **WordPress Admin → Flexa Block** to:
- Enable/disable **dark mode** and choose the activation method (`prefers-color-scheme` / `[data-theme="dark"]`).
- Enable **CSS specificity boost** (prepends `body ` to selectors to reliably override theme styles).
- **Enable/disable individual blocks** (core blocks are always on).

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
