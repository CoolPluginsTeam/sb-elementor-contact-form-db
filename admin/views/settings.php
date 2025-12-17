<?php
if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;


$instance_api = new FDBGP_Google_API_Functions();

// Get Google settings
$google_settings = get_option('fdbgp_google_settings', array(
    'client_id' => '',
    'client_secret' => '',
    'client_token' => ''
));

// Get site domain and redirect URI
$site_url = parse_url(site_url(), PHP_URL_HOST);
$site_domain = str_replace('www.', '', $site_url);
$redirect_uri = admin_url('admin.php?page=formsdb&tab=settings');

// Process form submission
$success_message = '';
$error_message = '';
$connection_status = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fdbgp_settings_nonce'])) {
    // Verify nonce
    if (wp_verify_nonce($_POST['fdbgp_settings_nonce'], 'fdbgp_settings_action')) {

        // Save Google Settings
        if (isset($_POST['save_google_settings'])) {
            $new_settings = array(
                'client_id' => sanitize_text_field($_POST['client_id'] ?? ''),
                'client_secret' => sanitize_text_field($_POST['client_secret'] ?? ''),
                'client_token' => sanitize_text_field($_POST['client_token'] ?? $google_settings['client_token'] ?? ''),
            );

            update_option('fdbgp_google_settings', $new_settings);
            $google_settings = $new_settings;
            $success_message = __('Settings saved successfully!', 'elementor-contact-form-db');
        }

        // Revoke Token
        if (isset($_POST['revoke_token'])) {
            $google_settings['client_token'] = '';
            update_option('fdbgp_google_settings', $google_settings);
            delete_option('fdbgp_google_access_token');
            $success_message = __('Token revoked successfully!', 'elementor-contact-form-db');
        }

        // Reset Settings
        if (isset($_POST['reset_google_settings'])) {
            delete_option('fdbgp_google_settings');
            delete_option('fdbgp_google_access_token');
            $google_settings = array('client_id' => '', 'client_secret' => '', 'client_token' => '');
            $success_message = __('Settings reset successfully!', 'elementor-contact-form-db');
        }
    } else {
        $error_message = __('Security check failed. Please try again.', 'elementor-contact-form-db');
    }
}

?>

<div class="fdbgp-settings-box">
    <div class="cfk-promo">
        <div class="cfk-box cfk-left">
            <form method="post" action="" class="cool-formkit-form">
                <div class="wrapper-header">
                    <div class="fdbgp-save-all">
                        <div class="fdbgp-title-desc">
                            <h2><?php esc_html_e('Cool FormKit Settings', 'elementor-contact-form-db'); ?></h2>
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
                                        placeholder="Enter your Client Secret"
                                        <?php echo !empty($google_settings['client_secret']) ? 'readonly' : ''; ?> />
                                    <p class="description"><?php esc_html_e('Enter your Google API Client Secret', 'elementor-contact-form-db'); ?></p>
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
                                        <?php if (empty($google_settings['client_token']) && !isset($_GET['code'])) : ?>
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
                        </table>
                    </div>
                </div>

                <hr>
                <div class="fdbgp-submit">
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



        <div class="cfk-right">
            <!-- Configuration Instructions -->
            <div class="notice notice-info">
                <h3><?php esc_html_e('Google API Configuration Instructions', 'elementor-contact-form-db'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Go to', 'elementor-contact-form-db'); ?>
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">
                            Google Cloud Console
                        </a>
                    </li>
                    <li><?php esc_html_e('Create a new project or select existing one', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Enable Google Sheets API and Google Drive API', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Create OAuth 2.0 credentials', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Add Authorized Domain:', 'elementor-contact-form-db'); ?>
                        <code id="auth-domain"><?php echo esc_html($site_domain); ?></code>
                        <button type="button" class="button button-small copy-btn" data-clipboard-target="#auth-domain">
                            <?php esc_html_e('Copy', 'elementor-contact-form-db'); ?>
                        </button>
                    </li>
                    <li><?php esc_html_e('Add Authorized Redirect URI:', 'elementor-contact-form-db'); ?>
                        <div class="cool-formkit-site-url-con"><code id="redirect-uri"><?php echo esc_url($redirect_uri); ?></code></div>
                        <button type="button" class="button button-small copy-btn" data-clipboard-target="#redirect-uri">
                            <?php esc_html_e('Copy', 'elementor-contact-form-db'); ?>
                        </button>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>
</div>