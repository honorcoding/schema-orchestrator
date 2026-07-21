<?php
// ========================================
// Utilities - class-utilities.php 
// 
// Basic Utilities 
// ========================================

namespace Schema_Orchestrator; 

defined( 'ABSPATH' ) || exit;   // no access for random strangers


if ( ! class_exists( 'Utilities' ) ) :
    class Utilities {

        /**
         * checks if value is a valid string 
         * 
         * for strings that are potentially not set, 
         * use: Utilities::is_valid_string( $string ?? null ) 
         * 
         * @param {varies}  $value         : 
         * @param {boolean} $include_empty : false returns "invalid" if empty string
         * @return bool
         */
        public static function is_string( $value, $include_empty = false ) {
            
            $is_valid = is_string( $value ) &&
                        ( $include_empty || trim( $value ) !== '' );
            
            return $is_valid; 

        } // end : is_valid_string() 
        
        
    } // end : class Utilities
endif; 