<?php
/**
 * Reads, checks and cleans the JSON that people type into the plugin
 * (the page meta box and the Settings page).
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Json_Input {

	const MAX_DEPTH = 32;

	/**
	 * Turn text typed by a person into an overrides array.
	 *
	 * Returns array( 'data' => array, 'error' => string ).
	 * 'error' is an empty string when everything is fine.
	 *
	 * @param string $raw Text from a textarea.
	 */
	public static function parse_overrides( string $raw ): array {

		$raw = trim( $raw );

		// An empty box means "no overrides".
		if ( '' === $raw ) {
			return self::result( array() );
		}

		$decoded = json_decode( $raw, true, self::MAX_DEPTH );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return self::failure(
				sprintf(
					/* translators: %s: technical JSON error message */
					__( 'That is not valid JSON (%s). Check for missing commas, quotes or brackets.', 'schema-orchestrator' ),
					json_last_error_msg()
				)
			);
		}

		if ( ! is_array( $decoded ) ) {
			return self::failure(
				__( 'The top level must be a JSON object, like { "WebSite": { "name": "My site" } }.', 'schema-orchestrator' )
			);
		}

		// {} and [] both mean "nothing".
		if ( array() === $decoded ) {
			return self::result( array() );
		}

		if ( Schema_Graph::is_list( $decoded ) ) {
			return self::failure(
				__( 'The top level must be a JSON object with keys, like { "WebSite": { ... } }, not a plain list.', 'schema-orchestrator' )
			);
		}

		foreach ( $decoded as $key => $value ) {
			if ( ! is_array( $value ) ) {
				return self::failure(
					sprintf(
						/* translators: %s: the key the person typed */
						__( 'The value for "%s" must be an object (or a list, for __append and __remove).', 'schema-orchestrator' ),
						(string) $key
					)
				);
			}
		}

		return self::result( self::clean_strings( $decoded ) );
	}

	/**
	 * Remove HTML from every text value, so typed-in schema can never
	 * break out of the <script> tag it is printed in.
	 *
	 * Safe to run more than once on the same data.
	 *
	 * @param mixed $value Anything. Arrays are cleaned recursively.
	 * @return mixed
	 */
	public static function clean_strings( $value ) {

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::clean_strings( $item );
			}
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = wp_strip_all_tags( $value, false );
			return str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $value );
		}

		return $value;
	}

	/**
	 * Overrides array -> nicely indented JSON for a textarea.
	 * Empty overrides give an empty string (not "[]").
	 */
	public static function to_pretty_json( array $overrides ): string {

		if ( array() === $overrides ) {
			return '';
		}

		$json = wp_json_encode( $overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return is_string( $json ) ? $json : '';
	}

	private static function result( array $data ): array {
		return array(
			'data'  => $data,
			'error' => '',
		);
	}

	private static function failure( string $message ): array {
		return array(
			'data'  => array(),
			'error' => $message,
		);
	}
}
