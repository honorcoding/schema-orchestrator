<?php
/**
 * Plugin Name: Schema Orchestrator
 * Description: Schema orchestration framework for WordPress. Works with YoastSEO plugin.
 * Version: 1.1.0
 * Author: Honor Coding
 */

if (!defined('ABSPATH')) {
    exit;
}



/**
 * define plugin constants 
 */
define( 'SCHEMA_ORCHESTRATOR_PATH', plugin_dir_path(__FILE__) );
define( 'SCHEMA_ORCHESTRATOR_URL', plugin_dir_url(__FILE__) );

// plugin log folder (requires .htaccess : "deny from all" on folder) 
define('SCHEMA_ORCHESTRATOR_LOG_PATH', SCHEMA_ORCHESTRATOR_PATH . 'logs/');



/** 
 * load debugging tools 
 */

// ------------------------------------------
// DEBUG TOOLS 
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/debug/class-debugger.php';
function so_debug() {   
    $debugger = \SCHEMA_ORCHESTRATOR\Debugger::instance();    
    $debugger->set_log_path( SCHEMA_ORCHESTRATOR_LOG_PATH . 'debug.log' );
    return $debugger;    
}



/**
 * load core schema resources 
 */

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-registry.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-overrides.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-orchestrator.php';
Schema_Orchestrator::instance();



/**
 * load admin resources 
 */

if ( is_admin() ) { 
    
    require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/admin/class-schema-admin.php';
    new Schema_Admin();

}



/**
 * load test tools 
 */

//require_once SCHEMA_ORCHESTRATOR_PATH . 'test.php';