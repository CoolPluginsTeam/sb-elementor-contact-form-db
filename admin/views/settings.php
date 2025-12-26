<?php
if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;

/**
 * Handle unchecked checkbox for usage share data.
 * 
 * This function is kept as a standalone function for backward compatibility
 * with AJAX calls that require it.
 */
if (!function_exists('fdbgp_handle_unchecked_checkbox')) {
    function fdbgp_handle_unchecked_checkbox() {
        $choice  = get_option('cpfm_opt_in_choice_cool_forms');
        $options = get_option('cfef_usage_share_data');

        if (!empty($choice)) {

            // If the checkbox is unchecked (value is empty, false, or null)
            if (empty($options)) {
                // formsDB
                wp_clear_scheduled_hook('fdbgp_extra_data_update');

                // conditional free
                if(method_exists('cfef_cronjob', 'cfef_send_data')){
                    wp_clear_scheduled_hook('cfef_extra_data_update');
                }

                // conditional pro
                if(method_exists('cfefp_cronjob', 'cfefp_send_data')){

                    wp_clear_scheduled_hook('cfefp_extra_data_update');
                }

                // country code
                if(method_exists('ccfef_cronjob', 'ccfef_send_data')){
                    wp_clear_scheduled_hook('ccfef_extra_data_update');
                }

                // form mask input
                if(method_exists('fme_cronjob', 'fme_send_data')){

                    wp_clear_scheduled_hook('fme_extra_data_update');
                }

                // input form mask
                if(method_exists('Mask_Form_Elementor\mfe_cronjob', 'mfe_send_data')){
                    wp_clear_scheduled_hook('mfe_extra_data_update');
                }

            }else {
                // formsDB
                if (!wp_next_scheduled('fdbgp_extra_data_update')) {
                    if (class_exists('fdbgp_cronjob') && method_exists('fdbgp_cronjob', 'fdbgp_send_data')) {
                        fdbgp_cronjob::fdbgp_send_data();
                    }
                    wp_schedule_event(time(), 'every_30_days', 'fdbgp_extra_data_update');
                }

                // conditional free
                if(method_exists('cfef_cronjob', 'cfef_send_data')){                    
                    if (!wp_next_scheduled('cfef_extra_data_update')) {
                        cfef_cronjob::cfef_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'cfef_extra_data_update');
                    }
                }

                // condition field pro
                if(method_exists('cfefp_cronjob', 'cfefp_send_data')){
                    if (!wp_next_scheduled('cfefp_extra_data_update')) {
                        cfefp_cronjob::cfefp_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'cfefp_extra_data_update');
                    }
                }

                // country code
                if(method_exists('ccfef_cronjob', 'ccfef_send_data')){
                    if (!wp_next_scheduled('ccfef_extra_data_update')) {
                        ccfef_cronjob::ccfef_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'ccfef_extra_data_update');
                    }
                }

                // form mask input
                if(method_exists('fme_cronjob', 'fme_send_data')){
                    if (!wp_next_scheduled('fme_extra_data_update')) {
                        fme_cronjob::fme_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'fme_extra_data_update');
                    }

                }

                // input form mask
                if(method_exists('Mask_Form_Elementor\mfe_cronjob', 'mfe_send_data')){
                    if (!wp_next_scheduled('mfe_extra_data_update')) {
                        wp_schedule_event(time(), 'every_30_days', 'mfe_extra_data_update');
                        Mask_Form_Elementor\mfe_cronjob::mfe_send_data();
                    }
                }
            }
        }
    }
}

/**
 * Settings Page Class
 * 
 * Handles the settings page functionality for FormsDB plugin.
 */
if (!class_exists('FDBGP_Settings_Page')) {

    class FDBGP_Settings_Page {

        private $api_instance;
        private $google_settings;
        private $oauth_code;
        private $site_domain;
        private $redirect_uri;
        private $success_message = '';
        private $error_message = '';

        /**
         * Constructor.
         */
        public function __construct() {
            $this->api_instance = new FDBGP_Google_API_Functions();
            $this->init_settings();
            $this->process_form_submission();
        }

        /**
         * Initialize settings.
         */
        private function init_settings() {
            $this->google_settings = get_option('fdbgp_google_settings', array(
                'client_id' => '',
                'client_secret' => '',
                'client_token' => ''
            ));

            $this->oauth_code = isset($_GET['code']) && !empty($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
            
            $site_url = parse_url(site_url(), PHP_URL_HOST);
            $this->site_domain = str_replace('www.', '', $site_url);
            $this->redirect_uri = admin_url('admin.php?page=formsdb&tab=settings');
        }

        /**
         * Process form submission.
         */
        private function process_form_submission() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['fdbgp_settings_nonce'])) {
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            if (!check_admin_referer('fdbgp_settings_action', 'fdbgp_settings_nonce')) {
                $this->error_message = __('Security check failed. Please try again.', 'elementor-contact-form-db');
                return;
            }

            $cfef_usage_share_data = isset($_POST['cfef_usage_share_data']) ? sanitize_text_field($_POST['cfef_usage_share_data']) : '';
            update_option("cfef_usage_share_data", $cfef_usage_share_data);

            fdbgp_handle_unchecked_checkbox();

            // Save Google Settings
            if (isset($_POST['save_google_settings'])) {
                $new_settings = array(
                    'client_id' => sanitize_text_field($_POST['client_id'] ?? ''),
                    'client_secret' => sanitize_text_field($_POST['client_secret'] ?? ''),
                    'client_token' => sanitize_text_field($_POST['client_token'] ?? $this->google_settings['client_token'] ?? ''),
                );

                update_option('fdbgp_google_settings', $new_settings);
                $this->google_settings = $new_settings;
                $this->success_message = __('Settings saved successfully!', 'elementor-contact-form-db');
            }

            // Revoke Token
            if (isset($_POST['revoke_token'])) {
                $this->google_settings['client_token'] = '';
                update_option('fdbgp_google_settings', $this->google_settings);
                delete_option('fdbgp_google_access_token');
                $this->success_message = __('Token revoked successfully!', 'elementor-contact-form-db');
            }

            // Reset Settings
            if (isset($_POST['reset_google_settings'])) {
                delete_option('fdbgp_google_settings');
                delete_option('fdbgp_google_access_token');
                $this->google_settings = array('client_id' => '', 'client_secret' => '', 'client_token' => '');
                $this->success_message = __('Settings reset successfully!', 'elementor-contact-form-db');
            }
        }

        /**
         * Render the settings page.
         */
        public function render() {
            $google_settings = $this->google_settings;
            $oauth_code = $this->oauth_code;
            $site_domain = $this->site_domain;
            $redirect_uri = $this->redirect_uri;
            $success_message = $this->success_message;
            $error_message = $this->error_message;
            $instance_api = $this->api_instance;
            ?>

            <div class="fdbgp-settings-box">
                <div class="cfk-promo">
                    <div class="cfk-box cfk-left">
                        <form method="post" action="" class="cool-formkit-form">
                            <div class="wrapper-header">
                                <div class="fdbgp-save-all">
                                    <div class="fdbgp-title-desc">
                                        <h2><?php esc_html_e('Settings', 'elementor-contact-form-db'); ?></h2>
                                    </div>
                                    <div class="fdbgp-save-controls">
                                        <button type="submit" name="save_google_settings" class="button button-primary">
                                            <?php esc_html_e('Save Changes', 'elementor-contact-form-db'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="wrapper-body">
                                <!-- Display messages -->
                                <?php if (!empty($success_message)) : ?>
                                    <div class="notice notice-success is-dismissible">
                                        <p><?php echo esc_html($success_message); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($error_message)) : ?>
                                    <div class="notice notice-error is-dismissible">
                                        <p><?php echo esc_html($error_message); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="cool-formkit-left-side-setting">
                                    <h3><?php esc_html_e('Google API Settings', 'elementor-contact-form-db'); ?></h3>
                                    <p class="description"><?php esc_html_e('Connect your Google account to sync form data with Google Sheets.', 'elementor-contact-form-db'); ?></p>

                                    <table class="form-table cool-formkit-table">
                                        <!-- Client ID -->
                                        <tr>
                                            <th class="cool-formkit-table-th">
                                                <label for="client_id" class="cool-formkit-label">
                                                    <?php esc_html_e('Client ID', 'elementor-contact-form-db'); ?>
                                                </label>
                                            </th>
                                            <td class="cool-formkit-table-td">
                                                <input type="text"
                                                    id="client_id"
                                                    name="client_id"
                                                    class="regular-text cool-formkit-input"
                                                    value="<?php echo esc_attr($google_settings['client_id']); ?>"
                                                    placeholder="Enter your Client ID"
                                                    <?php echo !empty($google_settings['client_id']) ? 'readonly' : ''; ?> />
                                                <p class="description"><?php esc_html_e('Enter your Google API Client ID', 'elementor-contact-form-db'); ?></p>
                                            </td>
                                        </tr>

                                        <!-- Client Secret -->
                                        <tr>
                                            <th class="cool-formkit-table-th">
                                                <label for="client_secret" class="cool-formkit-label">
                                                    <?php esc_html_e('Client Secret Key', 'elementor-contact-form-db'); ?>
                                                </label>
                                            </th>
                                            <td class="cool-formkit-table-td">
                                                <input type="text"
                                                    id="client_secret"
                                                    name="client_secret"
                                                    class="regular-text cool-formkit-input"
                                                    value="<?php echo esc_attr($google_settings['client_secret']); ?>"
                                                    placeholder="Enter your Client Secret key"
                                                    <?php echo !empty($google_settings['client_secret']) ? 'readonly' : ''; ?> />
                                                <p class="description"><?php esc_html_e('Enter your Google API Client Secret key', 'elementor-contact-form-db'); ?></p>
                                            </td>
                                        </tr>

                                        <!-- Authentication Token -->
                                        <?php if (!empty($google_settings['client_id']) && !empty($google_settings['client_secret'])) : ?>
                                            <tr>
                                                <th class="cool-formkit-table-th">
                                                    <label for="client_token" class="cool-formkit-label">
                                                        <?php esc_html_e('Authentication Token', 'elementor-contact-form-db'); ?>
                                                    </label>
                                                </th>
                                                <td class="cool-formkit-table-td">
                                                    <?php if (empty($google_settings['client_token']) && empty($oauth_code)) : ?>
                                                        <?php $auth_url = $instance_api->getClient(); ?>
                                                        <div id="authbtn" style="margin-bottom: 10px;">
                                                            <a href="<?php echo esc_url($auth_url); ?>" id="authlink" target="_blank" class="button button-secondary">
                                                                <?php esc_html_e('Generate Authentication Token', 'elementor-contact-form-db'); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div id="authtext">
                                                        <input type="text"
                                                            id="client_token"
                                                            name="client_token"
                                                            class="regular-text cool-formkit-input"
                                                            value="<?php echo esc_attr($google_settings['client_token']); ?>"
                                                            placeholder="Authentication token will appear here"
                                                            <?php echo !empty($google_settings['client_token']) ? 'readonly' : ''; ?> />
                                                        <?php if (!empty($google_settings['client_token'])) : ?>
                                                            <p class="description">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                                                    <path fill="#11ec38" d="M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8s8-3.58 8-8s-3.58-8-8-8m-.615 12.66h-1.34l-3.24-4.54l1.341-1.25l2.569 2.4l5.141-5.931l1.34.94z" />
                                                                </svg>
                                                                <?php esc_html_e('Token is configured', 'elementor-contact-form-db'); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($google_settings['client_token'])) : ?>
                                                        <input type="submit" name="revoke_token" class="revoke-button revoke-button-secondary"
                                                            value="<?php esc_html_e('Revoke Token', 'elementor-contact-form-db'); ?>"
                                                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to revoke the token?', 'elementor-contact-form-db'); ?>');">
                                                        </input>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php $cpfm_opt_in = get_option('cpfm_opt_in_choice_cool_forms','');
                                            if ($cpfm_opt_in) {
                                                $check_option =  get_option( 'cfef_usage_share_data','');
                                                if($check_option == 'on'){
                                                    $checked = 'checked';
                                                }else{
                                                    $checked = '';
                                                }                
                                                ?>            
                                                <tr>
                                                    <th scope="row" class="cool-formkit-table-th">
                                                        <label for="cfef_usage_share_data" class="usage-share-data-label"><?php esc_html_e('Help Improve Plugins', 'cool-formkit'); ?></label>
                                                    </th>
                                                    <td class="cool-formkit-table-td usage-share-data">
                                                        <input type="checkbox" id="cfef_usage_share_data" name="cfef_usage_share_data" value="on" <?php echo $checked ?>  class="regular-text cool-formkit-input"  />
                                                        <div class="description cool-formkit-description">
                                                            <?php esc_html_e('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'ccpw'); ?>
                                                            <a href="#" class="fdbgp-ccpw-see-terms">[<?php esc_html_e('See terms', 'ccpw'); ?>]</a>
                        
                                                            <div id="termsBox" style="display: none; padding-left: 20px; margin-top: 10px; font-size: 12px; color: #999;">
                                                                <p>
                                                                    <?php esc_html_e('Opt in to receive email updates about security improvements, new features, helpful tutorials, and occasional special offers. We\'ll collect:', 'ccpw'); ?>
                                                                    <a href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank">Click Here</a>

                                                                </p>
                                                                <ul style="list-style-type: auto;">
                                                                    <li><?php esc_html_e('Your website home URL and WordPress admin email.', 'ccpw'); ?></li>
                                                                    <li><?php esc_html_e('To check plugin compatibility, we will collect the following: list of active plugins and themes, server type, MySQL version, WordPress version, memory limit, site language and database prefix.', 'ccpw'); ?></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php } ?>

                                    </table>
                                </div>

                                <?php wp_nonce_field('fdbgp_settings_action', 'fdbgp_settings_nonce'); ?>
                                <button type="submit" name="save_google_settings" class="button button-primary">
                                    <?php esc_html_e('Save Settings', 'elementor-contact-form-db'); ?>
                                </button>
                                <?php if (!empty($google_settings['client_id']) || !empty($google_settings['client_secret'])) : ?>
                                    <button type="submit" name="reset_google_settings" class="button button-secondary"
                                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all Google API settings?', 'elementor-contact-form-db'); ?>');">
                                        <?php esc_html_e('Reset Google Settings', 'elementor-contact-form-db'); ?>
                                    </button>
                                <?php endif; ?>

                            </div>

                        </form>
                    </div>



                    <div class="fdbgp-card">
                        <h2 class="fdbgp-card-title">
                            <span class="fdbgp-icon">🎓</span> Google API Configuration Instructions
                        </h2>

                        <div class="fdbgp-steps">
                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">1</div>
                                <div class="fdbgp-step-content">
                                    <h3>Go to</h3>
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console →</a>
                                </div>
                            </div>

                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">2</div>
                                <div class="fdbgp-step-content">
                                    <h3>Create project</h3>
                                    <p>Create a new project or select existing one.</p>
                                </div>
                            </div>

                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">3</div>
                                <div class="fdbgp-step-content">
                                    <h3>Google API</h3>
                                    <p>Enable Google Sheets API and Google Drive API.</p>
                                </div>
                            </div>

                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">4</div>
                                <div class="fdbgp-step-content">
                                    <h3>Add Authorized Domain</h3>
                                    <code id="auth-domain"><?php echo esc_html($site_domain); ?></code>
                                    <button type="button" class="button button-small copy-btn" data-clipboard-target="#auth-domain">
                                        <?php esc_html_e('Copy', 'elementor-contact-form-db'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">5</div>
                                <div class="fdbgp-step-content">
                                    <h3>Add Authorized Redirect URI</h3>
                                    <code id="redirect-uri"><?php echo esc_url($redirect_uri); ?></code>
                                    <button type="button" class="button button-small copy-btn" data-clipboard-target="#redirect-uri">
                                        <?php esc_html_e('Copy', 'elementor-contact-form-db'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="fdbgp-step">
                                <div class="fdbgp-step-number">6</div>
                                <div class="fdbgp-step-content">
                                    <h3>Google OAuth</h3>
                                    <p>Create OAuth 2.0 credentials.</p>
                                </div>
                            </div>

                        </div>

                        <div class="fdbgp-help-box">
                            <h4>NEED HELP?</h4>
                            <ul>
                                <li><a href="https://docs.coolplugins.net/doc/formsdb-video-tutorials/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar#how-to-generate-a-google-api-authentication-token" target="_blank" rel="noopener noreferrer">▶ Watch Video Tutorial</a></li>
                                <li><a href="https://docs.coolplugins.net/doc/google-api-setup-connect-elementor-google-sheets/?utm_source=fdbgp_plugin&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar#how-to-generate-a-google-api-authentication-token" target="_blank" rel="noopener noreferrer">📄 Read Documentation</a></li>
                                <li><a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db" target="_blank">🎧 Contact Support</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <?php
        }
    }
}