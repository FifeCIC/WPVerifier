<?php
/**
 * WP Verifier Namer Page Tabs
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Handles tabs for the Plugin Namer page.
 */
class Namer_Page_Tabs {

	/**
	 * Get tabs for the namer page
	 */
	public static function get_tabs() {
		return array(
			'evaluate'  => __( 'Evaluate Name', 'wp-verifier' ),
			'analytics' => __( 'Analytics', 'wp-verifier' ) . ' <span class="coming-soon-badge">Coming Soon</span>',
			'saved'     => __( 'Saved Names', 'wp-verifier' ) . ' <span class="coming-soon-badge">Coming Soon</span>',
		);
	}

	/**
	 * Display tabs navigation
	 */
	public static function render_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'evaluate';
		$tabs        = self::get_tabs();
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_id => $tab_title ) {
				$active_class = ( $current_tab === $tab_id ) ? 'nav-tab-active' : '';
				$url          = add_query_arg( array( 'page' => 'plugin-check-namer', 'tab' => $tab_id ), admin_url( 'tools.php' ) );
				printf(
					'<a href="%s" class="nav-tab %s">%s</a>',
					esc_url( $url ),
					esc_attr( $active_class ),
					wp_kses_post( $tab_title )
				);
			}
			?>
		</h2>
		<?php
	}

	/**
	 * Render coming soon message
	 */
	public static function render_coming_soon() {
		?>
		<div class="notice notice-info inline">
			<p><strong><?php esc_html_e( 'Coming Soon', 'wp-verifier' ); ?></strong></p>
			<p><?php esc_html_e( 'This feature is currently under development and will be available in a future release.', 'wp-verifier' ); ?></p>
		</div>
		<?php
	}
}
