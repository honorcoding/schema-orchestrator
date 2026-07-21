<?php
/**
 * ============================================================
 * Admin_Settings_Page : class - class-admin-settings-page.php 
 * ============================================================
 * Base class for admin settings pages 
 * ------------------------------------------------------------
 * 
 * Child overrides:
 *     __construct() 
 *     render_content()
 *     process_form_post() 
 * 
 * in order to process form posts: 
 *     copy and paste process_form_post() to the child class (modify as needed)
 *     for alerts or errors, use $this->add_message()
 * 
 */

namespace Schema_Orchestrator\Admin; 
use Schema_Orchestrator\Settings\Settings_Controller;
use Schema_Orchestrator\Utilities;


defined( 'ABSPATH' ) || exit;   // no access for random strangers


if ( ! class_exists( 'Admin_Settings_Page' ) ) : 
    abstract class Admin_Settings_Page {

    
        // -----------------------------------------
        // PROPERTIES 
        // -----------------------------------------
    
        /**
         * page details  
         */
        protected $page_title;
        protected $menu_title;
        protected $menu_slug;
        protected $capability; 
        protected $parent_slug = null;

        
        /**
         * page messages 
         */
        protected $messages;

        
        /**
         * form posts 
         */
        protected $nonce_action; 
        protected $nonce_name;
        
        
        
        // -----------------------------------------
        // INSTANTIATION
        // -----------------------------------------
    
        /**
         * child class overrides this construct as follows 
         * 
         * public function __construct() {
         *      parent::__construct(
         *          $settings, $page_title, $menu_title, $menu_slug, $capability 
         *      );
         * }
         * 
         * @param Settings_Controller $settings
         * @param string $page_title
         * @param string $menu_title
         * @param string $menu_slug
         * @param string $capability
         * @param string $parent_slug (optional) Example: 'woocommerce' adds this page under the Woocommerce menu
         */
        public function __construct( 
                string $page_title,  
                string $menu_title,
                string $menu_slug,
                string $capability,     
                string $parent_slug = null
            ) {

            $this->page_title  = Utilities::is_string( $page_title ) ? trim( $page_title ) : null;
            $this->menu_title  = Utilities::is_string( $menu_title ) ? trim( $menu_title ) : null;
            $this->menu_slug   = Utilities::is_string( $menu_slug ) ? trim( $menu_slug ) : null;
            $this->capability  = Utilities::is_string( $capability ) ? trim( $capability ) : null;
            $this->parent_slug = Utilities::is_string( $parent_slug ) ? trim( $parent_slug ) : null;
            
            $this->nonce_action = ( $this->menu_slug ) ? $this->menu_slug . '_submit' : null;
            $this->nonce_name   = ( $this->menu_slug ) ? $this->menu_slug . '_nonce' : null;

            add_action( 'admin_menu', [ $this, 'register_menu' ] );

        }

        public function register_menu() {

            // if just a normal settings page (under Settings menu)
            if ( ! $this->parent_slug ) {
                
                add_options_page(
                    $this->page_title,
                    $this->menu_title,
                    $this->capability,
                    $this->menu_slug,
                    [ $this, 'render_page' ]
                );
                
            // else, add this under the parent menu 
            } else {
                
                add_submenu_page(
                    $this->parent_slug,
                    $this->page_title,
                    $this->menu_title,
                    $this->capability,
                    $this->menu_slug,
                    [ $this, 'render_page' ]
                );                
                
            }
            

        }

        
        
        // -----------------------------------------
        // RENDER PAGE 
        // -----------------------------------------
            
        public function render_page() {
            
            $this->process_form_post();

            ?>
            <div class="so-admin-settings-page wrap">
                
                <h1><?php echo esc_html( $this->page_title ); ?></h1>
                
                <?php echo $this->get_messages(); ?>

                <form id="<?php echo $this->menu_slug . '_form'; ?>" method="post">

                    <?php wp_nonce_field( $this->nonce_action, $this->nonce_name ); ?>

                    <?php $this->render_content(); ?>
                    
                </form>

            </div>
            <?php

        }
        
        /**
         * child class overrides this to display content
         */
        abstract protected function render_content(): void;
        
        
        
        // -----------------------------------------
        // HANDLE FORM POST 
        // -----------------------------------------
        
        /**
         * in order to handle form posts, 
         *  - copy and paste process_form_post() to the child class (modify as needed)
         *  - for alerts or errors, use $this->add_message()
         */
        
        
        /**
         * handles the form posts  
         * child class overrides
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
                
                // check actions                
                if ( $this->is_action( 'my_action' ) ) {
                    
                    // do something here with $_POST input  
                    
                }
                
            }
            
        } // end : process_form_post();        

        
        /**
         * determines if nonce is valid 
         * 
         * @return bool 
         */
        protected function verify_nonce(): bool {
            
            if ( 
                isset($_POST[ $this->nonce_name ]) && 
                wp_verify_nonce($_POST[ $this->nonce_name ], $this->nonce_action ) 
            ) {
                return true;
            } else { 
                return false;
            }
            
        } // end : verify_nonce()
        
        
        /**
         * checks if 
         */
        protected function is_action( $action ) {
            return $_POST['action'] === $action;
        }
        
        
        /**
         * gets sanitized post value 
         */
        protected function get_form_post( $key ) {
            
            $value = '';
            
            if ( isset( $_POST[ $key ] ) && $_POST[ $key ] !== '' ) {
                $value = wp_unslash( $_POST[ $key ] );
            }
            
            return $value;
            
        }
        
        
        
        // -----------------------------------------
        // ADMIN MESSAGES 
        // -----------------------------------------

        /**
         * add message to the list of messages
         * 
         * @param {string} $message : the message to add 
         * @param {string} $type : the type of message (standard, warning, error)
         */
        public function add_message( $message, $type = 'standard' ) {

            if ( empty( $this->messages ) ) {
                $this->messages = [];
            }

            if ( $type !== '' && $type !== 'warning' && $type !== 'error' ) {
                $type = '';
            }

            $this->messages[] = [
                'message' => $message,
                'type' => $type
            ];

        } // end : add_message() 


        /**
         * get messages as HTML
         * 
         * @return {string} $html : a string of messages in HTML format 
         */
        public function get_messages() {

            $html = '';

            if ( ! empty( $this->messages ) ) {

                $html .= '<div class="so-admin-messages">';

                foreach( $this->messages as $message ) {

                    $type = '';
                    switch( $message['type'] ) {

                        case 'warning': 
                            $type = ' warning';
                            break;
                        case 'error': 
                            $type = ' error';
                            break;
                        default: 
                            $type = '';
                            break;

                    }

                    $html .= '<div class="so-admin-message' . $type . '">';
                        $html .= nl2br( $message['message'] );
                    $html .= '</div>';

                }

                $html .= '</div>';

            }

            return $html;

        } // end : get_messages() 
        
        

    } // end : class Admin_Settings_Page
endif;     