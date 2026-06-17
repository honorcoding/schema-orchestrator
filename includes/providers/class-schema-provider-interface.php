<?php
/**
 * Schema Provider Interface :
 * All schema providers must implement this.
 */

if (!defined('ABSPATH')) {
    exit;
}

interface Schema_Provider_Interface {

    /**
     * Return schema graph.
     */
    public function get_graph( int $post_id ): array;

    /**
     * Provider name.
     */
    public function get_name(): string;
    
}