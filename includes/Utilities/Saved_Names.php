<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Saved_Names
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages saved plugin name evaluations.
 */
class Saved_Names {

	const OPTION_NAME = 'wpv_saved_names';
	const MAX_SAVED = 50;

	/**
	 * Save a name evaluation.
	 *
	 * @param string $name       Plugin name.
	 * @param array  $evaluation Evaluation data.
	 * @param string $note       Optional note.
	 * @return bool Success.
	 */
	public static function save_name( $name, $evaluation, $note = '' ) {
		$saved = self::get_all();
		$id = md5( $name );

		$saved[ $id ] = array(
			'name'       => $name,
			'evaluation' => $evaluation,
			'note'       => $note,
			'saved_at'   => time(),
			'favorite'   => false,
		);

		if ( count( $saved ) > self::MAX_SAVED ) {
			$saved = array_slice( $saved, -self::MAX_SAVED, null, true );
		}

		return update_option( self::OPTION_NAME, $saved );
	}

	/**
	 * Get all saved names.
	 *
	 * @return array Saved names.
	 */
	public static function get_all() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Get a specific saved name.
	 *
	 * @param string $id Name ID.
	 * @return array|null Saved name data or null.
	 */
	public static function get_name( $id ) {
		$saved = self::get_all();
		return isset( $saved[ $id ] ) ? $saved[ $id ] : null;
	}

	/**
	 * Delete a saved name.
	 *
	 * @param string $id Name ID.
	 * @return bool Success.
	 */
	public static function delete_name( $id ) {
		$saved = self::get_all();
		unset( $saved[ $id ] );
		return update_option( self::OPTION_NAME, $saved );
	}

	/**
	 * Toggle favorite status.
	 *
	 * @param string $id Name ID.
	 * @return bool Success.
	 */
	public static function toggle_favorite( $id ) {
		$saved = self::get_all();
		if ( isset( $saved[ $id ] ) ) {
			$saved[ $id ]['favorite'] = ! $saved[ $id ]['favorite'];
			return update_option( self::OPTION_NAME, $saved );
		}
		return false;
	}

	/**
	 * Update note for a saved name.
	 *
	 * @param string $id   Name ID.
	 * @param string $note Note text.
	 * @return bool Success.
	 */
	public static function update_note( $id, $note ) {
		$saved = self::get_all();
		if ( isset( $saved[ $id ] ) ) {
			$saved[ $id ]['note'] = $note;
			return update_option( self::OPTION_NAME, $saved );
		}
		return false;
	}

	/**
	 * Get comparison data for multiple names.
	 *
	 * @param array $ids Array of name IDs.
	 * @return array Comparison data.
	 */
	public static function get_comparison( $ids ) {
		$saved = self::get_all();
		$comparison = array();

		foreach ( $ids as $id ) {
			if ( isset( $saved[ $id ] ) ) {
				$comparison[ $id ] = $saved[ $id ];
			}
		}

		return $comparison;
	}
}
