<?php
// =========================================
// admin.php - handles admin 
// =========================================



// ----------------------------------------------------
// ADMIN STYLES AND SCRIPTS 
// ----------------------------------------------------

function ost_admin_scripts_and_styles() {

    // admin styles
    $css_slug = "so-admin-styles"; 
    $css_uri = SCHEMA_ORCHESTRATOR_URL . '/assets/css/admin.css';
    $css_filetime = filemtime( SCHEMA_ORCHESTRATOR_PATH . 'assets/css/admin.css' );
    
    wp_register_style( $css_slug, $css_uri, array(), $css_filetime );
    wp_enqueue_style( $css_slug ); 
    
    // admin scripts
    $js_slug = "so-admin-scripts"; 
    $js_uri = SCHEMA_ORCHESTRATOR_URL . '/assets/js/admin.js';
    $js_filetime = filemtime( SCHEMA_ORCHESTRATOR_PATH . 'assets/js/admin.js' );

    wp_register_script( $js_slug, $js_uri, array('jquery'), $js_filetime, true );    
    wp_enqueue_script( $js_slug );   

    $ajax_url = [ 'url' => admin_url( 'admin-ajax.php' ) ];
    wp_localize_script( $js_slug, 'ajax', $ajax_url );
    
}
add_action('admin_enqueue_scripts', 'ost_admin_scripts_and_styles' );



// ----------------------------------------------------
// SETTINGS PAGES 
// ----------------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/admin/class-admin-settings-page.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/admin/class-schema-settings-page.php';
new \Schema_Orchestrator\Admin\Schema_Settings_Page();



// ----------------------------------------------------
// SINGLE PAGES / POSTS 
// ----------------------------------------------------

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/admin/class-admin-single-page.php';
new \Schema_Orchestrator\Admin\Admin_Single_Page();


