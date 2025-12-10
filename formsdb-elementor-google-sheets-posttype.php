<?php

/*
 * Plugin Name: FormsDB - Save Elementor Forms to Google Sheets & Post Type
 * Plugin URI:  https://webacetechs.in
 * Description: A simple plugin to save contact form submissions in the database, designed for the Elementor Form Module
 * Author:      Cool Plugins
 * Version:     1.8.1
 * Author URI:  https://coolplugins.net/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * Text Domain: elementor-contact-form-db
 * Requires Plugins: elementor
 * Elementor tested up to: 3.33.4
 * Elementor Pro tested up to: 3.33.2
 */

namespace Formsdb_Google_Sheets_Posttype;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}   

define( 'FDBGP_PLUGIN_FILE', __FILE__ );
define( 'FDBGP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FDBGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FDBGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FDBGP_PLUGIN_VERSION', '1.8.1' );


if(!class_exists('FDBGP_Main')) { 

	class FDBGP_Main {

		private static $instance = null;

		/**
		 * Main FDBGP_Main Instance.
		 *
		 * Ensures only one instance of FDBGP_Main is loaded or can be loaded.
		 *
		 * @return FDBGP_Main - Main instance.
		*/

		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * FDBGP_Main Constructor.
		 */
		private function __construct() {
			if (! version_compare(PHP_VERSION, '7.4', '>=')) {
				add_action('admin_notices', [$this, 'admin_notice_php_version_fail']);
				return false;
			}

			var_dump("fsklsdf");
			
			static $autoloader_registered = false;

			if ( ! $autoloader_registered ) {
				$autoloader_registered = spl_autoload_register( [ $this, 'autoload' ] );
			}

			$this->includes();
			
		}

		public function autoload( $class_name ) {

		
			if ( 0 !== strpos( $class_name, __NAMESPACE__ ) ) {
				return;
			}
			$has_class_alias = isset( $this->classes_aliases[ $class_name ] );

			// Backward Compatibility: Save old class name for set an alias after the new class is loaded
			if ( $has_class_alias ) {
				$class_alias_name = $this->classes_aliases[ $class_name ];
				$class_to_load = $class_alias_name;
			} else {
				$class_to_load = $class_name;
			}
			
			if ( ! class_exists( $class_to_load ) ) {
				$filename = strtolower(
					preg_replace(
						[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
						[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
						$class_to_load
					)
				);


				$filename = trailingslashit( FDBGP_PLUGIN_DIR ) . $filename . '.php';


				if ( is_readable( $filename ) ) {
					include $filename;
				}
			}

			if ( $has_class_alias ) {
				class_alias( $class_alias_name, $class_name );
			}
		}

		private function includes() {
			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-loader.php';
		}


	}

	FDBGP_Main::get_instance();

}
