<?php
/**
 * Class WordPress\Plugin_Check\Utilities\SEO_Analyzer
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Utility class for SEO analysis of plugin names.
 *
 * @since 1.9.0
 */
class SEO_Analyzer {

	/**
	 * Analyze plugin name for SEO.
	 *
	 * @since 1.9.0
	 *
	 * @param string $name Plugin name.
	 * @return array SEO analysis results.
	 */
	public static function analyze( $name ) {
		$name = trim( $name );
		$word_count = str_word_count( $name );
		$char_count = strlen( $name );
		$words = str_word_count( strtolower( $name ), 1 );

		return array(
			'length'      => self::analyze_length( $char_count, $word_count ),
			'keywords'    => self::analyze_keywords( $words ),
			'readability' => self::analyze_readability( $name, $words ),
			'score'       => 0, // Calculated after
		);
	}

	/**
	 * Analyze name length.
	 *
	 * @since 1.9.0
	 *
	 * @param int $char_count Character count.
	 * @param int $word_count Word count.
	 * @return array Length analysis.
	 */
	private static function analyze_length( $char_count, $word_count ) {
		$score = 0;
		$issues = array();
		$recommendations = array();

		// Optimal: 2-4 words, 10-30 characters
		if ( $word_count >= 2 && $word_count <= 4 ) {
			$score += 30;
		} elseif ( $word_count === 1 ) {
			$issues[] = 'Single word names may be too generic';
			$recommendations[] = 'Consider adding a descriptive word';
		} else {
			$issues[] = 'Name is too long (' . $word_count . ' words)';
			$recommendations[] = 'Aim for 2-4 words for better memorability';
		}

		if ( $char_count >= 10 && $char_count <= 30 ) {
			$score += 20;
		} elseif ( $char_count < 10 ) {
			$issues[] = 'Name is very short';
		} else {
			$issues[] = 'Name is too long (' . $char_count . ' characters)';
			$recommendations[] = 'Shorter names are easier to remember';
		}

		return array(
			'score'           => $score,
			'char_count'      => $char_count,
			'word_count'      => $word_count,
			'issues'          => $issues,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Analyze keywords.
	 *
	 * @since 1.9.0
	 *
	 * @param array $words Array of words.
	 * @return array Keyword analysis.
	 */
	private static function analyze_keywords( $words ) {
		$score = 0;
		$issues = array();
		$recommendations = array();

		// Check for WordPress-related keywords
		$wp_keywords = array( 'wp', 'wordpress', 'plugin' );
		$has_wp_keyword = false;
		foreach ( $wp_keywords as $keyword ) {
			if ( in_array( $keyword, $words, true ) ) {
				$has_wp_keyword = true;
				break;
			}
		}

		if ( $has_wp_keyword ) {
			$score += 15;
		} else {
			$recommendations[] = 'Consider including "WP" or "WordPress" for better discoverability';
		}

		// Check for descriptive keywords
		$common_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'for', 'with' );
		$descriptive_count = 0;
		foreach ( $words as $word ) {
			if ( ! in_array( $word, $common_words, true ) && strlen( $word ) > 3 ) {
				$descriptive_count++;
			}
		}

		if ( $descriptive_count >= 1 ) {
			$score += 20;
		} else {
			$issues[] = 'Lacks descriptive keywords';
			$recommendations[] = 'Add words that describe what the plugin does';
		}

		return array(
			'score'           => $score,
			'has_wp_keyword'  => $has_wp_keyword,
			'descriptive_count' => $descriptive_count,
			'issues'          => $issues,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Analyze readability.
	 *
	 * @since 1.9.0
	 *
	 * @param string $name  Plugin name.
	 * @param array  $words Array of words.
	 * @return array Readability analysis.
	 */
	private static function analyze_readability( $name, $words ) {
		$score = 0;
		$issues = array();
		$recommendations = array();

		// Check for proper capitalization
		if ( ucwords( strtolower( $name ) ) === $name || strtoupper( $name ) === $name ) {
			$score += 15;
		} else {
			$recommendations[] = 'Use consistent capitalization (Title Case or UPPERCASE)';
		}

		// Check for special characters
		if ( preg_match( '/^[a-zA-Z0-9\s]+$/', $name ) ) {
			$score += 10;
		} else {
			$issues[] = 'Contains special characters that may affect searchability';
		}

		// Check for numbers
		if ( preg_match( '/\d/', $name ) ) {
			$recommendations[] = 'Numbers in names can reduce memorability';
		} else {
			$score += 5;
		}

		// Check average word length
		$total_length = 0;
		foreach ( $words as $word ) {
			$total_length += strlen( $word );
		}
		$avg_length = count( $words ) > 0 ? $total_length / count( $words ) : 0;

		if ( $avg_length >= 4 && $avg_length <= 8 ) {
			$score += 10;
		} elseif ( $avg_length > 10 ) {
			$issues[] = 'Words are too long on average';
			$recommendations[] = 'Use simpler, shorter words';
		}

		return array(
			'score'           => $score,
			'avg_word_length' => round( $avg_length, 1 ),
			'issues'          => $issues,
			'recommendations' => $recommendations,
		);
	}
}
