<?php
/**
 * Schema Overrides
 * Overrides other schema tools. 
 */


namespace Schema_Orchestrator;


if (!defined('ABSPATH')) {
    exit;
}

class Schema_Overrides {

    const META_KEY =
        '_schema_orchestrator_overrides';

    /**
     * Get overrides.
     */
    public static function get(
        int $post_id
    ): array {

        $overrides = get_post_meta(
            $post_id,
            self::META_KEY,
            true
        );

        return is_array($overrides)
            ? $overrides
            : [];
    }

    /**
     * Save overrides.
     */
    public static function save(
        int $post_id,
        array $overrides
    ): void {

        update_post_meta(
            $post_id,
            self::META_KEY,
            $overrides
        );
    }

    /**
     * Apply schema overrides.
     */
    public static function apply(
        array $graph,
        array $overrides
    ): array {

        foreach ($overrides as $key => $override) {

            /*
             * Ignore invalid override entries.
             */
            if (
                !is_string($key) ||
                !is_array($override)
            ) {
                continue;
            }

            /*
             * -------------------------------------------------
             * 1. TYPE-BASED OVERRIDE
             * -------------------------------------------------
             *
             * Example:
             *
             * "WebPage": {
             *     "description": "..."
             * }
             *
             * Match existing nodes by @type.
             */
            if (
                self::is_schema_type(
                    $key
                )
            ) {

                foreach (
                    $graph as &$node
                ) {

                    if (
                        !is_array($node) ||
                        !isset($node['@type'])
                    ) {
                        continue;
                    }

                    $types =
                        (array)
                        $node['@type'];

                    /*
                     * Normalize full Schema.org
                     * URLs to short type names.
                     *
                     * Example:
                     *
                     * http://schema.org/WebPage
                     *
                     * becomes:
                     *
                     * WebPage
                     */
                    $normalized_types = [];

                    foreach (
                        $types as $type
                    ) {

                        $normalized_types[] =
                            self::normalize_schema_type(
                                $type
                            );
                    }

                    /*
                     * Does this node match
                     * the override type?
                     */
                    if (
                        in_array(
                            $key,
                            $normalized_types,
                            true
                        )
                    ) {

                        $node =
                            array_replace_recursive(
                                $node,
                                $override
                            );
                    }
                }

                unset($node);

                /*
                 * Do not treat a type name
                 * as an @id.
                 */
                continue;
            }


            /*
             * -------------------------------------------------
             * 2. ID-BASED OVERRIDE
             * -------------------------------------------------
             *
             * Example:
             *
             * "https://example.com/#faq": {
             *     "@type": "FAQPage"
             * }
             *
             * If the ID exists:
             *     Modify it.
             *
             * If the ID does not exist:
             *     Create it.
             */
            $found = false;

            foreach (
                $graph as &$node
            ) {

                if (
                    !is_array($node) ||
                    !isset($node['@id'])
                ) {
                    continue;
                }

                if (
                    $node['@id'] !== $key
                ) {
                    continue;
                }

                /*
                 * Existing node found.
                 */
                $node =
                    array_replace_recursive(
                        $node,
                        $override
                    );

                $found = true;

                break;
            }

            unset($node);


            /*
             * -------------------------------------------------
             * 3. CREATE NEW NODE
             * -------------------------------------------------
             */
            if (!$found) {

                $new_node =
                    $override;

                /*
                 * Ensure the node has
                 * the requested @id.
                 */
                $new_node['@id'] =
                    $key;

                $graph[] =
                    $new_node;
            }
        }

        return $graph;
    }

    /**
     * Determine whether a key is a Schema.org type.
     */
    private static function is_schema_type(
        string $key
    ): bool {

        /*
         * Common Schema.org type names
         * are simple identifiers such as:
         *
         * WebPage
         * Organization
         * Person
         * Product
         * FAQPage
         * Event
         *
         * IDs are generally URLs.
         */
        return !filter_var(
            $key,
            FILTER_VALIDATE_URL
        );
    }

    /**
     * Normalize a Schema.org type.
     */
    private static function normalize_schema_type(
        string $type
    ): string {

        /*
         * Convert:
         *
         * http://schema.org/WebPage
         *
         * to:
         *
         * WebPage
         */
        $type =
            str_replace(
                [
                    'http://schema.org/',
                    'https://schema.org/'
                ],
                '',
                $type
            );

        return $type;
    }


}