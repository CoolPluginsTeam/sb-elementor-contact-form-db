<?php

/*
 * Plugin Name: FormsDB For Elementor Forms
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

namespace Formsdb_Elementor_Forms;
use Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}   

define( 'FDBGP_PLUGIN_FILE', __FILE__ );
define( 'FDBGP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FDBGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FDBGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FDBGP_PLUGIN_VERSION', '1.8.1' );
define('FDBGP_FEEDBACK_URL', 'https://feedback.coolplugins.net/');


register_activation_hook( FDBGP_PLUGIN_FILE, array( 'Formsdb_Elementor_Forms\FDBGP_Main', 'fdbgp_activate' ) );
register_deactivation_hook( FDBGP_PLUGIN_FILE, array( 'Formsdb_Elementor_Forms\FDBGP_Main', 'fdbgp_deactivate' ) );
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

			static $autoloader_registered = false;

			if ( ! $autoloader_registered ) {
				$autoloader_registered = spl_autoload_register( [ $this, 'autoload' ] );
			}

			add_action( 'plugins_loaded', array( $this, 'FDBGP_plugins_loaded' ) );
			add_action( 'plugins_loaded', array( $this, 'setting_redirect' ));


			$this->includes();
			
		}

		public function setting_redirect(){
			// Get site domain and redirect URI
			$site_url = parse_url(site_url(), PHP_URL_HOST);
			$site_domain = str_replace('www.', '', $site_url);
			$redirect_uri = admin_url('admin.php?page=formsdb');

			// Handle OAuth callback
			if (isset($_GET['code']) && !empty($_GET['code'])) {
				// Get Google settings
				$google_settings = get_option('fdbgp_google_settings', array(
					'client_id' => '',
					'client_secret' => '',
					'client_token' => ''
				));

				$code = sanitize_text_field($_GET['code']);
				$google_settings['client_token'] = $code;
				update_option('fdbgp_google_settings', $google_settings);

				$redirect_url = remove_query_arg('code');
				$redirect_uri = preg_replace('&code='.$code, '', $redirect_uri);
				$redirect_url = preg_replace('/&scope=[^&]*/', '', $redirect_url);

				// Remove code from URL and redirect
				wp_redirect($redirect_url);
				exit;
			}
		}

		public function FDBGP_plugins_loaded() {
			// Add plugin dashboard link
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'FDBGP_plugin_dashboard_link' ) );

			// Get the loader instance
			\FDBGP_Loader::get_instance();

		}

		function FDBGP_plugin_dashboard_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=formsdb' ) . '">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		private function includes() {
			if(!class_exists('Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice')){
				require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cpfm-common-notice.php';
			}

			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-loader.php';
			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-cache-manager.php';
			if ( is_admin() ) {
				require_once FDBGP_PLUGIN_DIR . 'admin/feedback/admin-feedback-form.php';
				// Load old submission handler early for CSV export to work
				require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
			}
			require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cron/fdbgp-class-cron.php';
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

		public static function fdbgp_activate() {
			update_option( 'fdbgp-v', FDBGP_PLUGIN_VERSION );
			update_option( 'fdbgp-type', 'FREE' );
			update_option( 'fdbgp-installDate', gmdate( 'Y-m-d h:i:s' ) );

			if (!get_option( 'formsdb_initial_version' ) ) {
                add_option( 'formsdb_initial_version', FDBGP_PLUGIN_VERSION );
            }

			if(!get_option( 'fdbgp-install-date' ) ) {
				add_option( 'fdbgp-install-date', gmdate('Y-m-d h:i:s') );
        	}


			$settings       = get_option('cfef_usage_share_data');

			
			if (!empty($settings) || $settings === 'on'){
				
				static::fdbgp_cron_job_init();
			}
		}

		public static function fdbgp_cron_job_init()
		{
			if (!wp_next_scheduled('fdbgp_extra_data_update')) {
				wp_schedule_event(time(), 'every_30_days', 'fdbgp_extra_data_update');
			}
		}


		/**
		 * Function run on plugin deactivate
		 */
		public static function fdbgp_deactivate() {

			if (wp_next_scheduled('fdbgp_extra_data_update')) {
            	wp_clear_scheduled_hook('fdbgp_extra_data_update');
        	}
		}

	}

	FDBGP_Main::get_instance();

}
