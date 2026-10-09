# Connecting an AI client over MCP

Flexa Block can expose a small set of tools to an AI client through the Model
Context Protocol. This page covers one client, Claude Code, because that is the
one combination of client, transport and protocol revision that has been tested
against this plugin. Other clients speak the same protocol and may well work;
this plugin makes no claim about them, and the table at the bottom says exactly
what has been checked and what has not.

Nothing here is on by default. The module ships switched off, and while it is
off the plugin registers no tools at all.

## What the site needs

| Requirement | Why |
|---|---|
| WordPress 7.0 or newer | The Abilities API, which the tools are registered with, arrived in 7.0. On an older version the panel shows the switch and the reason it cannot be used. |
| The [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin, 0.7.x | It owns the MCP server, the transport and the authentication. Flexa Block registers abilities; it does not speak the protocol. |
| Flexa Block, module switched on | **Flexa Block → AI agents**, the "Enable MCP" switch. The read and write toggles beside it decide which tools exist. |
| An account with an application password | The transport authenticates as a WordPress user. Everything a client does, it does as that user. |

## Setting it up with Claude Code

1. **Install and activate MCP Adapter.** The Flexa panel tells you if it is
   missing.

2. **Switch the module on.** Go to **Flexa Block → AI agents**, turn on "Enable MCP", and
   confirm. Leave "Create drafts" off until you want an agent writing.

3. **Create the account the client will use.** A separate user with the lowest
   role that fits the job, not your own administrator account. Reading the
   site's design needs `edit_posts`, reading one page needs permission to read
   that page, and everything to do with presets needs `edit_pages`. The Editor
   role covers all of it.

4. **Give that account an application password.** In WordPress, **Users → the
   account → Application Passwords**. Copy the generated password; WordPress
   shows it once.

5. **Copy the endpoint** from that same screen. It looks like
   `https://example.com/wp-json/mcp/mcp-adapter-default-server`.

6. **Add the server to Claude Code**, with the credentials as an HTTP Basic
   header:

   ```sh
   claude mcp add --transport http flexa-wp \
     https://example.com/wp-json/mcp/mcp-adapter-default-server \
     --header "Authorization: Basic $(printf '%s' 'LOGIN:APP PASSWORD' | base64)"
   ```

   Keep the spaces WordPress puts in an application password; they are part of
   it.

7. **Check the connection.**

   ```sh
   claude mcp list
   ```

   The server should report `✔ Connected`.

## What the client sees

The adapter's default server publishes three tools, not one tool per ability:

- `mcp-adapter-discover-abilities` lists what the account may call.
- `mcp-adapter-get-ability-info` returns one ability's schema.
- `mcp-adapter-execute-ability` runs one, taking `ability_name` and
  `parameters`.

So a call to a Flexa tool is a call to `execute-ability` with
`ability_name: "flexa/list-presets"` and the ability's own input under
`parameters`. This plugin registers five abilities:

| Ability | Needs | What it does |
|---|---|---|
| `flexa/get-design-context` | read toggle, `edit_posts` | The site's design tokens and the blocks that are registered. |
| `flexa/get-page-block-tree` | read toggle, `read_post` on that page | The block structure of one post or page. |
| `flexa/list-presets` | write toggle, `edit_pages` | The page presets available to build from. |
| `flexa/get-preset-schema` | write toggle, `edit_pages` | One preset's slots and their types. |
| `flexa/create-page-draft` | write toggle, `edit_pages` | Creates a page from a preset. Draft only, never published, never an overwrite. |

`discover-abilities` also lists abilities that WordPress core and other active
plugins registered. Those are not ours and the two toggles in the Flexa panel
do not govern them: on a WooCommerce site an Editor account reaches ten
WooCommerce abilities, `product-create` and `order-update-status` among them.
The role you give the account is what bounds that, which is the reason step 3
asks for a dedicated user.

## Protocol revisions

MCP Adapter 0.7.x serves two revisions, and they are not variations on one
handshake. Each has its own rules, and a request that breaks them fails in a
way that reads like a transport fault rather than a bad argument.

| Revision | How a request is identified | What every request must carry |
|---|---|---|
| `2025-11-25` | A session. `initialize` returns an `Mcp-Session-Id`. | That session id, plus an `MCP-Protocol-Version: 2025-11-25` header. Without the version header the server answers `-32600`. |
| `2026-07-28` | No session. Each request stands alone. | `params._meta` with `io.modelcontextprotocol/protocolVersion` and a `clientCapabilities` object (`-32602` otherwise), and headers mirroring the body: `Mcp-Method`, plus `Mcp-Name` on `tools/call`, `resources/read` and `prompts/get` (`-32020` otherwise). `initialize` is not a step on this path and answers "Method not found". |

A client picks the revision; the plugin is not involved in the choice.

## Compatibility

Tested on 2026-10-09 against WordPress 7.1.3 and MCP Adapter 0.7.0, on a local
site, over HTTP with Basic authentication.

| Client | Transport | Revision | Status |
|---|---|---|---|
| Claude Code | HTTP | `2025-11-25` | Connects and lists the adapter's tools. |
| Direct HTTP client (curl) | HTTP | `2025-11-25` | Full path checked: `initialize`, `notifications/initialized`, `tools/list`, and `tools/call` returning a Flexa ability's result. |
| Direct HTTP client (curl) | HTTP | `2026-07-28` | Discovery and execution checked, including the header and `_meta` requirements above. |
| Anything else | | | Not tested. No requirement stated, and none implied. |

Streamable HTTP is the only transport in the table because it is the only one
the adapter's default server exposes. There is no stdio path to this plugin.

## If it does not connect

- `claude mcp list` says the server is not connected: check the endpoint in a
  browser while signed in. A 404 means the adapter is not active.
- Every call is refused for want of a capability: the account's role is too
  low. See the capabilities column in the table above.
- `discover-abilities` returns core and WooCommerce abilities but none of ours:
  the module's switch is off, or both the read and write toggles are.
- A tool exists but a call fails with a rate limit: ten drafts and 120
  discovery calls per user per hour, counted per tool. The message says how
  long to wait.
- Something ran but you cannot tell what: **Flexa Block → AI agents → Recent activity**
  records every call this module handled, with the user, the tool, the page and
  the outcome. It keeps no content and no prompts.
