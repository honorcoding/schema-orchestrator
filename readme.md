# Schema Orchestrator

## Purpose 

Schema Orchestrator coordinates all schema components on the site, ensuring that individual schema types work together as a unified graph. It determines which schema nodes should be included, how they are connected, and what is ultimately output on the page.

Think of the Schema Orchestrator as the traffic controller for your site's structured data. It makes sure all schema pieces are connected correctly, prevents conflicts, and delivers a clean, organized schema graph to Google.


## Register a Node While Editing a Post 

### ... still developing ... add instructions here once complete ... 


## How Plugins Can Register Nodes 

### Any plugin can register a node 

```
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
```

### Then later, output the results 

```
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
```

### The output

```
Array
(
    [0] => Array
        (
            [@type] => FAQPage
            [@id] => https://example.com/post/#faq
        )
)
```


