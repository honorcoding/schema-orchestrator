<?php
/**
 * The "Examples" cheat sheet shown under both JSON boxes.
 */

namespace Schema_Orchestrator;

defined( 'ABSPATH' ) || exit;

final class Schema_Admin_Help {

	public static function render(): void {
		?>
		<details class="so-help">
			<summary><?php esc_html_e( 'Examples (click to open)', 'schema-orchestrator' ); ?></summary>
			<?php foreach ( self::examples() as $example ) : ?>
				<p><strong><?php echo esc_html( $example['title'] ); ?></strong></p>
				<pre><?php echo esc_html( $example['json'] ); ?></pre>
			<?php endforeach; ?>
			<p class="description">
				<?php esc_html_e( 'You can combine several of these in one box. Lists (like sameAs) are replaced as a whole, not mixed with the old list.', 'schema-orchestrator' ); ?>
			</p>
		</details>
		<?php
	}

	/**
	 * @return array<int, array{title: string, json: string}>
	 */
	private static function examples(): array {

		return array(
			array(
				'title' => __( 'Change a value on every node of one type', 'schema-orchestrator' ),
				'json'  => self::json( array( 'WebSite' => array( 'name' => 'My Site' ) ) ),
			),
			array(
				'title' => __( 'Change (or create) one specific node by its @id', 'schema-orchestrator' ),
				'json'  => self::json(
					array(
						'https://example.com/#organization' => array(
							'name'   => 'Example Organization',
							'sameAs' => array( 'https://www.facebook.com/example' ),
						),
					)
				),
			),
			array(
				'title' => __( 'Add a new node', 'schema-orchestrator' ),
				'json'  => self::json(
					array(
						'__append' => array(
							array(
								'@type' => 'Event',
								'@id'   => 'https://example.com/#event',
								'name'  => 'Annual Conference',
							),
						),
					)
				),
			),
			array(
				'title' => __( 'Remove one property (set it to null)', 'schema-orchestrator' ),
				'json'  => self::json( array( 'WebPage' => array( 'description' => null ) ) ),
			),
			array(
				'title' => __( 'Remove whole nodes, by type or by @id', 'schema-orchestrator' ),
				'json'  => self::json( array( '__remove' => array( 'BreadcrumbList' ) ) ),
			),
		);
	}

	private static function json( array $data ): string {

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		return is_string( $json ) ? $json : '';
	}
}
