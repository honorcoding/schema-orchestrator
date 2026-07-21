<?php
// =========================================
// Schema_Settings (class) : class-schema-settings.php 
// ----------------------------------------
// Purpose: 
//     handles schema settings for custom features
// =========================================

namespace Schema_Orchestrator\Settings; 
use Schema_Orchestrator\Settings\Settings_Controller;


defined( 'ABSPATH' ) || exit;   // no access for random strangers


if ( ! class_exists( 'Schema_Settings') ) :

    class Schema_Settings extends Settings_Controller {
        
        public function __construct() {
            parent::__construct( 'so-schema-settings' );
            
            // add these settings to the schema 
            add_filter( 'schema_orchestrator_global_overrides', [ $this, 'add_to_schema' ], 10, 3 );
        }
        
        protected function defaults() {
            
            $defaults = [
                
                'organization' => 
                
                    // organization schema json-ld
                    <<<END
                    {
                        "https://asdn.org/#organization": {
                            "@type": [
                                "Organization",
                                "EducationalOrganization"
                            ],
                            "alternateName": "ASDN",
                            "description": "Alaska\u2019s trusted source for high quality professional learning for educators for over 40 years.",
                            "foundingDate": "1983",
                            "parentOrganization": {
                                "@type": "Organization",
                                "name": "Alaska Council of School Administrators",
                                "url": "https://alaskaacsa.org/"
                            },
                            "address": {
                                "@type": "PostalAddress",
                                "streetAddress": "2204 Douglas Highway, Suite 100",
                                "addressLocality": "Douglas",
                                "addressRegion": "AK",
                                "postalCode": "99824",
                                "addressCountry": "US"
                            },
                            "contactPoint": {
                                "@type": "ContactPoint",
                                "contactType": "customer service",
                                "email": "asdn@alaskaacsa.org",
                                "telephone": "+1-907-364-3809",
                                "areaServed": "US-AK"
                            },
                            "sameAs": [
                                "https://www.facebook.com/AlaskaStaffDevelopmentNetwork",
                                "https://www.linkedin.com/company/alaska-staff-development-network"
                            ],
                            "knowsAbout": [
                                "Professional learning for educators",
                                "Teacher professional development",
                                "Educational leadership",
                                "School improvement",
                                "Instructional coaching",
                                "Curriculum development",
                                "Education conferences",
                                "K-12 education",
                                "Alaska education"
                            ]
                        }
                    }
                    END,                
                
            ];
            
            return $defaults;

        } // end : defaults 
        
        
        public function add_to_schema( $overrides, $post_id, $context ) {
            
            // get organization nodes
            $organization_nodes = null;
            if ( isset( $this->options ) && isset( $this->options['organization'] ) ) {
                $organization_nodes = json_decode( $this->options['organization'], true );                
            }
            
            if ( ! $organization_nodes ) {
                $organization_nodes = [];
            } 

            // add organization to global schema overrides
            if ( is_array( $overrides ) && is_array( $organization_nodes ) ) {
                $overrides = array_replace_recursive( $overrides, $organization_nodes );
            }
            
            return $overrides;

        } // end : add_to_schema()
        
        
    } // end : class Schema_Settings
    
endif;