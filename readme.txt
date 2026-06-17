1. HOW THE SCHEMA-ORCHESTRATOR WORKS: 

// ANY PLUGIN CAN REGISTER THE NODE 
add_action(
    'schema_orchestrator_register',
    function () {

        Schema_Registry::register_node(
            'faq',
            function ($post_id) {

                return [
                    [
                        '@type' => 'FAQPage',
                        '@id'   => get_permalink($post_id) . '#faq'
                    ]
                ];
            }
        );
    }
);


// THEN LATER, OUTPUT THE RESULTS 
add_action( 
    'wp_footer', 
    function () {
        $graph = Schema_Orchestrator::get_graph(
            get_the_ID()
        );

        echo '<pre>';
        print_r($graph);
        echo '</pre>';
    }
);


// THE OUTPUT: 
Array
(
    [0] => Array
        (
            [@type] => FAQPage
            [@id] => https://example.com/post/#faq
        )
)



