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
     * Apply overrides.
     */
    public static function apply(
        array $graph,
        array $overrides
    ): array {
        foreach ($graph as &$node) {

            if (!is_array($node)) {
                continue;
            }

            /*
             * Match by @id
             */
            if (
                isset($node['@id']) &&
                isset(
                    $overrides[
                        $node['@id']
                    ]
                )
            ) {

                $node =
                    array_replace_recursive(
                        $node,
                        $overrides[
                            $node['@id']
                        ]
                    );
            }

            /*
             * Match by @type
             */
            if (
                isset($node['@type'])
            ) {

                $types =
                    (array)
                    $node['@type'];

                foreach (
                    $types as $type
                ) {

                    if (
                        isset(
                            $overrides[$type]
                        )
                    ) {

                        $node =
                            array_replace_recursive(
                                $node,
                                $overrides[$type]
                            );
                    }
                }
            }
        }

        unset($node);

        /*
         * Append nodes
         */
        if (
            isset(
                $overrides['__append']
            ) &&
            is_array(
                $overrides['__append']
            )
        ) {

            $graph = array_merge(
                $graph,
                $overrides['__append']
            );
        }

        return $graph;
    }
}