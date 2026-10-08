<?php
/**
 * Tiny logger.
 *
 * Writes to WordPress's normal debug log, and only when both WP_DEBUG and
 * WP_DEBUG_LOG are on. On a normal production site this does nothing.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Logger {

	public static function log( string $message ): void {

		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
			return;
		}

		error_log( '[Schema Orchestrator] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
