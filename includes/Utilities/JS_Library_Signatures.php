<?php
/**
 * Class WordPress\Plugin_Check\Utilities\JS_Library_Signatures
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * JavaScript library signature patterns for detection.
 */
class JS_Library_Signatures {

	/**
	 * Library signature patterns.
	 * Each entry contains regex patterns to match in JS files.
	 */
	const SIGNATURES = array(
		'select2' => array(
			'patterns' => array(
				'/Select2\s+(\d+\.\d+\.\d+)/',
				'/jquery\.fn\.select2/',
				'/@license.*select2/i',
			),
			'name' => 'Select2',
		),
		'blockui' => array(
			'patterns' => array(
				'/jQuery BlockUI.*v(\d+\.\d+\.\d+)/',
				'/jquery\.blockUI/',
				'/\$\.blockUI/',
			),
			'name' => 'jQuery BlockUI',
		),
		'mermaid' => array(
			'patterns' => array(
				'/mermaid.*(\d+\.\d+\.\d+)/',
				'/window\.mermaid/',
				'/@license.*mermaid/i',
			),
			'name' => 'Mermaid',
		),
		'datatables' => array(
			'patterns' => array(
				'/DataTables\s+(\d+\.\d+\.\d+)/',
				'/jquery\.fn\.dataTable/',
			),
			'name' => 'DataTables',
		),
		'chosen' => array(
			'patterns' => array(
				'/Chosen.*v(\d+\.\d+\.\d+)/',
				'/jquery\.fn\.chosen/',
			),
			'name' => 'Chosen',
		),
		'slick' => array(
			'patterns' => array(
				'/Slick\s+(\d+\.\d+\.\d+)/',
				'/jquery\.fn\.slick/',
			),
			'name' => 'Slick Slider',
		),
		'owl-carousel' => array(
			'patterns' => array(
				'/Owl Carousel.*v(\d+\.\d+\.\d+)/',
				'/jquery\.fn\.owlCarousel/',
			),
			'name' => 'Owl Carousel',
		),
		'magnific-popup' => array(
			'patterns' => array(
				'/Magnific Popup.*(\d+\.\d+\.\d+)/',
				'/jquery\.magnificPopup/',
			),
			'name' => 'Magnific Popup',
		),
		'jquery-ui' => array(
			'patterns' => array(
				'/jQuery UI.*(\d+\.\d+\.\d+)/',
				'/jquery\.ui\./',
			),
			'name' => 'jQuery UI',
		),
		'jquery-validation' => array(
			'patterns' => array(
				'/jQuery Validation.*(\d+\.\d+\.\d+)/',
				'/jquery\.validator/',
			),
			'name' => 'jQuery Validation',
		),
		'jquery-migrate' => array(
			'patterns' => array(
				'/jQuery Migrate.*(\d+\.\d+\.\d+)/',
				'/jquery-migrate/',
			),
			'name' => 'jQuery Migrate',
		),
		'lodash' => array(
			'patterns' => array(
				'/lodash.*(\d+\.\d+\.\d+)/',
				'/window\._\s*=/',
			),
			'name' => 'Lodash',
		),
		'underscore' => array(
			'patterns' => array(
				'/Underscore\.js.*(\d+\.\d+\.\d+)/',
				'/window\._\s*=.*Underscore/',
			),
			'name' => 'Underscore.js',
		),
		'moment' => array(
			'patterns' => array(
				'/moment\.js.*(\d+\.\d+\.\d+)/',
				'/window\.moment\s*=/',
			),
			'name' => 'Moment.js',
		),
		'chart' => array(
			'patterns' => array(
				'/Chart\.js.*v(\d+\.\d+\.\d+)/',
				'/window\.Chart\s*=/',
			),
			'name' => 'Chart.js',
		),
		'd3' => array(
			'patterns' => array(
				'/d3\.js.*(\d+\.\d+\.\d+)/',
				'/window\.d3\s*=/',
			),
			'name' => 'D3.js',
		),
		'axios' => array(
			'patterns' => array(
				'/axios.*(\d+\.\d+\.\d+)/',
				'/window\.axios\s*=/',
			),
			'name' => 'Axios',
		),
		'popper' => array(
			'patterns' => array(
				'/Popper.*(\d+\.\d+\.\d+)/',
				'/window\.Popper\s*=/',
			),
			'name' => 'Popper.js',
		),
		'tippy' => array(
			'patterns' => array(
				'/Tippy\.js.*(\d+\.\d+\.\d+)/',
				'/window\.tippy\s*=/',
			),
			'name' => 'Tippy.js',
		),
		'sweetalert' => array(
			'patterns' => array(
				'/SweetAlert.*(\d+\.\d+\.\d+)/',
				'/window\.swal\s*=/',
			),
			'name' => 'SweetAlert',
		),
		'toastr' => array(
			'patterns' => array(
				'/toastr.*(\d+\.\d+\.\d+)/',
				'/window\.toastr\s*=/',
			),
			'name' => 'Toastr',
		),
	);

	/**
	 * Get all library signatures.
	 *
	 * @return array Library signatures.
	 */
	public static function get_signatures() {
		return self::SIGNATURES;
	}

	/**
	 * Get signature for a specific library.
	 *
	 * @param string $library_key Library key.
	 * @return array|null Library signature or null.
	 */
	public static function get_signature( $library_key ) {
		return isset( self::SIGNATURES[ $library_key ] ) ? self::SIGNATURES[ $library_key ] : null;
	}
}
