<?php
/**
 * Schema Admin
 */

namespace Schema_Orchestrator\Admin;
use Schema_Orchestrator\Schema_Overrides;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Single_Page {

    public function __construct() {

        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_post' ] );
        //add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_schema_orchestrator_preview', [ $this, 'ajax_preview' ] );
    }

    /**
     * Register metabox.
     */
    public function register_meta_box(): void {

        $post_types = get_post_types( [ 'public' => true ] );

        foreach ( $post_types as $post_type ) {

            add_meta_box(
                'schema_orchestrator',
                __( 'Schema Orchestrator', 'schema-orchestrator' ),
                [ $this, 'render_meta_box' ],
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Enqueue admin JS.
     */
    public function enqueue_assets(): void {

        wp_enqueue_script(
            'schema-orchestrator-admin',
            SCHEMA_ORCHESTRATOR_URL . 'assets/admin.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'schema-orchestrator-admin',
            'SchemaOrchestrator',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'schema_orchestrator_preview' ),
            ]
        );
    }

    /**
     * Render metabox.
     */
    public function render_meta_box( \WP_Post $post ): void {

        wp_nonce_field( 'schema_orchestrator_save', 'schema_orchestrator_nonce' );

        $overrides = Schema_Overrides::get( $post->ID );

        /*
        ?>
        <p>
            <button type="button" class="button" id="schema-orchestrator-preview">
                Preview Schema
            </button>
        </p>

        <div id="schema-orchestrator-debug">

            <strong>Debug</strong>

            <pre>
                <?php

                $debug = Schema_Orchestrator::debug();

                unset( $debug['graph'] );

                print_r( $debug );

                ?>
            </pre>

        </div>
        */
        ?>
        
        <p>
            <strong>JSON-LD Overrides</strong>
        </p>

        <textarea
            name="schema_orchestrator_overrides"
            style="width:100%;height:300px;font-family:monospace;"
        ><?php

        echo esc_textarea(
            wp_json_encode(
                $overrides,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        ?></textarea>

        <?php /*
        <p>
            <strong>Preview Output</strong>
        </p>

        <textarea
            id="schema-orchestrator-preview-output"
            readonly
            style="width:100%;height:300px;font-family:monospace;"
        ></textarea>

        <?php
         * 
         */
    }

    /**
     * Save overrides.
     */
    public function save_post( int $post_id ): void {

        if ( ! isset( $_POST['schema_orchestrator_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['schema_orchestrator_nonce'], 'schema_orchestrator_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $json = wp_unslash( $_POST['schema_orchestrator_overrides'] ?? '' );

        if ( trim( $json ) === '' ) {

            delete_post_meta( $post_id, Schema_Overrides::META_KEY );

            return;
        }

        $decoded = json_decode( $json, true );

        if ( json_last_error() === JSON_ERROR_NONE ) {

            Schema_Overrides::save( $post_id, $decoded );

        } else {

            so_debug()->log( '[Schema Orchestrator] Invalid JSON on post ' . $post_id );

            return;
        }
    }

    /**
     * AJAX Preview.
     */
    public function ajax_preview(): void {

        check_ajax_referer( 'schema_orchestrator_preview', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );

        if ( ! $post_id ) {

            wp_send_json_error( [ 'message' => 'Invalid post' ] );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {

            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }

        $graph = Schema_Orchestrator::get_graph( $post_id );

        wp_send_json_success(
            [
                'debug' => Schema_Orchestrator::debug(),
                'graph' => $graph,
            ]
        );
    }
}