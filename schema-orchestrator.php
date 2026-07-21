<?php
/**
 * Plugin Name: Schema Orchestrator
 * Description: Schema orchestration framework for WordPress. Works with YoastSEO plugin.
 * Version: 1.2.2
 * Author: Honor Coding
 * Author URI: https://honorcoding.com 
 */

if (!defined('ABSPATH')) {
    exit;
}



// ------------------------------------------
// PLUGIN CONSTANTS
// ------------------------------------------

define( 'SCHEMA_ORCHESTRATOR_PATH', plugin_dir_path(__FILE__) );
define( 'SCHEMA_ORCHESTRATOR_URL', plugin_dir_url(__FILE__) );

// plugin log folder (requires .htaccess : "deny from all" on folder) 
define('SCHEMA_ORCHESTRATOR_LOG_PATH', SCHEMA_ORCHESTRATOR_PATH . 'logs/');



// ------------------------------------------
// PLUGIN CONFIG 
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/config.php';



// ------------------------------------------
// CORE SCHEMA RESOURCES
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/config.php';



// ------------------------------------------
// ADMIN RESOURCES
// ------------------------------------------

if ( is_admin() ) { 
    
    require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/admin/admin.php';

}



// ------------------------------------------
// TESTING RESOURCES
// ------------------------------------------

//require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/qa/test.php';