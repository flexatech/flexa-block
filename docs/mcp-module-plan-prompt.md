You are a senior WordPress plugin architect experienced in Gutenberg, the
WordPress Abilities API, MCP, and WordPress.org plugin releases.

Produce an implementation plan for adding an MCP module to an existing, shipped
WordPress.org plugin:

- Host plugin: Flexa Block – Blocks & Page Builder (free), v1.0.16, text domain
  `flexa-block`, namespace `Flexa\Block`
- Path: /Users/dangchi/Local-Sites/woocommerce/app/public/wp-content/plugins/flexa-block
- The module ships in the free plugin. It is not a separate plugin and not a Pro
  add-on. That decision is made; do not re-argue it.

PLAN ONLY. Do not implement code yet.

Before planning, load these repo skills, which encode this project's conventions:
`flexa-plugin-conventions`, `flexa-plugin-ui`, `flexa-plugin-activity-log`,
`file-size-budgets`, `wp-plugin-review`, `wp-readme-txt`. Follow them over your
own defaults.

## 0. Verified context (established; do not re-research)

Platform:
- WordPress 7.1.3 locally. The Abilities API is in core: `wp-includes/abilities-api/`,
  `wp_register_ability()`, hook `wp_abilities_api_init`, REST controllers
  `abilities-v1-list` and `abilities-v1-run`. It arrived in WP 7.0.
- Core registers only `core/get-site-info`, `core/get-user-info`,
  `core/get-environment-info` (`wp-includes/abilities.php`).
- `core/content-query` is NOT in core. The WordPress "AI" plugin registers it
  behind an opt-in experiment (`ai/includes/Abilities/Content/Content.php:208`)
  and deprecated its own `ai/get-post-details` in favour of it.
- MCP Adapter is a WordPress.org plugin: slug `mcp-adapter`, v0.7.0, 40,000+
  installs, requires WP 6.9 / PHP 7.4, also published as the Composer package
  `wordpress/mcp-adapter`. It owns the MCP server, HTTP/STDIO transport, protocol
  negotiation and authentication, registers `mcp-adapter/discover-abilities`,
  `get-ability-info` and `execute-ability`, and exposes an ability only when that
  ability declares `meta.mcp.public = true`. Reference shape:
  `ai/includes/Abilities/Utilities/Posts.php:129`.
  Consequence: build no MCP server, no transport, no authentication.

Host plugin conventions (`flexa-block.php`):
- No autoloader. Explicit `require_once FLEXA_BLOCK_DIR . '...'` then
  `Class::init()`, all inside `flexa_block_init()` on `plugins_loaded`.
- Constants: `FLEXA_BLOCK_VER`, `FLEXA_BLOCK_DIR`, `FLEXA_BLOCK_URL`,
  `FLEXA_BLOCK_BASENAME`, `FLEXA_BLOCK_MIN_WP` ('6.4'), with an existing
  version-guard + admin-notice pattern at the top of `flexa_block_init()`.
- Plugin headers declare `Requires at least: 6.4`, `Requires PHP: 7.4`.
- Settings live in a single option, `flexa_block_settings`
  (`includes/admin/class-admin.php:27`), read via `get_option()` at line 107.
- One top-level admin menu at `admin.php?page=flexa-block`, added at
  `includes/admin/class-admin.php:384`, rendering a single React SPA mount point
  (`render_page()`, line 403). The left nav and view switching live in
  `src/admin/index.tsx`; panels follow `src/admin/samples-panel.tsx`.
- Boot data is localized as `flexaBlockAdmin` in `enqueue_assets()`
  (~line 447): `restUrl`, `nonce`, `version`, `settings`, `blocks`,
  `editableBlocks`, `roles`, `formFlow`.
- Settings auto-save, debounced, to REST `flexa-block/v1/settings`
  (`register_routes()`, line 494). Permission is `manage_options`
  (`rest_permission()`, line 527).
- `save_settings()` (line 131) rewrites the whole option array, so any key not
  explicitly carried over is lost on a partial save, and it calls
  `CSS_Generator_Service::flush_all_cache()` on every save (line 153).

Flexa Block internals the module will reuse:
- 73 blocks, namespace `flexa/*`, registered from metadata in
  `includes/class-block-manager.php:746`.
- Every block is dynamic: each `build/blocks/*/block.json` has
  `"render": "file:./render.php"`, each editor entry returns `save: () => null`
  (e.g. `src/blocks/heading/index.tsx:21`), and no attribute uses `source`. All
  block state lives in the block-comment JSON, so `parse_blocks()` /
  `serialize_blocks()` round-trips Flexa blocks cleanly. The static-block
  serialization hazard does not apply.
- Preset and draft pipeline, already working:
  `includes/import/class-import-registry.php` (sources via the
  `flexa_block_import_sources` filter), `includes/import/class-sample-source.php`,
  `includes/import/class-content-importer.php::import()` (inserts the post,
  `regenerate_block_ids()` ~line 169, resolves media, writes
  `_flexa_import_source` / `_flexa_import_id` / `_flexa_import_version`, and
  `find_existing()`), `includes/import/class-import-manager.php`,
  `includes/import/class-import-rest.php`.
- Two real presets: `samples/contact-page.php`, `samples/landing-saas.php`,
  serialized from PHP arrays through a local `$mk()` helper.
- Per-instance CSS: `includes/class-css-generator-service.php` stores combined
  CSS in `_flexa_block_css` with `_flexa_block_css_version`;
  `includes/class-asset-loader.php` regenerates lazily via `needs_regeneration()`;
  per-block generators in `includes/css-generators/`.
- Design context the plugin owns: `includes/class-global-styles.php`,
  `includes/class-dark-mode-settings.php`. Every colour attribute is a
  `{light, dark}` pair.

Hazard to design around, not inherit:
`Content_Importer::import()` calls `kses_remove_filters()` around
`wp_insert_post()` and accepts `post_status => publish`. Fine for a
capability-gated admin button over plugin-bundled markup. Not acceptable on an
MCP-reachable path. Plan an explicit wrapper that pins `draft` and never routes
caller-supplied text through the KSES bypass.

## 1. Goal

Let an authenticated external AI agent create and read content on the site
through MCP, using verified Flexa Block presets.

MVP workflow:
- Report design context: palette, dark-mode state, available Flexa blocks.
- List the curated presets this site can generate.
- Report one preset's fillable content slots.
- Read a permitted page's block structure.
- Create a draft page from one allowlisted preset plus caller-supplied slot
  values.
- The user opens the draft in Gutenberg, edits normally, publishes manually.

Design against a preset that exists:
"Create a draft contact page for Mai Interior, with this address, this phone
number, and the form sending to hello@example.com."

Do not claim the module can redesign arbitrary WordPress sites.

## 2. Remaining inspection work

Section 0 covers most of it. Inspect only what is still open, citing real paths
and symbols:
- The exact definition shape returned by `Sample_Source::definition()` (keys,
  `media` entries, `version`), since create-draft must build one.
- Which attributes in the two sample files hold human-visible text, and their
  block paths. This is the raw material for the slot map in section 5.
- Whether a post inserted outside the admin triggers CSS generation, or needs an
  explicit `CSS_Generator_Service` call.
- Whether `Block_Manager` exposes a stable accessor for the block catalog that
  the design-context ability can read.
- The capability and nonce gating on `class-import-rest.php`, as a baseline to
  mirror or tighten.
- How the left nav and view switch are declared in `src/admin/index.tsx`, and
  the smallest addition that adds one more view.

Do not invent blocks, attributes, functions or hooks.

## 3. Module architecture, loading and gating

Shape it as an extractable module so it can later become a shared Flexa MCP
plugin serving other Flexa products without a rewrite:
- Runtime under `includes/mcp/`, with its own manager class and `::init()`,
  following the host's require-then-init convention.
- Abilities contributed through a module-owned filter (propose a name) so other
  Flexa plugins can add their own later.
- No dependency from the module runtime into admin-only code paths.

Loading and gating rules, already decided. Plan to these, do not re-litigate:

1. The MCP runtime loads only when an administrator has enabled it. When the
   switch is off, nothing under `includes/mcp/` is required and no ability is
   registered. "Inert" means absent from the abilities registry, not registered
   and then denied.
2. Enabling is impossible below WordPress 7.0. The toggle renders disabled with
   a reason, and the save path rejects the value, so the option cannot be turned
   on by a form post, by `update_option()` from elsewhere, or by an imported
   settings payload. Say which layer enforces this (sanitize callback,
   `pre_update_option_*`, or both) and recommend one.
3. The stored flag is not trusted on its own. The runtime re-checks the
   WordPress version at load, because a site can enable this on WP 7.0 and then
   restore an older backup. In that state the module stays off and the admin
   panel explains why, without a site-wide admin notice.
4. `FLEXA_BLOCK_MIN_WP` stays at '6.4'. The host plugin's own requirements do not
   change.
5. Never add `Requires Plugins: mcp-adapter` to the Flexa Block header. A page
   builder must not refuse to run because an MCP plugin is absent.
6. The settings panel is NOT part of the gated module. It loads with the existing
   admin code whenever the user is in wp-admin, because otherwise there would be
   no way to switch the module on. Keep the panel free of any dependency on
   module classes: it reads its option and the environment, nothing more.
   Decided, plan to it: the panel's PHP lives in a new
   `includes/admin/class-mcp-settings.php`, admin-only and always loaded, which
   owns a separate option (propose `flexa_block_mcp`), its own REST route
   (propose `flexa-block/v1/mcp`, GET and POST, `manage_options`), the sanitize
   and version guard, and one `mcp` key contributed to the `flexaBlockAdmin` boot
   payload: `{ supported, minWp, enabled, read, write, adapter: { active,
   version }, endpoint, restUrl }`. Do not extend `flexa_block_settings` and do
   not reuse the `/settings` route, for three reasons: `save_settings()` flushes
   every post's CSS cache on save, the app auto-saves that route with a debounce
   which is wrong for a switch that opens an agent-reachable write path, and that
   handler rewrites the whole option array. A separate option also puts the
   "cannot enable below WP 7.0" guard in exactly one place. Write the permission
   callback in the new class rather than calling `Admin::rest_permission()`, so
   the file can move out later untouched.
7. When the module does load, abilities register whether or not MCP Adapter is
   active. They remain reachable through core's `abilities-v1-run` REST
   controller, and MCP Adapter exposes them over MCP when it is active. Detect
   the adapter softly and report its presence in the panel.

Respect `file-size-budgets` when deciding how the module splits into files.

## 4. Ability catalog

Five abilities. Before designing each, check whether core or MCP Adapter already
provides it, and do not duplicate:
- No site-info ability duplicating `core/get-site-info`.
- No generic page-listing ability. Core REST already covers it, and a redundant
  one is a review liability. If you still believe one is needed, justify it
  against what core returns.
- No per-block schema abilities. Drafts come only from allowlisted presets, so an
  agent cannot author arbitrary blocks and does not need block schemas in v1.

Proposed names, not existing APIs:

| Ability | Mode | Purpose |
| --- | --- | --- |
| `flexa/get-design-context` | read | Palette, dark-mode state, available Flexa blocks, preset count. Design context only. |
| `flexa/list-presets` | read | Allowlisted presets: id, title, intent, required slots. |
| `flexa/get-preset-schema` | read | One preset's slots: key, type, required, max length, example. |
| `flexa/get-page-block-tree` | read | Block structure of one permitted page. |
| `flexa/create-page-draft` | write | Create one draft page from one preset plus slot values. |

For each: purpose, input and output JSON Schema, permission callback, pagination
and payload limits, error responses, read/write classification, availability
condition, `meta.mcp` value, and which existing Flexa Block class it delegates to.

Constraints:
- `flexa/get-design-context` returns design and content context only. No
  environment diagnostics, no option dumps, no secrets. Note that
  `flexa_block_settings` holds unrelated plugin settings; do not expose it.
- `flexa/get-page-block-tree` checks `current_user_can( 'read_post', $id )`,
  returns nothing for content the caller cannot read, and its output description
  distinguishes serialized block data from resolved attributes.
- `flexa/create-page-draft`: pages only; `post_status` pinned to `draft`;
  allowlisted preset id; typed slot values; existing permitted media IDs only; no
  arbitrary status; no raw HTML or executable content; full validation before any
  write; never touches an existing post.
- Idempotency: `find_existing()` keys on preset provenance meta, so it answers
  "has this preset ever been imported", not "have I already handled this
  request". Design a per-request key scoped to user plus operation (a transient is
  fine) and state its lifetime. Say how it interacts with the existing
  "open existing vs import a fresh copy" behaviour.

## 5. Presets and content slots (the hard part)

The samples are fixed markup whose only placeholder is `blockId`. There is no
token or slot system. Building one is the largest unknown here, so plan it in
detail.

Choose ONE mechanism and justify it:
(a) The preset declares a `slots` map from slot key to block paths plus attribute
    names. Substitution walks the `parse_blocks()` array, writes typed values
    into those attributes, and re-serializes with `serialize_blocks()`. Safe
    precisely because every Flexa block is dynamic with no HTML-sourced
    attributes.
(b) Token replacement inside the serialized JSON.

Then specify:
- The slot type set (plain text, multiline text, URL, email, phone, media ID),
  with validation and sanitization per type.
- Max length per slot and the total request payload ceiling.
- Behaviour for an omitted slot: preset default, or reject.
- Where slot definitions live, and how a preset declares them with the smallest
  possible change to the existing sample files and to `Import_Source`.
- Composition order with `regenerate_block_ids()` and media resolution.
- Versioning, so a later Flexa Block release cannot silently break the slot
  contract of an already-created draft.
- Whether the admin import UI should also use slots, or keep using presets as-is.
  Recommend one; do not expand admin scope without saying so.

Select which of `contact-page` and `landing-saas` are suitable for v1. If only
one is, ship one and say why. Do not pad the catalog.

## 6. Serialization correctness

A verification task, not a design problem, because all blocks are dynamic.
Specify the checks that must pass:
- A page created through MCP opens in Gutenberg with no invalid-block warnings.
- Saving it unmodified in Gutenberg does not change the content.
- The front end renders correctly, with per-instance CSS present on first view.
- Nested blocks, the `anchor` and `className` supports, and the `{light, dark}`
  colour shape survive the round trip.
- CSS is generated or invalidated for a post created outside the admin.

No Node.js requirement on customer hosting. If a preset cannot be generated
reliably server-side, drop it from v1.

## 7. Authentication and authorization

MCP Adapter owns transport and authentication; document its workflow rather than
designing one. The master off switch and its WP 7.0 gate are specified in
section 3. Specify what else the module adds:

- Read access and draft-write access separately switchable, both off by default
  even once MCP itself is enabled. State what a fresh enable allows.
- Server-side policy on top of WordPress capabilities: permitted users or roles,
  permitted content scope.
- Every ability validates the caller's capability and every referenced object, on
  every call. The enable switch is not a substitute for a permission callback.
- Application Passwords are authentication, not per-tool scopes. State that in
  the docs; never treat one as a grant of scope.
- Rate and request-size limits, with concrete numbers.
- No credentials in URLs, logs, exported configuration or screenshots. Never
  store a recoverable copy of an Application Password. Document creation, use and
  revocation instead.
- No anonymous MCP execution.
- MCP tool annotations are descriptive metadata, never enforcement.
- Content read back from the site is untrusted data, never agent instructions.
  Say where that boundary is enforced.

Produce a compatibility matrix covering only clients and transports whose
requirements you can state, including the MCP protocol revisions MCP Adapter
0.7.x supports. Do not advertise universal client compatibility.

## 8. Admin experience

Add one view to the existing Flexa Block admin app, not a new top-level page and
not Settings → something. Follow `flexa-plugin-ui`.

Decided, plan to it:
- A new panel component `src/admin/mcp-panel.tsx`, alongside
  `src/admin/samples-panel.tsx`, plus one nav entry and one view branch in
  `src/admin/index.tsx`. Split the panel further if `file-size-budgets` requires
  it; a setup guide is the natural second file.
- The nav entry is always visible, including on WordPress below 7.0, where the
  panel renders state 1 below. Hiding it would make the feature undiscoverable,
  so the unsupported state carries the explanation instead.
- The panel saves explicitly. It does not join the app's debounced auto-save, and
  enabling MCP asks for confirmation before the write.

The panel must work in all four states and read clearly in each:
1. WordPress below 7.0: toggle disabled, one sentence saying why, nothing else
   actionable.
2. WP 7.0+, MCP off: the switch, and a short description of what enabling does.
3. WP 7.0+, MCP on, MCP Adapter missing: enabled but not reachable over MCP;
   explain the adapter and that abilities are still available over the core
   abilities REST route.
4. WP 7.0+, MCP on, adapter active: endpoint, copyable client configuration with
   credential placeholders, setup guide for the one supported client, read and
   draft-write toggles, permitted users and content scope, recent activity.

Settings live in their own option and move over their own REST route, per
section 3. State what that means for `uninstall.php`, and confirm that a save on
either route cannot clobber the other option or trigger the other's side effects.

For activity, follow `flexa-plugin-activity-log`, and first check what MCP
Adapter's own observability already records so you do not duplicate it. If a
module-side log is still warranted: timestamp, user, ability, target post ID,
outcome, request ID. No content, prompts, passwords or tokens. Bounded retention
with an explicit cleanup policy. Never present it as a rollback mechanism. Say
whether the log writer lives in the gated module or alongside the panel, given
that nothing can be logged while the module is off.

## 9. Release readiness for an existing WordPress.org plugin

This is an update to a shipped plugin, not a new submission. Plan accordingly:
- readme.txt changes: the new feature, that it is off by default and requires WP
  7.0 plus MCP Adapter, its limitations, a privacy and data-flow section, and
  explicit disclosure that the user's chosen AI client receives site content
  through authorized tool calls. Follow `wp-readme-txt`.
- Changelog and version bump, including `FLEXA_BLOCK_VER`.
- Confirm that `Requires at least`, `Requires PHP` and `Tested up to` do not need
  to change, given the module is gated internally.
- No telemetry, no unsolicited outbound requests, no bundled remote assets.
- i18n with the `flexa-block` text domain, escaping, sanitization, capability
  checks, prefixed and namespaced symbols.
- Uninstall: state what `uninstall.php` must clean up for the new option, meta
  and transients, and what stays on deactivation.
- Run Plugin Check and WPCS (`phpcs.xml.dist`, `phpstan.neon.dist`) and keep the
  existing baseline clean.
- Translation catalogue regeneration via the existing `makepot.sh` script.
- Note the one real cost of shipping inside the free plugin: a security fix in
  the MCP surface now means a full Flexa Block release. Say how that affects the
  release plan.

Do not add license checks, expiry, or payment gates. This is free and fully
functional.

## 10. Validation

Plan tests for:
- Unauthenticated and under-privileged calls.
- Reading another user's restricted page.
- Invalid preset id; invalid, oversized and missing slot values; an unauthorized
  media ID.
- Attempts to force a non-draft status.
- Duplicate requests against the idempotency key.
- MCP disabled: no Flexa abilities in the registry, and MCP Adapter's
  discover-abilities returns none of them.
- Draft-write disabled while read is on.
- An attempt to enable MCP on WordPress 6.9, through the form and through a
  direct `update_option()` call. Both must fail.
- The stored flag on while the site runs below WP 7.0: module stays off, panel
  explains, no fatal, no notice spam.
- Toggling MCP off while a client is connected: subsequent calls stop resolving.
- The nav entry renders on WordPress 6.4 with the toggle disabled and a reason,
  and the panel makes no request that 404s on that version.
- A non-administrator POSTing to the MCP route is refused.
- Option isolation: saving general settings leaves the MCP option untouched and
  vice versa, and an MCP save does not flush the CSS cache.
- MCP Adapter absent or at an incompatible version.
- A real client performing discovery, a read and a draft creation.
- The Gutenberg round trip and front-end render from section 6.
- CSS generation and cache behaviour for a post created outside the admin.
- A regression pass confirming the update changes nothing for a site that never
  enables the module, including no extra files loaded on a front-end request.

State the supported matrix: WordPress (and the behaviour on 6.4 through 6.9),
PHP, MCP Adapter 0.7.x with its protocol revisions, and the Flexa Block version
this lands in.

Separate verified findings from assumptions and from tests still to run.

## 11. Required output

1. Inspection findings from section 2, with paths and symbols.
2. Final v1 scope and non-goals.
3. Module architecture, plus the enforcement decisions left open in section 3.
4. Ability catalog with schemas and delegation targets.
5. Slot mechanism and preset selection.
6. Authentication and authorization design, with the compatibility matrix.
7. Admin panel across the four states, and the setup flow.
8. New and modified files, with the diff to `flexa-block.php` described in words.
9. Implementation phases with concrete tasks and acceptance criteria.
10. Release checklist for the Flexa Block update.
11. Risks, open blockers, and decisions needing my input.
12. Definition of Done for the release candidate.

Explicit non-goals, to keep in both the plan and the shipped documentation:
editing or publishing existing content; deleting or bulk-modifying content; theme
templates, global styles, navigation, site options; plugin or theme installation;
PHP, shell, SQL or filesystem execution; WooCommerce operations; media downloads
from arbitrary external URLs; built-in AI chat or paid model APIs; any Flexa
cloud relay, account or subscription; OAuth, unless the one chosen client
requires it; and any change to how existing blocks render.

Recommend a single simplest viable approach. No competing architecture proposals.
Ask a question only when the answer would change the implementation; otherwise
state a reasonable assumption and keep going.
