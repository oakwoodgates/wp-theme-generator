<?php

/**
 * WP Customizer Class
 *
 * @author Elexicon, Inc.
 * @version 1.0.0
 */

if( !class_exists('{%THEME_CLASS_NAMES%}_Customizer') ) :

	class {%THEME_CLASS_NAMES%}_Customizer {

		/**
		 * Initializes the theme customizer functions
		 */
		public function __construct() {
			add_action( 'customize_register', '{%THEME_CLASS_NAMES%}_Customizer::customize_register' );
			add_action( 'customize_preview_init', '{%THEME_CLASS_NAMES%}_Customizer::customize_preview_js' );
		}

		/**
		 * Registers the theme customizer settings
		 *
		 * @param object $wp_customize WP Customizer Object
		 * @return null
		 */
		public static function customize_register( $wp_customize ) {
			$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
			$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
			$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';
		}

		/**
		 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
		 *
		 * @return null
		 */
		public static function customize_preview_js() {
			wp_enqueue_script( 'customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), {%THEME_CLASS_NAMES%}::$theme_version, true );
		}
	}

	new {%THEME_CLASS_NAMES%}_Customizer();
endif;
