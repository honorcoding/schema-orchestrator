<?php
/**
 * Describes "which page are we building schema for, and who asked".
 *
 * The same small object is handed to every filter and registry callback,
 * no matter which SEO plugin is in use.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Context {

	/**
	 * Post ID of the current page, or 0 when the page is not a single
	 * post (archives, search, 404 ...).
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Which SEO plugin is in use: 'yoast' or 'rank-math'.
	 *
	 * @var string
	 */
	public $source = '';

	/**
	 * The SEO plugin's own context object, untouched, for advanced use.
	 *
	 * @var mixed
	 */
	public $raw = null;

	/**
	 * @param int    $id     Post ID, or 0.
	 * @param string $source Adapter slug.
	 * @param mixed  $raw    The SEO plugin's own context object.
	 */
	public function __construct( int $id, string $source, $raw = null ) {
		$this->id     = max( 0, $id );
		$this->source = $source;
		$this->raw    = $raw;
	}
}
