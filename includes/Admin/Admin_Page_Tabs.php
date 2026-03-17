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
			'select'      => array( 'title' => __( 'Select Plugin', 'wp-verifier' ), 'code' => 'TAB01' ),
			'preparation' => array( 'title' => __( 'Configure', 'wp-verifier' ), 'code' => 'TAB02' ),
			'verify'      => array( 'title' => __( 'Verification', 'wp-verifier' ), 'code' => 'TAB03' ),
			'results'     => array( 'title' => __( 'Files', 'wp-verifier' ), 'code' => 'TAB04' ),
			'issues'      => array( 'title' => __( 'Issues', 'wp-verifier' ), 'code' => 'TAB05' ),
			'monitoring'  => array( 'title' => __( 'Plugin Monitoring', 'wp-verifier' ), 'code' => 'TAB06' ),
			'test-area'   => array( 'title' => __( 'Test Area', 'wp-verifier' ), 'code' => 'TAB07' ),
			'error-codes' => array( 'title' => __( 'Error Codes', 'wp-verifier' ), 'code' => 'TAB08' ),
			'settings'    => array( 'title' => __( 'Settings', 'wp-verifier' ), 'code' => 'TAB09' ),
			'assets'      => array( 'title' => __( 'Assets', 'wp-verifier' ), 'code' => 'TAB10' ),
			'architecture' => array( 'title' => __( 'Architecture', 'wp-verifier' ), 'code' => 'TAB11' ),
			'roadmap'     => array( 'title' => __( 'Roadmap', 'wp-verifier' ), 'code' => 'TAB12' ),
		);
	}

	/**
	 * Display tabs navigation
	 */
	public static function render_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'verify';
		$tabs        = self::get_tabs();
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_id => $tab_data ) {
				$active_class = ( $current_tab === $tab_id ) ? 'nav-tab-active' : '';
				$url          = add_query_arg( array( 'page' => 'wp-verifier', 'tab' => $tab_id ), admin_url( 'plugins.php' ) );
				printf(
					'<a href="%s" class="nav-tab %s">',
					esc_url( $url ),
					esc_attr( $active_class )
				);
				wpverifier_header( $tab_data['title'], $tab_data['code'], true );
				echo '</a>';
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
