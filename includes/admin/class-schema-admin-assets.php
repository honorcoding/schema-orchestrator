<?php
/**
 * Loads the admin CSS and JavaScript, but only on the screens that need them:
 * the post / page editor and this plugin's Settings page.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Admin_Assets {

	public function hook(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * @param string $hook_suffix The current admin screen.
	 */
	public function enqueue( $hook_suffix ): void {

		$screens = array( 'post.php', 'post-new.php', 'settings_page_' . Schema_Admin_Settings_Page::SLUG );

		if ( ! in_array( $hook_suffix, $screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'schema-orchestrator-admin',
			SCHEMA_ORCHESTRATOR_URL . 'assets/css/admin.css',
			array(),
			SCHEMA_ORCHESTRATOR_VERSION
		);

		wp_enqueue_script(
			'schema-orchestrator-admin',
			SCHEMA_ORCHESTRATOR_URL . 'assets/js/admin.js',
			array(),
			SCHEMA_ORCHESTRATOR_VERSION,
			true
		);
	}
}
