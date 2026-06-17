<?php
/**
 * Schema Orchestrator
 * Core engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Schema_Orchestrator {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Registered providers.
     *
     * @var Schema_Provider_Interface[]
     */
    private $providers = [];

    /**
     * Provider statistics.
     *
     * @var array
     */
    private $provider_stats = [];
    
    /**
     * The last graph Yoast provided
     * 
     * @var array
     */
    private $last_graph = [];

    /**
     * Singleton.
     */
    public static function instance(): self {

        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {

        add_action(
            'init',
            [$this, 'register_components']
        );
        
        add_filter(
            'wpseo_schema_graph',
            [$this, 'filter_yoast_graph'],
            999,
            2
        );       
        
    }

    /**
     * Register providers and schema nodes.
     */
    public function register_components(): void {

        /**
         * Allow third-party plugins
         * to register schema nodes/providers.
         */
        do_action(
            'schema_orchestrator_register'
        );
    }

    /**
     * Register a provider.
     */
    public function register_provider(
        Schema_Provider_Interface $provider
    ): void {

        $this->providers[
            $provider->get_name()
        ] = $provider;
    }

    /**
     * Get providers.
     */
    public function get_providers(): array {

        return $this->providers;
    }

    /**
     * Get provider statistics.
     */
    public function get_provider_stats(): array {

        return $this->provider_stats;
    }
    
    /**
     * Get individual provider.
     */
    public function get_provider(
        string $name
    ): ?Schema_Provider_Interface {

        return $this->providers[$name] ?? null;
    }

    /**
     * Public API.
     */
    public static function get_graph(
        int $post_id
    ): array {

        return self::instance()
            ->build_graph($post_id);
    }

    /**
     * Build final graph.
     */
    public function build_graph(
        int $post_id
    ): array {

        /*
         * Reset stats every run.
         */
        $this->provider_stats = [];

        $graph = [];

        $context = (object) [
            'id' => $post_id,
        ];

        /*
         * Step 1
         * Collect provider graphs.
         */
        foreach ($this->providers as $provider) {

            try {

                $provider_graph =
                    $provider->get_graph(
                        $post_id
                    );

                if (is_array($provider_graph)) {

                    /*
                     * Store provider stats.
                     */
                    $this->provider_stats[
                        $provider->get_name()
                    ] = count(
                        $provider_graph
                    );

                    $graph = array_merge(
                        $graph,
                        $provider_graph
                    );
                }

            } catch (\Throwable $e) {

                $this->provider_stats[
                    $provider->get_name()
                ] = 'ERROR';

                debugger->log(
                    sprintf(
                        '[Schema Orchestrator] Provider "%s" failed: %s',
                        $provider->get_name(),
                        $e->getMessage()
                    )
                );
            }
        }

        /*
         * Step 2
         * Pre-merge filter.
         */
        $graph = apply_filters(
            'schema_orchestrator_pre_merge_graph',
            $graph,
            $post_id,
            $context
        );

        /*
         * Step 3
         * Registered nodes.
         */
        $graph = array_merge(
            $graph,
            Schema_Registry::build_nodes(
                $post_id,
                $context
            )
        );

        /*
         * Step 4
         * Additional nodes filter.
         */
        $extra_nodes = apply_filters(
            'schema_orchestrator_additional_nodes',
            [],
            $post_id,
            $context
        );

        if (is_array($extra_nodes)) {

            $graph = array_merge(
                $graph,
                $extra_nodes
            );
        }

        /*
         * Step 5
         * Apply overrides.
         */
        $overrides =
            Schema_Overrides::get(
                $post_id
            );

        if (!empty($overrides)) {

            $graph =
                Schema_Overrides::apply(
                    $graph,
                    $overrides
                );
        }

        /*
         * Step 6
         * Final filter.
         */
        $graph = apply_filters(
            'schema_orchestrator_final_graph',
            $graph,
            $post_id,
            $context
        );

        return $graph;
    }

    public function filter_yoast_graph(
        $graph,
        $context
    ) {
        $post_id = 0;

        if (
            is_object($context)
            && isset($context->id)
        ) {
            $post_id = (int) $context->id;
        }

        /*
         * Pre-merge hook.
         */
        $graph = apply_filters(
            'schema_orchestrator_pre_merge_graph',
            $graph,
            $post_id,
            $context
        );

        /*
         * Registry nodes.
         */
        $graph = array_merge(
            $graph,
            Schema_Registry::build_nodes(
                $post_id,
                $context
            )
        );

        /*
         * Additional nodes.
         */
        $extra_nodes = apply_filters(
            'schema_orchestrator_additional_nodes',
            [],
            $post_id,
            $context
        );

        if (is_array($extra_nodes)) {

            $graph = array_merge(
                $graph,
                $extra_nodes
            );
        }

        /*
         * Overrides.
         */
        $overrides =
            Schema_Overrides::get(
                $post_id
            );

        if (!empty($overrides)) {

            $graph =
                Schema_Overrides::apply(
                    $graph,
                    $overrides
                );
        }

        /*
         * Final hook.
         */
        $graph = apply_filters(
            'schema_orchestrator_final_graph',
            $graph,
            $post_id,
            $context
        );
        
        $this->last_graph = $graph;

        return $graph;
    }
    
    /**
     * gets the last graph 
     * 
     * @return array
     */
    public function get_last_graph(): array {

        return $this->last_graph;
    }    

    
    /**
     * Debug helper.
     */
    public static function debug(): array {

        $instance = self::instance();

        return [
            'providers' =>
                $instance->get_provider_stats(),
            'total_nodes' =>
                count(
                    $instance->get_last_graph()
                ),
        ];
    }    
    
}