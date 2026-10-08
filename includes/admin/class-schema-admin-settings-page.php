<?php
/**
 * Settings > Schema Orchestrator
 *
 * One JSON box for schema that applies to every page (usually the
 * Organization), plus a line saying which SEO plugin is being used.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Admin_Settings_Page {

	const SLUG         = 'schema-orchestrator';
	const NONCE_ACTION = 'schema_orchestrator_settings';
	const NONCE_FIELD  = 'schema_orchestrator_settings_nonce';

	/**
	 * Message shown at the top after a save: array( 'type' => 'success'|'error', 'text' => string ).
	 *
	 * @var array
	 */
	private $notice = array();

	/**
	 * Text typed by the person when a save was rejected, so it can be shown again.
	 *
	 * @var string|null
	 */
	private $rejected_text = null;

	public function hook(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	public function add_page(): void {

		$hook = add_options_page(
			__( 'Schema Orchestrator', 'schema-orchestrator' ),
			__( 'Schema Orchestrator', 'schema-orchestrator' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'handle_post' ) );
		}
	}

	/**
	 * Runs before the page is drawn. Saves the form when one was sent.
	 */
	public function handle_post(): void {

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'schema-orchestrator' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$raw = isset( $_POST['global_json'] ) ? wp_unslash( $_POST['global_json'] ) : '';

		if ( ! is_string( $raw ) ) {
			$raw = '';
		}

		$error = Schema_Settings::instance()->save_global_json( $raw );

		if ( '' !== $error ) {
			$this->rejected_text = $raw;
			$this->notice        = array(
				'type' => 'error',
				'text' => $error . ' ' . __( 'Nothing was saved.', 'schema-orchestrator' ),
			);
			return;
		}

		$this->notice = array(
			'type' => 'success',
			'text' => __( 'Settings saved.', 'schema-orchestrator' ),
		);
	}

	public function render(): void {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Schema_Settings::instance();
		$text     = null !== $this->rejected_text ? $this->rejected_text : $settings->get_global_json();
		$adapter  = Schema_Orchestrator::instance()->get_adapter();
		?>
		<div class="wrap so-settings">
			<h1><?php esc_html_e( 'Schema Orchestrator', 'schema-orchestrator' ); ?></h1>

			<?php if ( ! empty( $this->notice ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $this->notice['type'] ); ?> inline">
					<p><?php echo esc_html( $this->notice['text'] ); ?></p>
				</div>
			<?php endif; ?>

			<p>
				<strong><?php esc_html_e( 'SEO plugin in use:', 'schema-orchestrator' ); ?></strong>
				<?php
				echo $adapter
					? esc_html( $adapter->get_label() )
					: esc_html__( 'none found (install and activate Yoast SEO or Rank Math SEO)', 'schema-orchestrator' );
				?>
			</p>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<h2><?php esc_html_e( 'Sitewide schema', 'schema-orchestrator' ); ?></h2>
				<p>
					<?php esc_html_e( 'Applied to every page. Use it for details that never change, such as your organization. A page\'s own Schema Orchestrator box is applied afterwards and wins if the two disagree.', 'schema-orchestrator' ); ?>
				</p>

				<textarea
					name="global_json"
					class="so-json-textarea"
					data-so-json="overrides"
					rows="22"
					spellcheck="false"
				><?php echo esc_textarea( $text ); ?></textarea>
				<p class="so-json-status" aria-live="polite"></p>

				<?php Schema_Admin_Help::render(); ?>

				<?php submit_button( __( 'Save', 'schema-orchestrator' ) ); ?>
			</form>
		</div>
		<?php
	}
}
