<?php
if (!defined('ABSPATH')) {
    die;
}

if(!class_exists('FDBGP_Admin')) { 

    class FDBGP_Admin {

        private static $instance = null;
        private $plugin_name;
        private $version;
        private $google_settings;
        
        /**
         * Main FDBGP_Admin Instance.
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
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            
            // Initialize Google settings
            $this->google_settings = get_option('fdbgp_google_settings', array(
                'client_id' => '',
                'client_secret' => '',
                'client_token' => ''
            ));
            
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'), 999);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        }

        public function add_plugin_admin_menu() {
            add_submenu_page(
                'elementor',
                __('FormsDB', 'elementor-contact-form-db'),
                __('FormsDB', 'elementor-contact-form-db'),
                'manage_options',
                'formsdb',
                array($this, 'display_plugin_admin_page')
            );
        }

        public function display_plugin_admin_page() {
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'forms-sheets';
            
            ?>
            <div class="fdbgp-wrapper">
                <div class="fdbgp-header">
                    <div class="fdbgp-header-logo">
                        <a href="?page=formsdb">
                            <img src="<?php echo esc_url(FDBGP_PLUGIN_URL . 'assets/images/logo-cool-formkit.png'); ?>" alt="Cool FormKit Logo">
                        </a>
                    </div>
                    <div class="fdbgp-header-buttons">
                        <p><?php esc_html_e('Advanced Elementor Form Builder.', 'elementor-contact-form-db'); ?></p>
                        <a href="https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_header" class="button" target="_blank"><?php esc_html_e('Check Docs', 'elementor-contact-form-db'); ?></a>
                        <a href="https://coolformkit.com/features/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=demo&utm_content=setting_page_header" class="button button-secondary" target="_blank"><?php esc_html_e('View Form Demos', 'elementor-contact-form-db'); ?></a>
                    </div>
                </div>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=formsdb&tab=forms-sheets" class="nav-tab <?php echo $tab == 'forms-sheets' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Sheet', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=post-type" class="nav-tab <?php echo $tab == 'post-type' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Post Type', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=hello-plus-entries" class="nav-tab <?php echo $tab == 'hello-plus-entries' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hello+ Form Entries', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=advanced" class="nav-tab <?php echo $tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced Fields', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=google-api" class="nav-tab <?php echo $tab == 'google-api' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Google API', 'elementor-contact-form-db'); ?></a>
                </h2>
                <div class="tab-content">
                    <?php
                    switch ($tab) {
                        case 'settings':
                            include_once 'views/settings.php';
                            break;
                        default:
                            // Show default tab content
                            break;
                    }
                    ?>
                </div>
            </div>
            <?php
        }

        public function enqueue_admin_styles() {
            wp_enqueue_style('fdbgp-admin-global-style', FDBGP_PLUGIN_URL . 'assets/css/global-admin-style.css', array(), $this->version, 'all');

            if (isset($_GET['page']) && (strpos($_GET['page'], 'formsdb') !== false)){
                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                wp_enqueue_style('dashicons');

                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true);                
            }
        }
    }
}