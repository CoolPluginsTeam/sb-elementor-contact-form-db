<?php



if (!defined('ABSPATH')) {
    die;
}

if(!class_exists('FDBGP_Admin')) { 

    class FDBGP_Admin {

        private static $instance = null;

        /**
         * Main FDBGP_Admin Instance.
         *
         * Ensures only one instance of FDBGP_Admin is loaded or can be loaded.
         *
         * @return FDBGP_Admin - Main instance.
        */

        public static function get_instance($plugin_name, $version) {
            if ( null == self::$instance ) {
                self::$instance = new self($plugin_name, $version);
            }
            return self::$instance;
        }

        /**
         * FDBGP_Admin Constructor.
         */
        private function __construct($plugin_name, $version) {
            // Admin related hooks and actions can be added here
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'),999);

        }

        public static function get_instance($plugin_name, $version) {
            if (null == self::$instance) {
                self::$instance = new self($plugin_name, $version);
            }
            return self::$instance;
        }

        public function add_plugin_admin_menu() {
            add_submenu_page(
                'elementor',
                __('Cool FormKit', 'cool-formkit'),
                __('Cool FormKit', 'cool-formkit'),
                'manage_options',
                'FomrsDB',
                array($this, 'display_plugin_admin_page')
            );
        }

        public function display_plugin_admin_page() {
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'form-elements';
            ?>
            <div class="fdbgp-wrapper">
                <div class="fdbgp-header">
                    <div class="fdbgp-header-logo">
                        <a href="?page=cool-formkit">
                            <img src="<?php echo esc_url(FDBGP_PLUGIN_URL . 'assets/images/logo-cool-formkit.png'); ?>" alt="Cool FormKit Logo">
                        </a>
                    </div>
                    <div class="fdbgp-header-buttons">
                        <p><?php esc_html_e('Advanced Elementor Form Builder.', 'cool-formkit'); ?></p>
                        <a href="https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_header" class="button" target="_blank"><?php esc_html_e('Check Docs', 'cool-formkit'); ?></a>
                        <a href="https://coolformkit.com/features/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=demo&utm_content=setting_page_header" class="button button-secondary" target="_blank"><?php esc_html_e('View Form Demos', 'cool-formkit'); ?></a>
                    </div>
                </div>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=cool-formkit&tab=form-elements" class="nav-tab <?php echo $tab == 'form-elements' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Form Elements', 'cool-formkit'); ?></a>
                    <a href="?page=fdbgp-entries" class="nav-tab <?php echo $tab == 'fdbgp-entries' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Entries', 'cool-formkit'); ?></a>
                    <a href="?page=cool-formkit&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'cool-formkit'); ?></a>
                    <a href="?page=cool-formkit&tab=license" class="nav-tab <?php echo $tab == 'license' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('License', 'cool-formkit'); ?></a>
                </h2>
                <div class="tab-content">
                    <?php
                    // switch ($tab) {
                    //     case 'form-elements':
                    //         include_once 'views/form-elements.php';
                    //         break;
                    //     case 'settings':
                    //         include_once 'views/settings.php';
                    //         break;
                    //     case 'license':
                    //         include_once 'views/license.php';
                    //         break;
                    // }
                    ?>
                </div>
            </div>
            <?php
        }

    }
}