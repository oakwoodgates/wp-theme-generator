<?php

/**
 * {%THEME_NAME%} Theme initialization class
 *
 * Initial theme setup and functions
 *
 * @author Tyler Bailey <tylerb.media@gmail.com>
 * @version 1.0.0
 */

namespace Elexicon;

if( !class_exists('Elexicon\ThemeInit') ) :

	class ThemeInit {

		/**
		 * Constructs the class variables
		 *
		 * Configures theme with WP
		 */
		public function __construct() {
			$this->setup_theme();
			$this->register_theme_menus();
		}

		/**
		 * Hook into the WP actions to register the theme
		 */
		private function setup_theme() {
			add_action( 'after_setup_theme', array($this, 'theme_init') );
			add_action( 'wp_enqueue_scripts', array($this, 'enqueue_files') );
			add_action( 'customize_register', 'Elexicon\\Customizer::customize_register' );
			add_action( 'customize_preview_init', 'Elexicon\\Customizer::customize_preview_js' );
		}

		/**
		 * Initialize The Theme
		 * @return null
		 */
		public function theme_init() {
			/*
			 * Make theme available for translation.
			 * Translations can be filed in the /languages/ directory.
			 * If you're building a theme based on {%THEME_PREFIX%}, use a find and replace
			 * to change '{%THEME_PREFIX%}' to the name of your theme in all the template files.
			 */
			load_theme_textdomain( \Elexicon\Helper::$theme_prefix, get_template_directory() . '/languages' );

			// Add default posts and comments RSS feed links to head.
			add_theme_support( 'automatic-feed-links' );

			/*
			 * Let WordPress manage the document title.
			 * By adding theme support, we declare that this theme does not use a
			 * hard-coded <title> tag in the document head, and expect WordPress to
			 * provide it for us.
			 */
			add_theme_support( 'title-tag' );

			/*
			 * Enable support for Post Thumbnails on posts and pages.
			 */
			add_theme_support( 'post-thumbnails' );

			// This theme uses wp_nav_menu() in one location.
			register_nav_menus( array(
				'primary' => esc_html__( 'Primary', \Elexicon\Helper::$theme_prefix),
				'secondary' => esc_html__( 'Secondary', \Elexicon\Helper::$theme_prefix),
				'footer' => esc_html__( 'Footer', \Elexicon\Helper::$theme_prefix)
			) );

			/*
			 * Switch default core markup for search form, comment form, and comments
			 * to output valid HTML5.
			 */
			add_theme_support( 'html5', array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			) );

			/*
			 * Enable support for Post Formats.
			 */
			add_theme_support( 'post-formats', array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
			) );

			// Set up the WordPress core custom background feature.
			add_theme_support( 'custom-background', apply_filters( 'custom_background_args', array(
				'default-color' => 'ffffff',
				'default-image' => '',
			) ) );

			// Remove unnecessary elements from header
			remove_action('wp_head', 'rsd_link');
			remove_action('wp_head', 'wp_generator');
			remove_action('wp_head', 'feed_links', 2);
			remove_action('wp_head', 'index_rel_link');
			remove_action('wp_head', 'wlwmanifest_link');
			remove_action('wp_head', 'feed_links_extra', 3);
			remove_action('wp_head', 'start_post_rel_link', 10, 0);
			remove_action('wp_head', 'parent_post_rel_link', 10, 0);
			remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
		}

		/**
		 * Enqueue Theme Scripts and Styles
		 * @return null
		 */
		public function enqueue_files() {
			// Stylesheets
		  wp_enqueue_style( 'wp-styles', get_stylesheet_uri() );
		  wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );

		  $theme_style = ({%THEME_CONST%}_DEV ? 'style.css' : 'style.min.css');
		  wp_enqueue_style( \Elexicon\Helper::$theme_slug . '-style', get_template_directory_uri() . '/dist/styles/' . $theme_style );

		  wp_enqueue_script( \Elexicon\Helper::$theme_slug . '-js', get_template_directory_uri() . '/dist/js/bundle.js', array(), \Elexicon\Helper::$theme_version, true );

		  // Localize scripts
		  $this->localize_theme_scripts();
		}


		/**
		 * Localize PHP variables to Javascript for usage
		 * @return null
		 */
		private function localize_theme_scripts() {
			global $post;

		  $cur_page = $post->post_name;

		  if(is_archive()) {
		    $cur_page = get_post_type();
		  }
		  // Localize the global admin-ajax URL
		  // usage: autoloader.ajaxurl;
		  wp_localize_script(
		    \Elexicon\Helper::$theme_slug . '-js',
		    \Elexicon\Helper::$theme_prefix,
		    array(
					'ajaxnonce' => wp_create_nonce('ajax-nonce'),
		      'ajaxurl' => admin_url( 'admin-ajax.php' ),
		      'isMobile' => (wp_is_mobile() ? true : false),
		      'curPage' => $cur_page,
		      'isHome' => (is_home() || is_front_page() ? true : false),
		      'isSingle' => (is_single() ? true : false),
		    )
		  );
		}

		/**
		 * Register theme menus with WordPress
		 * @return null
		 */
		private static function register_theme_menus() {
			// Register the nav menu locations
			register_nav_menus( array(
				'primary' => esc_html__( 'Primary', \Elexicon\Helper::$theme_prefix),
				'secondary' => esc_html__( 'Secondary', \Elexicon\Helper::$theme_prefix),
				'footer' => esc_html__( 'Footer', \Elexicon\Helper::$theme_prefix)
			) );
		}
	}

endif;
