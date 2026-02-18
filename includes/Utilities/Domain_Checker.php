<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Domain_Checker
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Checks domain availability across multiple TLDs.
 */
class Domain_Checker {

	/**
	 * Common TLDs to check.
	 */
	const COMMON_TLDS = array( 'com', 'net', 'org', 'io', 'dev', 'app' );

	/**
	 * Check domain availability for multiple TLDs.
	 *
	 * @param string $domain_name Domain name without TLD.
	 * @param array  $tlds        Optional array of TLDs to check.
	 * @return array Results for each TLD.
	 */
	public static function check_domains( $domain_name, $tlds = null ) {
		if ( null === $tlds ) {
			$tlds = self::COMMON_TLDS;
		}

		$results = array();
		foreach ( $tlds as $tld ) {
			$full_domain = $domain_name . '.' . $tld;
			$results[ $tld ] = self::check_single_domain( $full_domain );
		}

		return $results;
	}

	/**
	 * Check if a single domain is available.
	 *
	 * @param string $domain Full domain name.
	 * @return array Status and details.
	 */
	private static function check_single_domain( $domain ) {
		$result = array(
			'domain'    => $domain,
			'available' => null,
			'status'    => 'unknown',
			'checked'   => time(),
		);

		// DNS lookup method
		$dns = @dns_get_record( $domain, DNS_A + DNS_AAAA );
		if ( false === $dns || empty( $dns ) ) {
			$result['available'] = true;
			$result['status'] = 'available';
		} else {
			$result['available'] = false;
			$result['status'] = 'registered';
		}

		return $result;
	}

	/**
	 * Get cached domain check results.
	 *
	 * @param string $domain_name Domain name.
	 * @return array|null Cached results or null.
	 */
	public static function get_cached_results( $domain_name ) {
		$cache_key = 'wpv_domain_check_' . md5( $domain_name );
		$cached = get_transient( $cache_key );
		return $cached ? $cached : null;
	}

	/**
	 * Cache domain check results.
	 *
	 * @param string $domain_name Domain name.
	 * @param array  $results     Results to cache.
	 * @param int    $expiration  Cache expiration in seconds (default 1 hour).
	 */
	public static function cache_results( $domain_name, $results, $expiration = 3600 ) {
		$cache_key = 'wpv_domain_check_' . md5( $domain_name );
		set_transient( $cache_key, $results, $expiration );
	}

	/**
	 * Format domain name for checking.
	 *
	 * @param string $name Plugin or domain name.
	 * @return string Formatted domain name.
	 */
	public static function format_domain_name( $name ) {
		$name = strtolower( $name );
		$name = preg_replace( '/[^a-z0-9-]/', '-', $name );
		$name = preg_replace( '/-+/', '-', $name );
		$name = trim( $name, '-' );
		return $name;
	}
}
