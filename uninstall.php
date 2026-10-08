<?php
/**
 * Runs only when the plugin is deleted from the Plugins screen.
 * Removes everything Schema Orchestrator stored in the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'schema_orchestrator_settings' );
delete_option( 'so-schema-settings' ); // Settings key used by version 1.x.
delete_post_meta_by_key( '_schema_orchestrator_overrides' );
