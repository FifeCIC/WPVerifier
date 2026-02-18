<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Trademark_Checker
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Utility class for basic trademark checking.
 *
 * @since 1.9.0
 */
class Trademark_Checker {

	/**
	 * Known trademarked terms to avoid.
	 *
	 * @since 1.9.0
	 * @var array
	 */
	private static $trademarked_terms = array(
		'google', 'facebook', 'twitter', 'instagram', 'youtube', 'amazon',
		'microsoft', 'apple', 'adobe', 'salesforce', 'oracle', 'ibm',
		'paypal', 'stripe', 'mailchimp', 'hubspot', 'shopify', 'woocommerce',
		'elementor', 'divi', 'beaver', 'visual composer', 'wpbakery',
		'yoast', 'jetpack', 'akismet', 'gravityforms', 'ninja forms',
	);

	/**
	 * Check for potential trademark conflicts.
	 *
	 * @since 1.9.0
	 *
	 * @param string $name Plugin name to check.
	 * @return array Trademark check results.
	 */
	public static function check( $name ) {
		$name_lower = strtolower( $name );
		$words = str_word_count( $name_lower, 1 );

		$results = array(
			'has_conflicts' => false,
			'conflicts'     => array(),
			'warnings'      => array(),
			'risk_level'    => 'low',
		);

		// Check for exact matches
		foreach ( self::$trademarked_terms as $term ) {
			if ( in_array( $term, $words, true ) || strpos( $name_lower, $term ) !== false ) {
				$results['has_conflicts'] = true;
				$results['conflicts'][] = array(
					'term'    => $term,
					'type'    => 'exact',
					'message' => sprintf( 'Contains trademarked term: "%s"', ucfirst( $term ) ),
				);
			}
		}

		// Check for WordPress trademark usage
		if ( preg_match( '/\bwordpress\b/i', $name ) && ! preg_match( '/\bfor wordpress\b|\bwp\b/i', $name ) ) {
			$results['warnings'][] = array(
				'term'    => 'WordPress',
				'message' => 'WordPress trademark should be used as "for WordPress" or abbreviated as "WP"',
				'link'    => 'https://wordpressfoundation.org/trademark-policy/',
			);
			$results['risk_level'] = 'medium';
		}

		// Check for generic terms that might be trademarked
		$generic_risky = array( 'pro', 'premium', 'ultimate', 'advanced', 'professional' );
		foreach ( $generic_risky as $term ) {
			if ( in_array( $term, $words, true ) ) {
				$results['warnings'][] = array(
					'term'    => $term,
					'message' => sprintf( '"%s" is commonly trademarked - verify availability', ucfirst( $term ) ),
				);
			}
		}

		// Set risk level
		if ( count( $results['conflicts'] ) > 0 ) {
			$results['risk_level'] = 'high';
		} elseif ( count( $results['warnings'] ) > 1 ) {
			$results['risk_level'] = 'medium';
		}

		return $results;
	}

	/**
	 * Get trademark guidelines.
	 *
	 * @since 1.9.0
	 *
	 * @return array Trademark guidelines.
	 */
	public static function get_guidelines() {
		return array(
			array(
				'title'       => 'WordPress Trademark',
				'description' => 'Use "for WordPress" or "WP" prefix instead of "WordPress" in plugin names',
				'link'        => 'https://wordpressfoundation.org/trademark-policy/',
			),
			array(
				'title'       => 'Third-Party Trademarks',
				'description' => 'Avoid using company names, product names, or brand names without permission',
				'link'        => '',
			),
			array(
				'title'       => 'Generic Terms',
				'description' => 'Be cautious with terms like "Pro", "Premium", "Ultimate" as they may be trademarked',
				'link'        => '',
			),
			array(
				'title'       => 'Legal Disclaimer',
				'description' => 'This is a basic check only. Consult a trademark attorney for legal advice',
				'link'        => '',
			),
		);
	}
}
