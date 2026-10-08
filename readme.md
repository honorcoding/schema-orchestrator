# Schema Orchestrator

Version 2.0.0 · Needs WordPress with PHP 7.4 or newer · Works with **Yoast SEO** or **Rank Math SEO**

## What is this?

Yoast SEO and Rank Math both add hidden "structured data" (also called *schema* or *JSON-LD*) to your pages. Search engines read it to learn who you are, what a page is about, and so on.

Those plugins make good guesses, but sometimes you need to **change** a value, **add** something they don't know about, or **remove** something you don't want. Schema Orchestrator sits between your SEO plugin and the page and lets you do exactly that, without editing the SEO plugin itself.

You can do it three ways:

| Who | Where | Use it for |
| --- | --- | --- |
| An editor | The **Schema Orchestrator** box under the page editor | Changes for one page |
| An administrator | **Settings > Schema Orchestrator** | Changes for every page (e.g. your organization's details) |
| A developer | PHP code (registry and filters, see below) | Reusable or dynamic schema |

## Which SEO plugin does it use?

It checks automatically, and uses the **first one it finds** in this order:

1. **Yoast SEO**
2. **Rank Math SEO**

You don't choose anything. If neither is active, the plugin does nothing and shows a notice on the Plugins screen and on its Settings page. (Running two SEO plugins at once is not recommended anyway; if you do, Yoast wins.)

The Settings page and the page editor box both say which plugin is in use.

## A few words used in this document

- **Node**: one piece of schema, for example one Organization or one WebPage. It has an `@type` (what it is) and usually an `@id` (a unique address for it, which looks like a URL, e.g. `https://example.com/#organization`).
- **Graph**: the full list of nodes on a page.
- **Override**: a change you type in, written as JSON.

## Typing overrides (editors and administrators)

Both the page box and the Settings page take the same kind of text: **JSON**. The box shows "Valid JSON" or an error as you type. Leave it empty if you don't want any changes.

The top level is always an object `{ ... }`. Inside, each entry is one instruction. You can mix as many as you like.

### Change something by type

Changes **every** node of that type on the page.

```json
{
    "WebSite": { "name": "My Site" }
}
```

### Change (or create) something by its @id

If a node with that `@id` exists, it is changed. If not, a new node is created.

```json
{
    "https://example.com/#organization": {
        "name": "Example Organization",
        "sameAs": ["https://www.facebook.com/example"]
    }
}
```

### Add a new node

```json
{
    "__append": [
        {
            "@type": "Event",
            "@id": "https://example.com/#event",
            "name": "Annual Conference"
        }
    ]
}
```

Every node needs an `@type` or an `@id`, otherwise it is ignored.

### Remove a single property

Set it to `null`.

```json
{
    "WebPage": { "description": null }
}
```

### Remove whole nodes

Give a type (like `BreadcrumbList`) or an `@id`.

```json
{
    "__remove": ["BreadcrumbList", "https://example.com/#something"]
}
```

### Rules worth remembering

- **Objects are merged, lists are replaced.** If you change `sameAs` (a list), your list **replaces** the old list entirely. Objects (like `address`) are merged property by property.
- **An empty `{}` does nothing.** It never wipes out data that is already there.
- **Order of work:** `__append` first, then changes by type or `@id`, then `__remove`.
- **HTML is stripped.** Anything like `<b>` or `<script>` in your text is removed so it can't break the page.
- **Mistakes are safe.** If your JSON is invalid, it is **not saved** and the old version stays in place. The page editor shows the error, with your text kept in the box so you can fix it. A page never breaks because of a typo.
- **Page beats sitewide.** If the Settings page and a page box change the same thing, the page's own box wins.

## How it works (the order things happen)

When Yoast or Rank Math is about to print a page's schema, Schema Orchestrator receives it and runs these steps:

1. Developer filter `schema_orchestrator_pre_merge_graph`
2. Nodes from the **Registry** (developer-registered builders)
3. Developer filter `schema_orchestrator_additional_nodes`
4. **Sitewide** overrides (the Settings page)
5. **This page's** overrides (the page box)
6. Nodes that share the same `@id` are combined into one (no duplicates)
7. Developer filter `schema_orchestrator_final_graph`

The result goes back to the SEO plugin, which prints it.

```
SEO plugin's schema
        |
        v
  Adapter (Yoast or Rank Math)
        |
        v
  Pipeline: steps 1 to 7 above
        |
        v
  Adapter hands it back
        |
        v
SEO plugin prints the page's JSON-LD
```

## For developers

### Registry: reusable schema builders

Use this from a plugin or theme when you want to add schema on many pages (for example a Product from WooCommerce). Register on the `schema_orchestrator_register` action:

```php
add_action( 'schema_orchestrator_register', function () {

    \Schema_Orchestrator\Schema_Registry::register_node(
        'my-organization',
        function ( $post_id, $context ) {
            return [
                '@type' => 'Organization',
                '@id'   => home_url( '/#organization' ),
                'name'  => 'Example Organization',
            ];
        }
    );
} );
```

The callback gets the post ID (`0` on archives, search and so on) and a context object, and returns **one node or a list of nodes**. Returning anything else is ignored. If a callback throws an error, it is skipped and the page still works. `Schema_Registry::unregister_node( 'name' )` removes one.

### Filters

All filters receive `( $value, $post_id, $context )`.

| Filter | Gets | Must return | Use it to |
| --- | --- | --- | --- |
| `schema_orchestrator_pre_merge_graph` | the SEO plugin's graph | the graph (list of nodes) | change or prepare what the SEO plugin made |
| `schema_orchestrator_additional_nodes` | `[]` | one node or a list of nodes | add nodes |
| `schema_orchestrator_global_overrides` | `[]` | an overrides array (same format as the JSON above) | sitewide changes |
| `schema_orchestrator_final_graph` | the finished graph | the graph | last-minute cleanup |
| `schema_orchestrator_adapters` | the list of adapters (no extra args) | a list of adapters | support another SEO plugin |

If a graph filter returns something that is not an array, its result is ignored.

**Note:** developer filters are trusted, so unlike typed-in JSON their output is not stripped of HTML. Escape anything you take from users.

### The context object

Every callback and filter gets a `Schema_Orchestrator\Schema_Context`:

- `$context->id`: the post ID, or `0` if the page is not a single post
- `$context->source`: `'yoast'` or `'rank-math'`
- `$context->raw`: the SEO plugin's own context object, untouched

### Adding support for another SEO plugin

Write a class that implements `Schema_Orchestrator\Schema_Adapter` (4 small methods, see `includes/adapters/interface-schema-adapter.php`) and add it with the `schema_orchestrator_adapters` filter. The Yoast adapter is about 60 lines and is a good model.

### Looking at the result while debugging

`\Schema_Orchestrator\Schema_Orchestrator::instance()->get_last_graph()` returns the last graph produced during the current request, and `->get_adapter()` returns the adapter in use (or `null`).

To see log messages, turn on `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`. Messages go to the normal `wp-content/debug.log`, prefixed with `[Schema Orchestrator]`. With those off, nothing is written anywhere.

## Notes about each SEO plugin

**Yoast SEO**: connects to the `wpseo_schema_graph` filter. Yoast already gives a plain list of nodes. Page overrides only apply to single posts and pages. On category, tag and author pages Yoast's internal ID is a term or user ID, not a post ID, so those pages get only the sitewide settings.

**Rank Math SEO**: connects to the `rank_math/json_ld` filter. Rank Math hands over its schema as an array with named keys (`WebPage`, `Person` ...), not a plain list. The plugin converts it to a list, does its work, and converts it back with the same keys, so Rank Math and other code on that filter still see the shape they expect. New nodes you add get keys that start with `so_`. If Rank Math has no schema at all for a page, nothing is added to that page.

## What's in the plugin folder

```
schema-orchestrator.php        Starts the plugin
uninstall.php                  Cleans the database when the plugin is deleted
readme.md                      This file
includes/
    loader.php                 The list of files to load (add new files here)
    core/
        class-schema-orchestrator.php   Main class: finds the SEO plugin, connects to it
        class-schema-pipeline.php       The 7 steps, in order
        class-schema-overrides.php      Applies the JSON changes
        class-schema-graph.php          Small helpers (merge, find by type or @id ...)
        class-schema-registry.php       The developer registry
        class-schema-context.php        "Which page is this?" object
    adapters/
        interface-schema-adapter.php    What an adapter must do
        class-schema-adapter-yoast.php
        class-schema-adapter-rank-math.php
    settings/
        class-schema-settings.php       Saves and loads the sitewide JSON
    admin/
        class-schema-admin-metabox.php        The page editor box
        class-schema-admin-settings-page.php  Settings > Schema Orchestrator
        class-schema-admin-assets.php         Loads the CSS and JS where needed
        class-schema-admin-help.php           The "Examples" cheat sheet
    support/
        class-schema-json-input.php     Checks and cleans typed-in JSON
        class-schema-logger.php         Writes to debug.log when debugging is on
assets/
    css/admin.css
    js/admin.js                         The live "Valid JSON" message
```

Where data is stored: sitewide JSON in the option `schema_orchestrator_settings`; each page's overrides in the post meta `_schema_orchestrator_overrides`. Deleting the plugin removes both.

## Upgrading from 1.x

Your saved data keeps working: the sitewide JSON from 1.x is still read until the first time you press Save on the new Settings page, and page overrides use the same storage as before.

Things that changed:

- **The Organization JSON for ASDN is no longer built in.** In 1.x it was the default on every install. Now the sitewide box starts empty. A site upgrading from 1.x keeps its saved value.
- **The Settings page moved** to **Settings > Schema Orchestrator** (it was "Schema Settings").
- **`__append` now works.** In 1.x it was documented but did nothing. `__remove` is new.
- **Lists are replaced, not mixed.** Before, a shorter list would leave old items behind at the end.
- **Invalid JSON is rejected with a message** instead of being dropped silently.
- **Rank Math is supported.** The "providers" idea from 1.x (which was never switched on) is gone; adapters replace it.
- **The context object in filters is now a `Schema_Context`** (with `->id`, `->source`, `->raw`) instead of Yoast's own object. Yoast's is still available as `->raw`.
- **Logging** now uses WordPress's `debug.log`, so the `logs/` folder is no longer needed.

After copying in the new files, delete these old ones from the repository so they don't linger:

```
archive/
logs/
includes/config.php
includes/core/config.php
includes/debug/
includes/utilities/
includes/providers/
includes/qa/
includes/settings/class-settings-controller.php
includes/admin/admin.php
includes/admin/class-admin-settings-page.php
includes/admin/class-admin-single-page.php
includes/admin/class-schema-settings-page.php
```

## Changelog

**2.0.0**
- Added Rank Math SEO support, with automatic detection (Yoast first).
- New structure: adapters, one pipeline, one place for each job.
- `__append` implemented; `__remove` added.
- JSON is validated everywhere, with clear messages; bad input is never saved.
- Typed-in text is stripped of HTML.
- Lists are replaced instead of mixed.
- Duplicate `@id` nodes are merged.
- Overrides on Yoast archive pages no longer pick up a wrong post.
- ASDN default data removed.
- Added uninstall cleanup.
- Admin files load only on the screens that need them.

**1.2.2** and earlier: the original version (Yoast only).
