<?php
/**
 * Schema Registry: the extension API for plugins and themes.
 *
 * A callback registered here says "I know how to build this kind of schema,
 * call me for every page". It receives ( int $post_id, Schema_Context $context )
 * and returns one node or a list of nodes.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Registry {

	/**
	 * @var array<string, callable>
	 */
	private static $nodes = array();

	/**
	 * Register (or replace) a node builder.
	 */
	public static function register_node( string $name, callable $callback ): void {
		self::$nodes[ $name ] = $callback;
	}

	public static function unregister_node( string $name ): void {
		unset( self::$nodes[ $name ] );
	}

	/**
	 * @return array<string, callable>
	 */
	public static function get_nodes(): array {
		return self::$nodes;
	}

	/**
	 * Run every registered callback and collect the nodes.
	 * A callback that fails is logged and skipped; it never breaks the page.
	 */
	public static function build_nodes( Schema_Context $context ): array {

		$nodes = array();

		foreach ( self::$nodes as $name => $callback ) {

			try {
				$result = call_user_func( $callback, $context->id, $context );
				$nodes  = array_merge( $nodes, Schema_Graph::to_node_list( $result ) );
			} catch ( \Throwable $e ) {
				Schema_Logger::log( sprintf( 'Registered node "%s" failed: %s', $name, $e->getMessage() ) );
			}
		}

		return $nodes;
	}
}
