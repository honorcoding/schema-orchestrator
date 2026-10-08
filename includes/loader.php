<?php
/**
 * Loads every class file, in dependency order.
 *
 * To add a class: create its file, then add one line to the list below.
 */

defined( 'ABSPATH' ) || exit;

$schema_orchestrator_files = array(

	// Small helpers.
	'support/class-schema-logger.php',
	'support/class-schema-json-input.php',

	// Core: the schema "engine".
	'core/class-schema-context.php',
	'core/class-schema-graph.php',
	'core/class-schema-registry.php',
	'core/class-schema-overrides.php',
	'core/class-schema-pipeline.php',

	// Adapters: one per supported SEO plugin.
	'adapters/interface-schema-adapter.php',
	'adapters/class-schema-adapter-yoast.php',
	'adapters/class-schema-adapter-rank-math.php',

	// Sitewide settings.
	'settings/class-schema-settings.php',

	// Admin screens.
	'admin/class-schema-admin-help.php',
	'admin/class-schema-admin-assets.php',
	'admin/class-schema-admin-metabox.php',
	'admin/class-schema-admin-settings-page.php',

	// Main plugin class (needs everything above).
	'core/class-schema-orchestrator.php',
);

foreach ( $schema_orchestrator_files as $schema_orchestrator_file ) {
	require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/' . $schema_orchestrator_file;
}

unset( $schema_orchestrator_files, $schema_orchestrator_file );
