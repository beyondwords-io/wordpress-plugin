##  Extension plugins

The [plugins/](../plugins/) directory holds small standalone plugins that ship
as their own ZIP files, attached to each GitHub release. They are not part of
the main plugin ZIP and are never published to the WordPress Plugin Directory —
support hands the ZIP to a site owner who uploads it via **Plugins > Add New
Plugin > Upload Plugin**.

| Directory | What it does |
| --- | --- |
| `beyondwords-import-tool` | Bulk-assigns BeyondWords audio to existing posts from a JSON file |
| `beyondwords-debug-tool` | Logs BeyondWords REST API requests and responses to a file |
| `beyondwords-remove-auto-player` | Hides the auto-prepended player, keeping manually inserted ones |

##  Conventions

These follow the main plugin's conventions
([AGENTS.md](../AGENTS.md)) with two deliberate differences:

- **Namespace.** They sit under `Beyondwords\Wordpress\*`, not `BeyondWords\*`.
  Renaming them now would orphan the option and meta keys already written on
  live sites.
- **No autoloader.** Each `plugin.php` `require_once`s its own `src/` files, so
  there is no Composer classmap to regenerate.

`init()` is called from `plugin.php` once every file is required — never from
the foot of a class file. A self-call makes hook registration depend on the
`require` order above it, which is how the debug tool shipped fatal on
activation for six months: `Logger::init()` read `Settings` before
`class-settings.php` had been loaded.

PHPCS runs against [plugins/.phpcs.xml](../plugins/.phpcs.xml) via
`composer lint:plugins`, which GrumPHP's `code_quality` suite runs in CI.

##  The shared Tools page

The import and debug tools both render into a single **Tools > BeyondWords**
page. Neither owns it: each declares `MENU_SLUG = 'beyondwords-tools'`, checks
`get_plugin_page_hookname()` to see whether the other has already registered
the page this request, and registers it only if not. Whichever registers it
fires `beyondwords_tools_page_content`, and both hook their own section onto
that action at a fixed priority (import 20, debug 30).

The action is defined entirely by these plugins — the main plugin neither
fires nor listens to it. Two consequences:

- Either plugin works activated on its own.
- The hook is a contract between two separately-versioned ZIPs. Changing the
  slug, the action name or the priorities means changing both.

##  Coupling to the main plugin

The extension plugins are distributed separately but do not run in isolation,
and each coupling is deliberate:

- **The main plugin's uninstaller deletes the debug tool's options.**
  `beyondwords_debug_rest_api` and `beyondwords_debug_log_token` are listed in
  `BeyondWords\Core\Utils::get_options()`, which
  [src/core/class-uninstaller.php](../src/core/class-uninstaller.php) sweeps.
  Uninstalling the main plugin therefore resets the debug tool's settings and
  its log-file token. This is intended — the token names a file under
  `wp-content/uploads` that may hold request data, and leaving the option
  behind after an uninstall would strand it.
- **The import tool reads the main plugin's API.** Every such read is in
  `Beyondwords\Wordpress\Import\Compat`, and each falls back to a standalone
  equivalent so the tool still works when the main plugin is inactive.
- **Their strings ship in the main plugin's POT.** `composer wp:i18n:make-pot`
  runs `wp i18n make-pot ./`, which sweeps the whole repository. Adding,
  changing or removing a translatable string in `plugins/` changes
  `languages/speechkit.pot` and must be regenerated in the same PR.

##  Post meta written by the import tool

`PostMeta::update_for_record()` writes three keys per record:

| Key | Value |
| --- | --- |
| `beyondwords_generate_audio` | `'1'` |
| `beyondwords_project_id` | The record's `project_id` |
| `beyondwords_content_id` | The record's `content_id` |

### Why `beyondwords_integration_method` is not among them

It is tempting to write it, because a Content ID is only read under the REST
API integration: `Player\Renderer\Base::check()` treats a client-side ("Magic
Embed") post as renderable regardless of Content ID and resolves the player by
source ID instead, so on a Magic Embed site the imported IDs go unread.

Writing it would be a trap. `Settings\Fields::get_integration_method( $post )`
reads post meta *before* the site option, and
`Sync::generate_audio_result()` re-writes whichever value it just read on every
save. A value written here is therefore permanent: the post would keep
resolving as REST API no matter what the site owner later chose in Settings,
with no way back from the admin.

The main plugin writes that key as a receipt of a generation it performed on
that code path. The import tool performs no generation — it links content
created in the BeyondWords dashboard — so it has no such fact to record. Left
unwritten, `get_integration_method()` falls through to the site option, which
is reversible and stays the site owner's decision.

An import onto a Magic Embed site is therefore a no-op for playback, by
design. The IDs are still written, still visible in the Inspect panel, and
still take effect if the site later switches to the REST API integration.

Imports are restricted to `Settings\Utils::get_compatible_post_types()`, which
is what `Post\Sync::register_meta()` registers the meta keys against. Writing
to any other post type produces meta with no sanitize callback that the block
editor cannot see.

Direct `update_post_meta()` calls do not trigger audio generation: the main
plugin hangs generation off `wp_after_insert_post`, and
`Sync::should_generate_audio_for_post()` only honours the Preselect setting on
an editor/REST save. See
[preselect-generate-audio.md](./preselect-generate-audio.md).

##  Why the debug tool is disabled on WordPress VIP

Logging appends to a file under `wp-content/uploads` with
`FILE_APPEND | LOCK_EX`. VIP serves uploads through a stream wrapper that does
not honour `LOCK_EX`, so concurrent appends are not safe there.

`Environment::supports_file_logging()` returns false on VIP, which makes
`Settings::is_debug_enabled()` return false, so the `pre_http_request` and
`http_response` filters are never hooked. The Tools page says so rather than
offering a toggle that does nothing. Host detection matches
`Post\Sync::is_async_generation_enabled()` in the main plugin, duplicated
because this plugin can be activated without it.

Use the VIP request logs or Query Monitor to inspect API traffic there
instead. See [wordpress-vip.md](./wordpress-vip.md).

##  Log file protection

The log lives at
`wp-content/uploads/beyondwords/rest-api-<token>.log`, where `<token>` is 32
random characters stored in `beyondwords_debug_log_token`. The unguessable
filename is the actual protection — the `.htaccess` written alongside it is
defence in depth for Apache and does nothing on Nginx, Caddy or LiteSpeed. An
`index.php` blocks directory listing. Both guard files are written on every
writability check, not only when this plugin creates the directory, since
`uploads/beyondwords/` may already exist.

The log is deleted, along with its rotated copies and the token, when the
plugin is deactivated.

##  Testing

The e2e job installs all three from the built ZIPs — the same artefacts
attached to a release — and activates each in turn as a smoke test, so a fatal
on load fails CI. Behaviour coverage for `beyondwords_player_html` and the
remove-auto-player plugin is in
[tests/cypress/e2e/filters.cy.js](../tests/cypress/e2e/filters.cy.js).

The import and debug tools have no behaviour coverage beyond the activation
smoke test.

##  The removed export tool

`speechkit-export-tool` shipped until 7.0.1. It exported a capped 100-row CSV
of pre-4.x `speechkit_*` post meta, every key of which is now in the
`deprecated` list in `Core\Utils::get_post_meta_keys()` — so on a 7.x site the
CSV was a post ID, type and status followed by twelve empty columns. It was
never the counterpart to the import tool, which consumes JSON records from the
BeyondWords API and shares no field with it.

Site Health covers the site-wide picture and the editor Inspect panel covers
per-post meta.
