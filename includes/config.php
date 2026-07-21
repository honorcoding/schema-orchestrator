<?php
// ==========================================
// PLUGIN CONFIG AND SETTINGS 
// ==========================================


// ------------------------------------------
// DEBUG TOOLS 
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/debug/class-debugger.php';
function so_debug() {   
    $debugger = \SCHEMA_ORCHESTRATOR\Debugger::instance();    
    $debugger->set_log_path( SCHEMA_ORCHESTRATOR_LOG_PATH . 'debug.log' );
    return $debugger;    
}



// ------------------------------------------
// TOOLS AND UTILITIES 
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/utilities/class-utilities.php';



// ------------------------------------------
// PLUGIN SETTINGS 
// ------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/settings/class-settings-controller.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/settings/class-schema-settings.php';
\Schema_Orchestrator\Settings\Schema_Settings::instance();

// USE ONCE TO RESET DEFAULTS
// $schema_settings = \Schema_Orchestrator\Settings\Schema_Settings::instance();
// $schema_settings->reset_to_defaults();

