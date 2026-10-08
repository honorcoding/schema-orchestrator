<?php
/**
 * Sitewide settings: one block of override JSON that applies to every page
 * (typically the Organization details).
 *
 * It uses the exact same format as the per-page meta box, and is applied
 * through the "schema_orchestrator_global_overrides" filter.
 *
 * Upgrading from 1.x: the old option "so-schema-settings" is still read until
 * the first time the new Settings page is saved.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Settings {

	const OPTION        = 'schema_orchestrator_settings';
	const LEGACY_OPTION = 'so-schema-settings';

	/**
	 * @var Schema_Settings|null
	 */
	private static $instance = null;

	/**
	 * Parsed sitewide overrides, cached for the request.
	 *
	 * @var array|null
	 */
	private $parsed = null;

	public static function instance(): self {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function hook(): void {
		add_filter( 'schema_orchestrator_global_overrides', array( $this, 'add_global_overrides' ), 10, 1 );
	}

	/**
	 * The JSON text exactly as it should appear in the textarea.
	 */
	public function get_global_json(): string {

		$options = get_option( self::OPTION, null );

		if ( is_array( $options ) ) {
			return isset( $options['global_json'] ) ? (string) $options['global_json'] : '';
		}

		// Nothing saved with the new settings yet: fall back to version 1.x data.
		$legacy = get_option( self::LEGACY_OPTION, array() );

		if ( is_array( $legacy ) && isset( $legacy['organization'] ) && is_string( $legacy['organization'] ) ) {
			return $legacy['organization'];
		}

		return '';
	}

	/**
	 * Check and save new sitewide JSON.
	 *
	 * @return string An error message, or an empty string when saved.
	 */
	public function save_global_json( string $raw ): string {

		$result = Schema_Json_Input::parse_overrides( $raw );

		if ( '' !== $result['error'] ) {
			return $result['error'];
		}

		update_option(
			self::OPTION,
			array( 'global_json' => Schema_Json_Input::to_pretty_json( $result['data'] ) )
		);

		$this->parsed = null;

		return '';
	}

	/**
	 * The sitewide overrides as an array. Bad JSON gives an empty array
	 * (and a line in the debug log) instead of a broken page.
	 */
	public function get_global_overrides(): array {

		if ( null !== $this->parsed ) {
			return $this->parsed;
		}

		$result = Schema_Json_Input::parse_overrides( $this->get_global_json() );

		if ( '' !== $result['error'] ) {
			Schema_Logger::log( 'Sitewide JSON ignored: ' . $result['error'] );
		}

		$this->parsed = $result['data'];

		return $this->parsed;
	}

	/**
	 * Filter callback: add the sitewide overrides to whatever other code supplied.
	 *
	 * @param mixed $overrides Overrides collected so far.
	 */
	public function add_global_overrides( $overrides ) {

		$mine = $this->get_global_overrides();

		if ( array() === $mine ) {
			return $overrides;
		}

		if ( ! is_array( $overrides ) || array() === $overrides ) {
			return $mine;
		}

		return Schema_Overrides::combine( $overrides, $mine );
	}
}
