<?php
/**
 * An adapter connects Schema Orchestrator to one SEO plugin.
 *
 * Its job is small: when the SEO plugin is about to print its schema, hand
 * the schema to $process (as a plain list of nodes), and give the SEO plugin
 * back whatever $process returns, in the shape that plugin expects.
 *
 * To support another SEO plugin, implement this interface and add the
 * adapter with the "schema_orchestrator_adapters" filter.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

interface Schema_Adapter {

	/**
	 * Short machine name, e.g. "yoast".
	 */
	public function get_slug(): string;

	/**
	 * Name shown to people, e.g. "Yoast SEO".
	 */
	public function get_label(): string;

	/**
	 * Is the SEO plugin installed and running right now?
	 */
	public function is_active(): bool;

	/**
	 * Attach to the SEO plugin's schema hook.
	 *
	 * @param callable $process function( array $graph, Schema_Context $context ): array
	 */
	public function register( callable $process ): void;
}
