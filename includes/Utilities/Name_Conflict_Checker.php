<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Name_Conflict_Checker
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Utility class for checking plugin name conflicts.
 *
 * @since 1.9.0
 */
class Name_Conflict_Checker {

	/**
	 * Check if a plugin name exists on WordPress.org.
	 *
	 * @since 1.9.0
	 *
	 * @param string $name Plugin name to check.
	 * @return array Results with 'exists', 'slug', 'exact_match', 'similar_plugins'.
	 */
	public static function check_wordpress_org( $name ) {
		$slug = sanitize_title( $name );
		$cached = self::get_cached_check( $slug );

		if ( $cached ) {
			return $cached;
		}

		$results = array(
			'exists'          => false,
			'slug'            => $slug,
			'exact_match'     => false,
			'similar_plugins' => array(),
		);

		// Check exact slug match
		$api_url = 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json';
		$response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$plugin_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $plugin_data && isset( $plugin_data['slug'] ) ) {
				$results['exists'] = true;
				$results['exact_match'] = true;
				$results['similar_plugins'][] = array(
					'name'   => $plugin_data['name'],
					'slug'   => $plugin_data['slug'],
					'author' => $plugin_data['author'] ?? '',
				);
			}
		}

		// Search for similar names
		$search_url = 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[search]=' . urlencode( $name ) . '&request[per_page]=5';
		$search_response = wp_remote_get( $search_url, array( 'timeout' => 10 ) );

		if ( ! is_wp_error( $search_response ) && 200 === wp_remote_retrieve_response_code( $search_response ) ) {
			$search_data = json_decode( wp_remote_retrieve_body( $search_response ), true );
			if ( $search_data && isset( $search_data['plugins'] ) ) {
				foreach ( $search_data['plugins'] as $plugin ) {
					if ( $plugin['slug'] !== $slug ) {
						$results['similar_plugins'][] = array(
							'name'   => $plugin['name'],
							'slug'   => $plugin['slug'],
							'author' => $plugin['author'] ?? '',
						);
					}
				}
				if ( ! empty( $search_data['plugins'] ) ) {
					$results['exists'] = true;
				}
			}
		}

		self::cache_check( $slug, $results );
		return $results;
	}

	/**
	 * Get cached conflict check results.
	 *
	 * @since 1.9.0
	 *
	 * @param string $slug Plugin slug.
	 * @return array|false Cached results or false.
	 */
	private static function get_cached_check( $slug ) {
		return get_transient( 'wpv_conflict_' . md5( $slug ) );
	}

	/**
	 * Cache conflict check results.
	 *
	 * @since 1.9.0
	 *
	 * @param string $slug    Plugin slug.
	 * @param array  $results Check results.
	 */
	private static function cache_check( $slug, $results ) {
		set_transient( 'wpv_conflict_' . md5( $slug ), $results, 6 * HOUR_IN_SECONDS );
	}
}
