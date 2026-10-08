<?php
/**
 * Plugin Name: Schema Orchestrator
 * Description: Add, change and remove Schema.org (JSON-LD) data on top of Yoast SEO or Rank Math SEO.
 * Version:     2.0.0
 * Requires PHP: 7.4
 * Author:      Honor Coding
 * Author URI:  https://honorcoding.com
 * Text Domain: schema-orchestrator
 */

defined( 'ABSPATH' ) || exit;

define( 'SCHEMA_ORCHESTRATOR_VERSION', '2.0.0' );
define( 'SCHEMA_ORCHESTRATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCHEMA_ORCHESTRATOR_URL', plugin_dir_url( __FILE__ ) );

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/loader.php';

// Everything else starts on "plugins_loaded", after Yoast / Rank Math have loaded too.
\Schema_Orchestrator\Schema_Orchestrator::instance()->hook();
