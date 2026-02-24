<?php
/**
 * WP Verifier Admin Page Tabs
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Handles tabs for the main Verify Plugins page.
 */
class Admin_Page_Tabs {

	/**
	 * Get tabs for the main admin page
	 */
	public static function get_tabs() {
		return array(
			'preparation' => __( 'Preparation', 'wp-verifier' ),
			'basic'       => __( 'Basic Verification', 'wp-verifier' ),
			'verify'      => __( 'Advanced Verification', 'wp-verifier' ),
			'results'     => __( 'Results', 'wp-verifier' ),
			'monitoring'  => __( 'Plugin Monitoring', 'wp-verifier' ),
			'explore'     => __( 'Explore Plugin', 'wp-verifier' ),
			'ast'         => __( 'AST Demo', 'wp-verifier' ),
			'namer'       => __( 'Plugin Namer', 'wp-verifier' ),
			'settings'    => __( 'Settings', 'wp-verifier' ),
			'assets'      => __( 'Assets', 'wp-verifier' ),
			'ignores'     => __( 'Ignore Rules', 'wp-verifier' ),
		);
	}

	/**
	 * Display tabs navigation
	 */
	public static function render_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'basic';
		$tabs        = self::get_tabs();
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_id => $tab_title ) {
				$active_class = ( $current_tab === $tab_id ) ? 'nav-tab-active' : '';
				$url          = add_query_arg( array( 'page' => 'wp-verifier', 'tab' => $tab_id ), admin_url( 'plugins.php' ) );
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
