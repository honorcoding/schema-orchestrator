<?php
/**
 * Schema Provider - Yoast :
 */

/**
 * @todo
 * Replace with stable provider implementation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Schema_Provider_Yoast
    implements Schema_Provider_Interface {

    public function get_name(): string {

        return 'yoast';
    }

    public function get_graph(
        int $post_id
    ): array {

        if (
            !function_exists('YoastSEO')
        ) {
            return [];
        }

        try {
            $yoast = YoastSEO();

            if (
                !isset($yoast->classes)
                || !isset($yoast->classes->schema)
            ) {
                return [];
            }

            $schema = $yoast->classes->schema;

            if (
                !isset($schema->context)
                || !isset($schema->graph)
            ) {
                return [];
            }

            if (
                !method_exists(
                    $schema->context,
                    'generate'
                )
            ) {
                return [];
            }

            if (
                !method_exists(
                    $schema->graph,
                    'build'
                )
            ) {
                return [];
            }

            /*
             * IMPORTANT:
             * Raw graph only.
             * No wpseo_schema_graph filter.
             */

            $context =
                $schema->context->generate();

            $graph =
                $schema->graph->build(
                    $context
                );

            return is_array($graph)
                ? $graph
                : [];

        } catch (\Throwable $e) {

            debugger->log(
                '[Schema Orchestrator] Yoast provider: '
                . $e->getMessage()
            );

            return [];
        }
    }
}