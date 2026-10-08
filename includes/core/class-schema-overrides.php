<?php
/**
 * Overrides: the JSON "change list" an editor types in.
 *
 * Format (all parts optional):
 *
 *   {
 *     "WebSite":                    { "name": "New name" },    <- every node of this @type
 *     "https://example.com/#org":   { "name": "Acme" },        <- the node with this @id (created if missing)
 *     "__append":                   [ { "@type": "Event" } ],  <- extra nodes to add
 *     "__remove":                   [ "BreadcrumbList" ]       <- @types or @ids to delete
 *   }
 *
 * Order of work: __append, then changes by @type / @id, then __remove.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Overrides {

	const META_KEY   = '_schema_orchestrator_overrides';
	const APPEND_KEY = '__append';
	const REMOVE_KEY = '__remove';

	/**
	 * Overrides saved on one post.
	 */
	public static function get( int $post_id ): array {

		$overrides = get_post_meta( $post_id, self::META_KEY, true );

		return is_array( $overrides ) ? $overrides : array();
	}

	/**
	 * Save overrides on one post. An empty array deletes them.
	 */
	public static function save( int $post_id, array $overrides ): void {

		if ( array() === $overrides ) {
			delete_post_meta( $post_id, self::META_KEY );
			return;
		}

		// WordPress removes one level of backslashes when saving meta, so add them first.
		update_post_meta( $post_id, self::META_KEY, wp_slash( $overrides ) );
	}

	/**
	 * Apply a set of overrides to a graph and return the new graph.
	 */
	public static function apply( array $graph, array $overrides ): array {

		// Typed-in text is never trusted: strip HTML so it cannot break out of the <script> tag.
		$overrides = Schema_Json_Input::clean_strings( $overrides );
		$graph     = array_values( $graph );

		// 1. Extra nodes.
		if ( isset( $overrides[ self::APPEND_KEY ] ) ) {
			$graph = array_merge( $graph, Schema_Graph::to_node_list( $overrides[ self::APPEND_KEY ] ) );
		}

		// 2. Changes by @type or @id.
		foreach ( $overrides as $key => $override ) {

			$key = (string) $key;

			if ( self::APPEND_KEY === $key || self::REMOVE_KEY === $key ) {
				continue;
			}

			if ( ! is_array( $override ) || Schema_Graph::is_list( $override ) ) {
				Schema_Logger::log( sprintf( 'Override "%s" ignored: it must be an object with at least one property.', $key ) );
				continue;
			}

			if ( Schema_Graph::is_type_key( $key ) ) {
				$graph = self::apply_to_type( $graph, $key, $override );
			} else {
				$graph = self::apply_to_id( $graph, $key, $override );
			}
		}

		// 3. Removals.
		if ( isset( $overrides[ self::REMOVE_KEY ] ) && is_array( $overrides[ self::REMOVE_KEY ] ) ) {
			$graph = self::remove( $graph, $overrides[ self::REMOVE_KEY ] );
		}

		return array_values( $graph );
	}

	/**
	 * Combine two sets of overrides into one. Used to stack sitewide
	 * settings on top of what developer filters supplied.
	 */
	public static function combine( array $first, array $second ): array {

		$append = array_merge(
			Schema_Graph::to_node_list( isset( $first[ self::APPEND_KEY ] ) ? $first[ self::APPEND_KEY ] : array() ),
			Schema_Graph::to_node_list( isset( $second[ self::APPEND_KEY ] ) ? $second[ self::APPEND_KEY ] : array() )
		);

		$remove = array_merge(
			isset( $first[ self::REMOVE_KEY ] ) ? (array) $first[ self::REMOVE_KEY ] : array(),
			isset( $second[ self::REMOVE_KEY ] ) ? (array) $second[ self::REMOVE_KEY ] : array()
		);

		unset(
			$first[ self::APPEND_KEY ],
			$first[ self::REMOVE_KEY ],
			$second[ self::APPEND_KEY ],
			$second[ self::REMOVE_KEY ]
		);

		$combined = Schema_Graph::merge( $first, $second );

		if ( $append ) {
			$combined[ self::APPEND_KEY ] = $append;
		}

		if ( $remove ) {
			$combined[ self::REMOVE_KEY ] = array_values( array_unique( $remove ) );
		}

		return $combined;
	}

	/**
	 * Change every node that has the given @type.
	 */
	private static function apply_to_type( array $graph, string $type, array $override ): array {

		foreach ( $graph as $index => $node ) {
			if ( is_array( $node ) && Schema_Graph::has_type( $node, $type ) ) {
				$graph[ $index ] = Schema_Graph::merge( $node, $override );
			}
		}

		return $graph;
	}

	/**
	 * Change the node with the given @id, or create it if it does not exist.
	 */
	private static function apply_to_id( array $graph, string $id, array $override ): array {

		$found = false;

		foreach ( $graph as $index => $node ) {
			if ( is_array( $node ) && Schema_Graph::get_id( $node ) === $id ) {
				$graph[ $index ] = Schema_Graph::merge( $node, $override );
				$found           = true;
			}
		}

		if ( ! $found ) {
			$new_node        = Schema_Graph::merge( array(), $override );
			$new_node['@id'] = $id;
			$graph[]         = $new_node;
		}

		return $graph;
	}

	/**
	 * Delete nodes by @type ("BreadcrumbList") or by @id ("https://...").
	 */
	private static function remove( array $graph, array $targets ): array {

		foreach ( $targets as $target ) {

			if ( ! is_string( $target ) || '' === $target ) {
				continue;
			}

			$by_type = Schema_Graph::is_type_key( $target );

			foreach ( $graph as $index => $node ) {

				if ( ! is_array( $node ) ) {
					continue;
				}

				$matches = $by_type
					? Schema_Graph::has_type( $node, $target )
					: Schema_Graph::get_id( $node ) === $target;

				if ( $matches ) {
					unset( $graph[ $index ] );
				}
			}
		}

		return array_values( $graph );
	}
}
