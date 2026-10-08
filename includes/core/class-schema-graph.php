<?php
/**
 * Small helpers for working with schema nodes. None of them change anything
 * outside the data they are given.
 *
 * Words used in this plugin:
 *   node  = one schema entity (an Organization, a WebPage ...) as a PHP array.
 *           It normally has "@type" and/or "@id".
 *   graph = a plain list of nodes.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Graph {

	/**
	 * True for [] and for [a, b, c] (keys 0, 1, 2 ...). False for {"a": 1}.
	 */
	public static function is_list( array $value ): bool {

		if ( array() === $value ) {
			return true;
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/**
	 * A node is a key/value array that has "@type" or "@id".
	 *
	 * @param mixed $value Anything.
	 */
	public static function is_node( $value ): bool {

		return is_array( $value )
			&& ! self::is_list( $value )
			&& ( isset( $value['@type'] ) || isset( $value['@id'] ) );
	}

	/**
	 * Accepts one node OR a list of nodes and always returns a clean list
	 * of nodes. Anything that is not a node is dropped (and logged).
	 *
	 * @param mixed $value Whatever a callback or filter returned.
	 */
	public static function to_node_list( $value ): array {

		if ( ! is_array( $value ) || array() === $value ) {
			return array();
		}

		if ( self::is_node( $value ) ) {
			return array( $value );
		}

		$nodes = array();

		foreach ( $value as $item ) {
			if ( self::is_node( $item ) ) {
				$nodes[] = $item;
			} else {
				Schema_Logger::log( 'Ignored an item that is not a schema node (it needs "@type" or "@id").' );
			}
		}

		return $nodes;
	}

	/**
	 * Merge $override into $base.
	 *
	 * Rules:
	 *  - Objects (key/value arrays) are merged key by key.
	 *  - Lists are replaced as a whole, never mixed index by index.
	 *  - Text, numbers and booleans are replaced.
	 *  - null removes the property.
	 *  - An empty {} is ignored, so it can never wipe out an existing object.
	 */
	public static function merge( array $base, array $override ): array {

		foreach ( $override as $key => $value ) {

			if ( null === $value ) {
				unset( $base[ $key ] );
				continue;
			}

			$base_is_object = isset( $base[ $key ] )
				&& is_array( $base[ $key ] )
				&& ! self::is_list( $base[ $key ] );

			if ( array() === $value && $base_is_object ) {
				continue;
			}

			if ( $base_is_object && is_array( $value ) && ! self::is_list( $value ) ) {
				$base[ $key ] = self::merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * If two nodes share the same "@id", combine them into one (the later
	 * one wins on conflicts). Nodes without an "@id" are kept as they are.
	 */
	public static function merge_duplicate_ids( array $graph ): array {

		$result   = array();
		$position = array(); // "@id" => index in $result.

		foreach ( $graph as $node ) {

			$id = is_array( $node ) ? self::get_id( $node ) : null;

			if ( null === $id ) {
				$result[] = $node;
				continue;
			}

			if ( isset( $position[ $id ] ) ) {
				$result[ $position[ $id ] ] = self::merge( $result[ $position[ $id ] ], $node );
				continue;
			}

			$position[ $id ] = count( $result );
			$result[]        = $node;
		}

		return $result;
	}

	public static function get_id( array $node ): ?string {
		return ( isset( $node['@id'] ) && is_string( $node['@id'] ) ) ? $node['@id'] : null;
	}

	/**
	 * All "@type" values of a node as short names ("WebPage", not
	 * "https://schema.org/WebPage").
	 */
	public static function get_types( array $node ): array {

		if ( ! isset( $node['@type'] ) ) {
			return array();
		}

		$types = array();

		foreach ( (array) $node['@type'] as $type ) {
			if ( is_string( $type ) ) {
				$types[] = self::normalize_type( $type );
			}
		}

		return $types;
	}

	public static function has_type( array $node, string $type ): bool {
		return in_array( $type, self::get_types( $node ), true );
	}

	public static function normalize_type( string $type ): string {
		return str_replace( array( 'https://schema.org/', 'http://schema.org/', 'schema:' ), '', $type );
	}

	/**
	 * "WebPage" or "Organization" = a type name (plain letters and digits).
	 * Anything else ("https://x.com/#id", "#faq") = an @id.
	 */
	public static function is_type_key( string $key ): bool {
		return 1 === preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $key );
	}
}
