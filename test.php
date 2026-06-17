<?php

add_filter(
    'schema_orchestrator_additional_nodes',
    function (
        $nodes,
        $post_id
    ) {

        $nodes[] = [
            '@type' => 'Thing',
            '@id'   => home_url('/schema-test'),
            'name'  => 'Schema Orchestrator Test'
        ];

        return $nodes;
    },
    10,
    2
); 
    
    
add_action( 'wp_footer', function() {
    echo '<pre>';
    print_r(
        get_post_meta(
            get_the_ID(),
            '_schema_orchestrator_overrides',
            true
        )
    );
    echo '</pre>';
} );
