<?php
/**
 * The "Schema Orchestrator" box on the post / page editor, where editors
 * type page-specific overrides.
 *
 * If the JSON is invalid, nothing is saved (the old overrides stay as they
 * were) and the box shows the error together with the text that was typed,
 * so nothing is lost.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Admin_Metabox {

	const NONCE_ACTION = 'schema_orchestrator_save';
	const NONCE_FIELD  = 'schema_orchestrator_nonce';
	const FIELD        = 'schema_orchestrator_overrides';

	public function hook(): void {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'maybe_show_error_notice' ) );
	}

	/**
	 * Add the box to every public post type.
	 */
	public function register(): void {

		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			add_meta_box(
				'schema_orchestrator',
				__( 'Schema Orchestrator', 'schema-orchestrator' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'default'
			);
		}
	}

	public function render( \WP_Post $post ): void {

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$failed_save = get_transient( self::error_key( $post->ID ) );
		$has_error   = is_array( $failed_save ) && isset( $failed_save['raw'], $failed_save['error'] );

		if ( $has_error ) {
			$text  = (string) $failed_save['raw'];
			$error = (string) $failed_save['error'];
			delete_transient( self::error_key( $post->ID ) );
		} else {
			$text  = Schema_Json_Input::to_pretty_json( Schema_Overrides::get( $post->ID ) );
			$error = '';
		}

		$adapter = Schema_Orchestrator::instance()->get_adapter();
		?>
		<p>
			<?php
			if ( $adapter ) {
				/* translators: %s: name of the SEO plugin, e.g. Yoast SEO */
				printf( esc_html__( 'Changes to this page\'s schema. Working with: %s.', 'schema-orchestrator' ), '<strong>' . esc_html( $adapter->get_label() ) . '</strong>' );
			} else {
				esc_html_e( 'No supported SEO plugin (Yoast SEO or Rank Math SEO) is active, so these changes will have no effect.', 'schema-orchestrator' );
			}
			?>
		</p>

		<?php if ( '' !== $error ) : ?>
			<div class="so-message is-error">
				<strong><?php esc_html_e( 'Not saved.', 'schema-orchestrator' ); ?></strong>
				<?php echo esc_html( $error ); ?>
				<?php esc_html_e( 'Fix it below and update the page again. The previous overrides are still in place.', 'schema-orchestrator' ); ?>
			</div>
		<?php endif; ?>

		<textarea
			name="<?php echo esc_attr( self::FIELD ); ?>"
			class="so-json-textarea"
			data-so-json="overrides"
			rows="14"
			spellcheck="false"
		><?php echo esc_textarea( $text ); ?></textarea>
		<p class="so-json-status" aria-live="polite"></p>

		<?php Schema_Admin_Help::render(); ?>
		<?php
	}

	/**
	 * @param int      $post_id Post being saved.
	 * @param \WP_Post $post    The post.
	 */
	public function save( $post_id, $post = null ): void {

		$post_id = (int) $post_id;

		if ( ! isset( $_POST[ self::NONCE_FIELD ], $_POST[ self::FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = wp_unslash( $_POST[ self::FIELD ] );

		if ( ! is_string( $raw ) ) {
			return;
		}

		$result = Schema_Json_Input::parse_overrides( $raw );

		if ( '' !== $result['error'] ) {
			set_transient(
				self::error_key( $post_id ),
				array(
					'raw'   => $raw,
					'error' => $result['error'],
				),
				5 * MINUTE_IN_SECONDS
			);
			Schema_Logger::log( 'Invalid JSON not saved on post ' . $post_id . ': ' . $result['error'] );
			return;
		}

		delete_transient( self::error_key( $post_id ) );
		Schema_Overrides::save( $post_id, $result['data'] );
	}

	/**
	 * Red banner at the top of the editor after a save that was rejected.
	 */
	public function maybe_show_error_notice(): void {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( false === get_transient( self::error_key( $post_id ) ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Schema Orchestrator: your schema changes were not saved because the JSON has a mistake. See the Schema Orchestrator box below the editor.', 'schema-orchestrator' )
			. '</p></div>';
	}

	private static function error_key( int $post_id ): string {
		return 'so_meta_error_' . $post_id . '_' . get_current_user_id();
	}
}
