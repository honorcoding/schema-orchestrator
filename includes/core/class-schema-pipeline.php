<?php
/**
 * The pipeline: the one place that decides what happens to a graph, and in
 * which order. Every SEO plugin adapter sends its graph through here.
 *
 * Steps:
 *   1. Filter  schema_orchestrator_pre_merge_graph
 *   2. Nodes from the Schema_Registry
 *   3. Filter  schema_orchestrator_additional_nodes
 *   4. Sitewide overrides (Filter schema_orchestrator_global_overrides;
 *      the Settings page uses this filter)
 *   5. Overrides typed into this page's meta box
 *   6. Nodes that share an @id are merged into one
 *   7. Filter  schema_orchestrator_final_graph
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Pipeline {

	public static function run( array $graph, Schema_Context $context ): array {

		$graph = array_values( $graph );

		// 1. Pre-merge filter.
		$graph = self::filter_graph( 'schema_orchestrator_pre_merge_graph', $graph, $context );

		// 2. Registry.
		$graph = array_merge( $graph, Schema_Registry::build_nodes( $context ) );

		// 3. Additional nodes filter.
		$extra = apply_filters( 'schema_orchestrator_additional_nodes', array(), $context->id, $context );
		$graph = array_merge( $graph, Schema_Graph::to_node_list( $extra ) );

		// 4. Sitewide overrides.
		$sitewide = apply_filters( 'schema_orchestrator_global_overrides', array(), $context->id, $context );

		if ( is_array( $sitewide ) && array() !== $sitewide ) {
			$graph = Schema_Overrides::apply( $graph, $sitewide );
		}

		// 5. This page's overrides.
		if ( $context->id > 0 ) {
			$page_overrides = Schema_Overrides::get( $context->id );

			if ( array() !== $page_overrides ) {
				$graph = Schema_Overrides::apply( $graph, $page_overrides );
			}
		}

		// 6. One node per @id.
		$graph = Schema_Graph::merge_duplicate_ids( $graph );

		// 7. Final filter.
		return self::filter_graph( 'schema_orchestrator_final_graph', $graph, $context );
	}

	/**
	 * Run a filter that must return a graph. If a callback returns something
	 * else, keep the graph as it was before the filter.
	 */
	private static function filter_graph( string $hook, array $graph, Schema_Context $context ): array {

		$result = apply_filters( $hook, $graph, $context->id, $context );

		if ( ! is_array( $result ) ) {
			Schema_Logger::log( sprintf( 'Filter "%s" did not return an array; its result was ignored.', $hook ) );
			return $graph;
		}

		return array_values( $result );
	}
}
