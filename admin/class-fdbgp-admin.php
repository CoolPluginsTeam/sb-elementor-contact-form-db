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
            add_action('admin_action_fdbgp_create_elementor_page', array($this, 'redirect_to_elementor_builder'));
        }

        /**
         * Create a new page and redirect to Elementor Editor
         */
        public function redirect_to_elementor_builder() {
            if ( ! current_user_can( 'edit_pages' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            $post_data = array(
                'post_title'  => 'New Elementor Form',
                'post_type'   => 'page',
                'post_status' => 'draft',
            );
            
            $post_id = wp_insert_post($post_data);
            
            if($post_id && !is_wp_error($post_id)){
                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
                
                // Redirect to Elementor Editor
                $redirect_url = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
                wp_redirect($redirect_url);
                exit;
            }
            
            wp_redirect(admin_url('post-new.php?post_type=page'));
            exit;
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
                            <img src="<?php echo esc_url(FDBGP_PLUGIN_URL . 'assets/images/formsDB-logo.svg'); ?>" alt="Cool FormKit Logo">
                        </a>
                    </div>
                    <div class="fdbgp-header-buttons">
                        <a href="https://coolformkit.com/features/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=demo&utm_content=setting_page_header" class="button button-secondary" target="_blank"><?php esc_html_e('Advanced Form Builder Demos', 'elementor-contact-form-db'); ?></a>
                    </div>
                </div>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=formsdb&tab=forms-sheets" class="nav-tab <?php echo $tab == 'forms-sheets' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Sheet', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=post-type" class="nav-tab <?php echo $tab == 'post-type' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Post Type', 'elementor-contact-form-db'); ?></a>
                    <?php if(is_plugin_active( 'hello-plus/hello-plus.php' )): ?>
                        <a href="?page=cfkef-entries" class="nav-tab <?php echo $tab == 'cfkef-entries' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hello+ Form Entries', 'elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                    <a href="?page=formsdb&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=advanced" class="nav-tab <?php echo $tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced Fields', 'elementor-contact-form-db'); ?></a>
                </h2>
                <div class="tab-content">
                    <?php
                    switch ($tab) {
                        case 'settings':
                            include_once 'views/settings.php';
                            break;
                        case 'post-type':
                            include_once 'views/form-to-posttype.php';
                            break;
                        case 'forms-sheets':
                            include_once 'views/form-to-sheet.php';
                            break;
                        case 'advanced':
                            include_once 'views/advanced-fields.php';
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

            if (isset($_GET['page']) && (strpos($_GET['page'], 'formsdb') !== false || strpos($_GET['page'], 'cfkef-entries') !== false)) {
                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                wp_enqueue_style('dashicons');

                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true); 
            }
        }
    }
}