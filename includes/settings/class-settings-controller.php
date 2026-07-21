<?php
// =========================================
// Settings (class) : class-settings.php 
// ----------------------------------------
// Purpose: 
//     base class for plugin settings controller
//     
// Use: 
//     1. child class overrides __construct() and defaults() 
//     
//        public function __construct() {
//            $option_key = 'so-woocommerce-settings';
//            parent::__construct( $option_key );
//        }
//        
//        public function defaults() {
//            $defaults = [ 'woocommerce_settings', 'yes' ];
//            return $defaults;
//        }
//        
//     2. clear options after changing defaults 
//     
//        // use once after changing defaults 
//        $settings_child->reset_to_defaults();
//
// =========================================

namespace Schema_Orchestrator\Settings; 


defined( 'ABSPATH' ) || exit;   // no access for random strangers


if ( ! class_exists( 'Settings_Controller') ) :    

    class Settings_Controller {
    
    
        // -------------------------------------------
        // PROPERTIES
        // -------------------------------------------
    
        /**
         * one instance that can be used over and over 
         */
        protected static $instances = [];
        
        
        /**
         * the wordpress option key 
         */
        protected $option_key = null;
        
        
        /**
         * the options as an array 
         */
        protected $options = [];
        

        
        // -------------------------------------------
        // INSTANTIATION 
        // -------------------------------------------
    
        /**
         * Return an instance of this class 
         * 
         * Note: grabbing the instance prevents the need for a global 
         *       variable to reload multiple times on every page
         */
        public static function instance() {

            $class = static::class;

            if ( ! isset( self::$instances[ $class ] ) ) {
                self::$instances[ $class ] = new static();
            }

            return self::$instances[ $class ];
        
        }
        
        
        /**
         * parent: loads options into memory 
         * child: overrides to provide option key 
         */
        public function __construct( $option_key ) {
            
            if ( $this->is_string( $option_key ) ) {
                $this->option_key = trim( $option_key ) ?? null; 
            }
            
            // load options from wordpress 
            $this->load();
            
            // if no options available 
            if ( ! is_array( $this->options ) || empty( $this->options ) ) {
                
                // load defaults 
                $this->options = $this->defaults();

                // save for future reference 
                $this->save();
                
            }
            
        } // end : __construct()
        
        
        /**
         * loads default options into memory
         * (overridden by child class)
         */
        protected function defaults() {
            return [];
        }
        
        
        /**
         * forces a reset to defaults 
         * (useful for testing after making adjustments to defaults)
         */
        public function reset_to_defaults() {
            
            // load defaults 
            $this->options = $this->defaults();

            // save for future reference 
            $this->save();
            
        }
        

        
        // -------------------------------------------
        // LOAD AND SAVE TO WORDPRESS OPTIONS
        // -------------------------------------------
        
        /**
         * loads options from wordpress
         */
        protected function load() {
            
            if ( $this->option_key ) {
                $this->options = get_option( $this->option_key, [] );
            }

        } // end : load()
        
    
        /**
         * saves options to wordpress 
         * 
         * @return boolean 
         */
        protected function save() {
            
            $success = false;
            
            if ( $this->option_key && is_array( $this->options ) ) {
                $success = update_option( $this->option_key, $this->options );
            }
            
            return $success; 
            
        } // end : save()
        
        
        /**
         * clears options and starts fresh 
         * (use after changing defaults)
         */
        public function reset() {
            
            $this->options = [];
            $this->save();

        }
        

        protected function is_string( $value ) {
            
            if ( 
                isset( $value ) &&         
                is_string( $value ) && 
                trim( $value ) !== '' 
            ) {
                return true;
            } else { 
                return false;
            }    
            
        } // end : is_string() 
        
        
        
        // -------------------------------------------
        // GETTERs AND SETTERs
        // -------------------------------------------
        
        /**
         * gets all properties
         * 
         * @return {array}
         */
        public function all() {
            return $this->options;
        }
        
    
        /**
         * gets the property 
         * 
         * @param string $key
         * @return {varies}
         */
        public function get( $key ) {
            
            $value = null;
            
            if ( is_array( $this->options ) && isset( $this->options[ $key ] ) ) {
                $value = $this->options[ $key ];
            } 
            
            return $value;
            
        } // end : get()
        
        
        /**
         * sets the property (and saves to wordpress options) 
         * 
         * @param type $key
         * @param type $value
         */
        public function set( $key, $value ) {
            
            if ( is_array( $this->options ) ) {
                $this->options[ $key ] = $value;
            }
            
            $this->save();
            
        } // end : set() 
        
    
        
        // -------------------------------------------
        // ADVANCED GETTERs AND SETTERs
        // -------------------------------------------    

        /**
         * gets a URL 
         * 
         * the property must be saved as follows:
         *   ['key'] => [ 'url' => '/relative-path-url/' ] 
         * 
         * @param {string} $key
         * @return {url}
         */
        public function get_url( $key ) {
            
            $url = '';
            
            $property = $this->get( $key );
            if ( $property && isset( $property['url'] ) ) {
                $url =  $property['url'];
            }
            
            return $url;

        } // end : get_url() 
        
        
        /**
         * gets a button (text and url) 
         * 
         * the property must be saved as follows:
         *   ['key'] => [ 'text' => 'Button Text', 'url' => '/relative-path-url/' ] 
         * 
         * @param {string} $key
         * @return {url}
         */
        public function get_button( $key ) {
            
            $button = null;
            
            $property = $this->get( $key );
            if ( $property && isset( $property['url'] ) && isset( $property['text'] ) ) {
                $button = $property;
            }
            
            return $button;

        } // end : get_button()
        
        

    } // end : class Settings_Controller
    
endif;


