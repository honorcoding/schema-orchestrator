<?php
// ------------------------------------------
// DEBUGGER - class.debugger.php 
// 
// Purpose: 
//      Provides tools for debugging 
// ------------------------------------------

namespace SCHEMA_ORCHESTRATOR;


defined( 'ABSPATH' ) || exit;   // no access for random strangers


if ( ! class_exists( 'Debugger') ) :

    class Debugger {



            // -------------------------------------------------
            // PROPERTIES
            // -------------------------------------------------
    
            /**
             * one instance that can be used over and over 
             */
            protected static $_instance = null;

            /**
             * logger path : the path for the file to log to 
             */
            protected $log_path; 



            // -------------------------------------------------
            // INSTANTIATION 
            // -------------------------------------------------

            /**
             * Return an instance of this class 
             * 
             * Note: grabbing the instance prevents the need for a global 
             *       variable to reload multiple times on every page
             */
            public static function instance() {

                // If the single instance hasn't been set, set it now.
                if ( is_null( self::$_instance ) ) {
                    self::$_instance = new self();
                }

                return self::$_instance;

            }


            /**
             * Basic consructor 
             */
            public function __construct() {}



            // -------------------------------------------------
            // VARIABLE OUTPUT
            // -------------------------------------------------

            /**
             * dumps the contents of a variable to a string (for output)
             * 
             * @param {any} $var : the variable to be dumped
             * @param {booelan} $html : a flag that determines if the output is html
             * @return {string} : the contents of the variable in printable format
             */
            public function dump( $var, $is_html = true ) {  

                ob_start();
                    print_r( $var ); 
                $output = ob_get_clean();

                if ( $is_html ) {
                    $output = '<pre>' . $output . '</pre>';
                }

                return $output;        

            } // end : dump()      



            // -------------------------------------------------
            // LOGGING TO A FILE
            // -------------------------------------------------

            /**
             * determines the path for the log file
             * 
             * note: first checks if path is already set 
             * 
             * @param {string} $path : the path for the log file
             */
            public function set_log_path( $path ) {

                if ( 
                    ! $this->log_path || 
                    $this->log_path !== $path 
                ) {
                    $this->log_path = $path;
                }
                
            } // end : set_path()


            /**
             * logs a message to the log file 
             * 
             * @param {string} $message : the event to log 
             * @param {string} $log_path : the file to log to 
             */
            public function log( $message, $log_path = '' ) {
                
                // determine the log path 
                if ( $log_path === '' ) {
                    $log_path = $this->log_path;
                }

                // remember when this event occurred 
                $time = '[' . date('Y-m-d H:i:s') . '] ';

                // log the event
                error_log( $time . $message . "\n", 3, $log_path );    

            }


            /**
             * dumps a variable to the debug.log
             * 
             * @param {any} $var : the variable to be dumped to the log 
             * @param {string} $message : a message to prepend to the data dump
             */
            function log_var( $var, $message = '' ) {

                $this->log( $message . hc_dump( $var, false ) );

            }    



    } // end : class Debugger 

endif; 


