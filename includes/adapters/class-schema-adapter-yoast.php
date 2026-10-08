<?php
/**
 * Adapter for Yoast SEO.
 *
 * Hook: "wpseo_schema_graph" ( array $graph, Meta_Tags_Context $context ).
 * Yoast already gives a plain list of nodes, so no reshaping is needed.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Adapter_Yoast implements Schema_Adapter {

	public function get_slug(): string {
		return 'yoast';
	}

	public function get_label(): string {
		return 'Yoast SEO';
	}

	public function is_active(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function register( callable $process ): void {

		add_filter(
			'wpseo_schema_graph',
			function ( $graph, $context = null ) use ( $process ) {

				if ( ! is_array( $graph ) ) {
					return $graph;
				}

				$schema_context = new Schema_Context( self::post_id_from( $context ), $this->get_slug(), $context );

				return call_user_func( $process, $graph, $schema_context );
			},
			999,
			2
		);
	}

	/**
	 * Yoast describes the current page with an "indexable". Its object_id is a
	 * post ID only when object_type is "post"; on category pages it is a term
	 * ID and on author pages a user ID, which must not be mistaken for a post.
	 *
	 * @param mixed $context Yoast's Meta_Tags_Context.
	 */
	private static function post_id_from( $context ): int {

		if ( ! is_object( $context ) || ! property_exists( $context, 'indexable' ) || ! is_object( $context->indexable ) ) {
			return 0;
		}

		try {
			$type = $context->indexable->object_type;
			$id   = $context->indexable->object_id;
		} catch ( \Throwable $e ) {
			return 0;
		}

		return ( 'post' === $type ) ? (int) $id : 0;
	}
}
