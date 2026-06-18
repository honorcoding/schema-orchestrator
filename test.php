<?php

add_filter(
    'schema_orchestrator_additional_nodes',
    'add_additional_nodes',
    10,
    2
); 
function add_additional_nodes( $nodes, $post_id ) {

        $nodes[] = [
            '@type' => 'Thing',
            '@id'   => home_url('/schema-test'),
            'name'  => 'Schema Orchestrator Test'
        ];

        return $nodes;
}

    
    
//add_action( 'wp_footer', 'test_in_footer' );
function test_in_footer() {
    echo '<pre>';
    print_r(
        get_post_meta(
            get_the_ID(),
            '_schema_orchestrator_overrides',
            true
        )
    );
    echo '</pre>';
}
