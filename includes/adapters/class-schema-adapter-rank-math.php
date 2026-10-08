<?php
/**
 * Adapter for Rank Math SEO.
 *
 * Hook: "rank_math/json_ld" ( array $data, JsonLD $jsonld ).
 *
 * Rank Math hands over an array keyed by name ( 'WebPage' => node,
 * 'richSnippet' => node ... ), not a plain list. This adapter:
 *   1. turns it into a plain list, remembering each node's original key,
 *   2. runs the pipeline,
 *   3. turns the result back into a keyed array, so Rank Math (and any
 *      other code on the same filter) sees the shape it expects.
 * Nodes that did not exist before (added by this plugin) get keys that start
 * with "so_".
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Adapter_Rank_Math implements Schema_Adapter {

	/**
	 * Temporary note stored inside each node while the pipeline runs.
	 * It is always removed again before the data goes back to Rank Math.
	 */
	const KEY_MARKER = '__schema_orchestrator_key';

	public function get_slug(): string {
		return 'rank-math';
	}

	public function get_label(): string {
		return 'Rank Math SEO';
	}

	public function is_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	public function register( callable $process ): void {

		add_filter(
			'rank_math/json_ld',
			function ( $data, $jsonld = null ) use ( $process ) {

				// Rank Math decided this page has no schema: leave it alone.
				if ( ! is_array( $data ) || array() === $data ) {
					return $data;
				}

				$context = new Schema_Context( self::current_post_id(), $this->get_slug(), $jsonld );

				list( $graph, $untouched ) = self::to_graph( $data );

				$graph = call_user_func( $process, $graph, $context );

				return self::to_data( $graph, $untouched );
			},
			99,
			2
		);
	}

	/**
	 * Rank Math's keyed array -> plain list of nodes (plus entries that are
	 * not nodes, which are passed through untouched).
	 *
	 * @return array{0: array, 1: array}
	 */
	private static function to_graph( array $data ): array {

		$graph     = array();
		$untouched = array();

		foreach ( $data as $key => $entity ) {

			if ( Schema_Graph::is_node( $entity ) ) {
				$entity[ self::KEY_MARKER ] = (string) $key;
				$graph[]                    = $entity;
			} else {
				$untouched[ $key ] = $entity;
			}
		}

		return array( $graph, $untouched );
	}

	/**
	 * Plain list of nodes -> Rank Math's keyed array.
	 */
	private static function to_data( array $graph, array $untouched ): array {

		$data = $untouched;

		foreach ( $graph as $node ) {

			if ( ! is_array( $node ) ) {
				continue;
			}

			$key = isset( $node[ self::KEY_MARKER ] ) ? (string) $node[ self::KEY_MARKER ] : '';
			unset( $node[ self::KEY_MARKER ] );

			if ( '' === $key || isset( $data[ $key ] ) ) {
				$key = self::new_key( $node, $data );
			}

			$data[ $key ] = $node;
		}

		return $data;
	}

	/**
	 * A fresh, unused key for a node that Rank Math did not create.
	 */
	private static function new_key( array $node, array $taken ): string {

		$types = Schema_Graph::get_types( $node );
		$base  = $types ? preg_replace( '/[^A-Za-z0-9_]/', '', $types[0] ) : '';
		$base  = 'so_' . ( '' === $base ? 'node' : $base );

		$key    = $base;
		$number = 2;

		while ( isset( $taken[ $key ] ) ) {
			$key = $base . '_' . $number;
			++$number;
		}

		return $key;
	}

	/**
	 * Post ID of the page being shown, or 0 on archives, search, 404 ...
	 */
	private static function current_post_id(): int {

		if ( is_singular() ) {
			return (int) get_queried_object_id();
		}

		// The page chosen as "Posts page" in Settings > Reading is a real page with an ID.
		if ( is_home() && ! is_front_page() ) {
			return (int) get_option( 'page_for_posts' );
		}

		return 0;
	}
}
