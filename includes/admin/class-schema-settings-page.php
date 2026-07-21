<?php
// =========================================
// class Schema_Settings_Page : class-schema-settings-page.php 
// -----------------------------------------
// handles settings page for schema 
// =========================================

namespace Schema_Orchestrator\Admin;
use Schema_Orchestrator\Admin\Admin_Settings_Page;


defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'Schema_Settings_Page' ) ): 
    class Schema_Settings_Page extends Admin_Settings_Page {
    
        protected $schema_settings;
    
    
        public function __construct() {
            
            $this->schema_settings = \Schema_Orchestrator\Settings\Schema_Settings::instance();
            
            parent::__construct(
                'Schema Settings',
                'Schema Settings',
                'schema-settings',
                'manage_options', 
            );
            
        } // end: __construct() 
    
        
        /**
         * displays the schema settings content 
         * @return void
         */
        protected function render_content() : void {
            
            // ------------------------------------------
            // WOOCOMMERCE SETTINGS             
            // ------------------------------------------

            $schema = $this->schema_settings->all();
            
            ?>
                <section>
                    
                    <h2>Organization</h2>

                    <div class="field">
                        <label for="organization_schema">Organization Schema</label>
                        <textarea id="organization_schema" name="organization_schema"><?php echo esc_textarea( $schema['organization'] ?? '' ); ?></textarea>
                    </div>
                    
                    <div class="field">
                        <div class="padding"></div>
                        <button type="submit" name="action" value="update_schema_settings">Update</button>
                    </div>
                    
                </section>
            <?php            
            
        } // end : render_content()
        
        
        /**
         * handles the form posts  
         * 
         * for each action: 
         * 
         *      // submit action in render_content()
         *      <button type="submit" name="action" value="my_action">Some Action Here</button>
         * 
         *      // check action in process_form_post()
         *      if ( $this->is_action( 'my_action' ) ) {...}
         *      
         * 
         * @return void 
         */
        protected function process_form_post() : void {
            
            if ( $this->verify_nonce() ) {
                
                // if update_schema_settings is clicked 
                if ( $this->is_action( 'update_schema_settings' ) ) {
                    
                    $post = [
                        'organization' => $this->get_form_post( 'organization_schema' ),
                    ];          

                    $schema = $this->schema_settings->all();
                    $changed = true;
                    
                    if ( $schema['organization'] !== $post['organization'] ) {
//                        
// DEBUG / TODO: 
// need to check new json-ld for syntax errors... create a nice utility function to do that?... 
// also, consider checking for syntax errors in single-page-admin.php too 
//                        
                        $this->schema_settings->set( 'organization', $post['organization'] );
                    }
                    
                    if ( $changed ) {
                        $this->add_message( 'Organization schema updated.' );
                    }
                    
                } // end : if action: 'update_schema_settings'
                
                
            } // end : if verify_nonce 
            
        } // end : process_form_post();        

        
    
    } // end : Schema_Settings_Page     
endif;

