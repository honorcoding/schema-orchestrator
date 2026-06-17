<?php
/**
 * Schema Registry :
 * This is the extension API.
 */


if (!defined('ABSPATH')) {
    exit;
}

class Schema_Registry {

    /**
     * Registered node builders.
     *
     * @var array
     */
    private static $nodes = [];

    /**
     * Register node callback.
     */
    public static function register_node( string $name, callable $callback ): void {
        self::$nodes[$name] = $callback;
    }

    /**
     * Get registered callbacks.
     */
    public static function get_nodes(): array {
        return self::$nodes;
    }

    /**
     * Build nodes.
     */
    public static function build_nodes( int $post_id, $context = null ): array {

        $nodes = [];

        foreach (self::$nodes as $name => $callback) {

            try {

                $result = call_user_func( $callback, $post_id, $context );

                if (is_array($result)) {
                    $nodes = array_merge( $nodes, $result );                    
                }

            } catch (\Throwable $e) {

                debugger->log(
                    sprintf(
                        '[Schema Orchestrator] Node "%s" failed: %s',
                        $name,
                        $e->getMessage()
                    )
                );
                
            }
        }

        return $nodes;
    }
}