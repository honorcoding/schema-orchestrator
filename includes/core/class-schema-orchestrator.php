<?php
/**
 * Schema Orchestrator: the main class.
 *
 * It starts everything, finds out which SEO plugin is running, and connects
 * to that plugin through the matching adapter.
 *
 * Which SEO plugin is used? The first active one in this order:
 *   1. Yoast SEO
 *   2. Rank Math SEO
 * Developers can change the list with the "schema_orchestrator_adapters" filter.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Orchestrator {

	/**
	 * @var Schema_Orchestrator|null
	 */
	private static $instance = null;

	/**
	 * The adapter in use, or null when no supported SEO plugin is active.
	 *
	 * @var Schema_Adapter|null
	 */
	private $adapter = null;

	/**
	 * The last graph that was produced (handy when debugging).
	 *
	 * @var array
	 */
	private $last_graph = array();

	public static function instance(): self {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Called once from the main plugin file.
	 */
	public function hook(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ), 20 );
	}

	/**
	 * Runs on "plugins_loaded", when every other plugin's code is available.
	 */
	public function boot(): void {

		// Lets other plugins and themes register schema builders.
		add_action( 'init', array( $this, 'fire_register_action' ), 5 );

		// Sitewide settings feed the pipeline on every page, so always load them.
		Schema_Settings::instance()->hook();

		$this->adapter = $this->find_adapter();

		if ( null !== $this->adapter ) {
			$this->adapter->register( array( $this, 'process' ) );
		}

		if ( is_admin() ) {
			( new Schema_Admin_Assets() )->hook();
			( new Schema_Admin_Metabox() )->hook();
			( new Schema_Admin_Settings_Page() )->hook();

			add_action( 'admin_notices', array( $this, 'maybe_show_missing_plugin_notice' ) );
		}
	}

	/**
	 * Action: schema_orchestrator_register
	 * The place for other code to call Schema_Registry::register_node().
	 */
	public function fire_register_action(): void {
		do_action( 'schema_orchestrator_register' );
	}

	/**
	 * Receives a graph from an adapter, runs the pipeline, returns the result.
	 *
	 * @param array          $graph   List of schema nodes.
	 * @param Schema_Context $context Describes the current page.
	 */
	public function process( array $graph, Schema_Context $context ): array {

		$this->last_graph = Schema_Pipeline::run( $graph, $context );

		return $this->last_graph;
	}

	/**
	 * Picks the first adapter whose SEO plugin is active.
	 */
	private function find_adapter(): ?Schema_Adapter {

		$adapters = apply_filters(
			'schema_orchestrator_adapters',
			array(
				new Schema_Adapter_Yoast(),
				new Schema_Adapter_Rank_Math(),
			)
		);

		if ( ! is_array( $adapters ) ) {
			return null;
		}

		foreach ( $adapters as $adapter ) {
			if ( $adapter instanceof Schema_Adapter && $adapter->is_active() ) {
				return $adapter;
			}
		}

		return null;
	}

	/**
	 * The adapter in use, or null.
	 */
	public function get_adapter(): ?Schema_Adapter {
		return $this->adapter;
	}

	/**
	 * The last graph produced during this request.
	 */
	public function get_last_graph(): array {
		return $this->last_graph;
	}

	/**
	 * Tell administrators why nothing happens when no SEO plugin is active.
	 * Only shown on the Plugins screen and on this plugin's own settings page.
	 */
	public function maybe_show_missing_plugin_notice(): void {

		if ( null !== $this->adapter || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'settings_page_' . Schema_Admin_Settings_Page::SLUG ), true ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>'
			. esc_html__( 'Schema Orchestrator needs Yoast SEO or Rank Math SEO. Neither is active, so no schema is being changed.', 'schema-orchestrator' )
			. '</p></div>';
	}
}
