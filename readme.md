# Schema Orchestrator

Schema Orchestrator is a WordPress schema management framework designed to work with Yoast SEO. It provides a structured way to extend, modify, and control the final Schema.org graph generated on your website.

Instead of relying only on Yoast's default schema output, Schema Orchestrator provides multiple ways to manage schema:

* **Meta Box JSON overrides** for page-specific changes
* **Schema Providers / Registry** for reusable integrations
* **Developer filters** for advanced customization
* **Yoast schema graph integration** for final output control

---

# How It Works

The schema flow:

```
Yoast SEO Schema Graph
          |
          |
          v
Schema Orchestrator
          |
          |
 +----------------------+
 |                      |
 |                      |
Registry Providers   JSON Overrides
 |                      |
 |                      |
 +----------+-----------+
            |
            |
            v
Developer Filters
            |
            |
            v
Final Schema Graph
            |
            |
            v
Yoast JSON-LD Output
```

Schema Orchestrator acts as a middleware layer between Yoast SEO and the final schema output.

---

# Ways To Extend Schema

There are three primary ways to interact with Schema Orchestrator.

---

# 1. Meta Box JSON Overrides

The Schema Orchestrator meta box allows editors to modify schema on individual pages.

This is intended for:

* Adding schema to specific pages
* Updating existing schema values
* Adding custom entities
* One-off changes

Example:

```json
{
    "__append": [
        {
            "@type": "Organization",
            "@id": "https://example.com/#organization",
            "name": "Example Organization",
            "url": "https://example.com"
        }
    ]
}
```

This creates a new schema node on that page.

## Override existing schema

Modify a node by `@id`:

```json
{
    "https://example.com/#website": {
        "name": "Example Website"
    }
}
```

Modify a node by schema type:

```json
{
    "WebSite": {
        "name": "Example Website"
    }
}
```

---

# 2. Schema Registry (Providers)

The Schema Registry is the primary extension API for developers.

It allows plugins and themes to register reusable schema generators.

A provider says:

> "I know how to create this type of schema. Call me when building the graph."

Example:

```php
Schema_Registry::register_node(
    'organization',
    function( $post_id, $context ) {

        return [
            [
                '@type' => 'Organization',
                'name'  => 'Example Organization'
            ]
        ];

    }
);
```

When Schema Orchestrator builds the graph, registered providers are executed automatically.

Example providers:

```
Schema Registry

organization
    |
    +-- Creates Organization schema


product
    |
    +-- Creates Product schema


course
    |
    +-- Creates Course schema
```

Use the Registry for:

* WooCommerce integrations
* Learning platforms
* Custom post types
* Reusable schema components
* Plugin integrations

---

# 3. Developer Filters

Filters allow developers to modify the schema pipeline.

Use filters when you need to adjust or intercept schema behavior.

---

## Additional Nodes

Add schema dynamically:

```php
add_filter(
    'schema_orchestrator_additional_nodes',
    function( $nodes, $post_id ) {

        $nodes[] = [
            '@type' => 'Organization',
            'name'  => 'Example Organization'
        ];

        return $nodes;

    },
    10,
    2
);
```

---

## Pre-Merge Filter

Runs before additional schema is added.

Use for:

* Modifying incoming schema
* Preparing data
* Adjusting nodes before processing

```php
schema_orchestrator_pre_merge_graph
```

---

## Final Graph Filter

Runs immediately before schema is returned.

Use for:

* Removing nodes
* Last-minute changes
* Final cleanup

```php
schema_orchestrator_final_graph
```

---

# Registry vs Filters

Both can add schema, but they serve different purposes.

| Feature                 | Registry                           | Filters                  |
| ----------------------- | ---------------------------------- | ------------------------ |
| Purpose                 | Provide reusable schema generators | Modify schema processing |
| Best for                | Plugins and integrations           | Custom adjustments       |
| Creates schema          | Yes                                | Yes                      |
| Knows provider identity | Yes                                | No                       |
| Reusable                | Yes                                | Usually no               |
| Dynamic modifications   | Limited                            | Excellent                |

Example:

A WooCommerce integration should use the Registry:

```
WooCommerce
      |
      |
Product Schema Provider
      |
      |
Schema Registry
      |
      |
Schema Orchestrator
```

A developer changing Yoast's WebPage title should use a filter.

---

# Architecture Overview

```
Schema Orchestrator

├── Schema_Orchestrator
│       Core engine
│       Connects to Yoast schema
│
├── Schema_Registry
│       Stores schema providers
│
├── Schema_Overrides
│       Handles page-level JSON changes
│
├── Schema_Admin
│       Provides WordPress editor interface
│
└── Developer Filters
        Allows advanced customization
```

---

# Yoast Integration

Schema Orchestrator connects through:

```php
wpseo_schema_graph
```

The plugin receives Yoast's schema graph, processes it, and returns the modified graph.

This allows:

* Adding schema
* Updating schema
* Extending schema
* Creating custom schema relationships

without replacing Yoast SEO.

---

# Recommended Usage

## Site editors

Use:

```
Schema Orchestrator Meta Box
```

For:

* Individual pages
* Custom entities
* Manual additions

---

## Developers

Use:

```
Schema Registry
```

For:

* Plugins
* Themes
* Reusable schema builders

Use:

```
Filters
```

For:

* Modifying existing output
* Advanced customization
* Final adjustments

---

# Goal

Schema Orchestrator provides a flexible schema layer for WordPress:

```
WordPress Content
        |
        |
Schema Providers
        |
        |
Schema Orchestrator
        |
        |
Yoast SEO
        |
        |
Search Engine Structured Data
```

The goal is to make schema management modular, extensible, and developer-friendly.
