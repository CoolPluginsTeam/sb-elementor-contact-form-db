<?php
if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice;

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

            add_action('cpfm_register_notice', function () { 
                if (!class_exists('Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
                    return;
                }

                $notice = [
                    'title' => __('Elementor Form Addons by Cool Plugins', 'cool-formkit-for-elementor-forms'),
                    'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'cool-plugins-feedback'),
                    'pages' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings','formsdb'],
                    'always_show_on' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings','formsdb'], // This enables auto-show
                    'plugin_name'=>'fdbgp'
                ];

                CPFM_Feedback_Notice::cpfm_register_notice('cool_forms', $notice);

                    if (!isset($GLOBALS['cool_plugins_feedback'])) {
                        $GLOBALS['cool_plugins_feedback'] = [];
                    }
                    
                    $GLOBALS['cool_plugins_feedback']['cool_forms'][] = $notice;
            
            });
        
        add_action('cpfm_after_opt_in_fdbgp', function($category) {
            
                if ($category === 'cool_forms') {

                    require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cron/fdbgp-class-cron.php';

                    // Set the usage share data option to 'on'
                    update_option( 'cfef_usage_share_data', 'on' );

                    // Send initial data for this plugin
                    fdbgp_cronjob::fdbgp_send_data();

                    // Schedule crons for all form plugins
                    // Include the settings file where fdbgp_handle_unchecked_checkbox is defined
                    if (!function_exists('fdbgp_handle_unchecked_checkbox')) {
                        require_once FDBGP_PLUGIN_DIR . 'admin/views/settings.php';
                    }
                    
                    // Only schedule crons if function exists
                    if (function_exists('fdbgp_handle_unchecked_checkbox')) {
                        fdbgp_handle_unchecked_checkbox();
                    }
                } 
        });
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
            // Check if conflicting plugins are active
            $is_conflicting_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) 
                || is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
            
            if ( $is_conflicting_active ) {
                add_submenu_page(
                    'elementor',
                    __('FormsDB', 'elementor-contact-form-db'),
                    __('↳ FormsDB', 'elementor-contact-form-db'),
                    'manage_options',
                    'formsdb',
                    array($this, 'display_plugin_admin_page'),
                    18 // Position after cool-formkit (which is at default position)
                );
            } else {
                // Add as submenu under elementor (default behavior)
                add_submenu_page(
                    'elementor',
                    __('FormsDB', 'elementor-contact-form-db'),
                    __('FormsDB', 'elementor-contact-form-db'),
                    'manage_options',
                    'formsdb',
                    array($this, 'display_plugin_admin_page')
                );
            }
        }

        public function display_plugin_admin_page() {
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'forms-sheets';
            
            // Check for old submissions
            if (!class_exists('FDBGP_Old_Submission')) {
                require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
            }
            $has_old_submissions = FDBGP_Old_Submission::has_old_submissions();
            
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
                    <?php
                    if (
                        is_plugin_active( 'hello-plus/hello-plus.php' ) &&
                        ! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) &&
                        ! is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' )
                    ) :
                    ?>
                        <a href="?page=cfkef-entries" class="nav-tab <?php echo $tab == 'cfkef-entries' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hello+ Form Entries', 'elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                    <a href="?page=formsdb&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'elementor-contact-form-db'); ?></a>
                    <?php
                    if (! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )) :
                    ?>
                        <a href="?page=formsdb&tab=advanced" class="nav-tab <?php echo $tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced Fields', 'elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                    <?php if ($has_old_submissions) : ?>
                        <a href="?page=formsdb&tab=old-submission" class="nav-tab <?php echo $tab == 'old-submission' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Old Submissions', 'elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
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
                        case 'old-submission':
                            include_once 'views/old-submission.php';
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
            $is_conflicting_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) || is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
            if(!$is_conflicting_active){
                wp_enqueue_style('fdbgp-admin-global-style', FDBGP_PLUGIN_URL . 'assets/css/global-admin-style.css', array(), $this->version, 'all');
            }else{
                ?>
                <style>
                    li a[href="admin.php?page=formsdb"] {
                        padding-left: 10px;
                        font-style: italic;
                        opacity: 0.85;
                    }
                </style>
                <?php
            }

            $screen = get_current_screen();
            if ( $screen && 'elementor_page_e-form-submissions' === $screen->id ) {
                $button_text = __('Save Form Submissions To Google Sheet', 'elementor-contact-form-db');
                $button_url = admin_url('admin.php?page=formsdb');
                
                $custom_js = "
                    jQuery(document).ready(function($) {
                        var button = '<a href=\"{$button_url}\" target=\"_blank\" class=\"button button-primary\">{$button_text}</a>';
                        $('#e-form-submissions .e-form-submissions-search').prepend(button);
                    });
                ";
                wp_add_inline_script('jquery-core', $custom_js);
            }

            if (isset($_GET['page']) && (strpos($_GET['page'], 'formsdb') !== false || strpos($_GET['page'], 'cfkef-entries') !== false)) {
                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                wp_enqueue_style('dashicons');

                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true); 
            }else if(isset($_GET['page']) && (strpos($_GET['page'], 'cool-formkit') !== false)){
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true); 
            }
        }
    }
}