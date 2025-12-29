<?php
/**
 * Old Submission Tab View
 * 
 * Displays legacy form submissions from the old plugin version.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the helper class if not already loaded
if (!class_exists('FDBGP_Old_Submission')) {
    require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
}

class FDBGP_Old_Submission_View {

    private $per_page = 10;
    private $helper;

    public function __construct() {
        $this->helper = FDBGP_Old_Submission::get_instance();
        $this->render_page();
    }

    private function render_page() {
        $forms = $this->helper->get_submitted_pages();
        $form_ids = $this->helper->get_form_ids();
        $total_count = $this->helper->get_submission_count();
        ?>
        <div class='cfk-promo'>
            <div class="cfk-box cfk-left">
                <div class="wrapper-header">
                    <div class="cfkef-save-all">
                        <div class="cfkef-title-desc">
                            <h2><?php esc_html_e('Old Form Submissions', 'sb-elementor-contact-form-db'); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="wrapper-body">
                    <?php $this->render_content($forms, $form_ids, $total_count); ?>
                </div>
            </div>
            <?php $this->render_sidebar(); ?>
        </div>
        <?php
    }

    private function render_content($forms, $form_ids, $total_count) {
        ?>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">
                <div class="notice notice-warning" style="margin: 0 0 20px 0;">
                    <p>
                        <strong><?php esc_html_e('Legacy Data Notice:', 'sb-elementor-contact-form-db'); ?></strong>
                        <?php esc_html_e('This tab displays old form submissions from a previous plugin version. These submissions are read-only and cannot be modified. You can export this data to CSV format.', 'sb-elementor-contact-form-db'); ?>
                    </p>
                </div>

                <p>
                    <?php 
                    printf(
                        /* translators: %d: number of old form submissions */
                        esc_html__('You have %d old form submission(s) stored in the database.', 'sb-elementor-contact-form-db'),
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                        $total_count
                    ); 
                    ?>
                </p>

                <?php if ($total_count > 0): ?>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
                        <!-- Export by Page Submitted -->
                        <div style="flex: 1; min-width: 300px;">
                            <form method="POST">
                                <p><strong><?php esc_html_e('Export by Page Submitted', 'sb-elementor-contact-form-db'); ?></strong></p>
                                <select style="margin-right: 10px; width: 200px;" name="form_name">
                                    <?php 
                                    ksort($forms);
                                    foreach ($forms as $form => $label): 
                                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                        $selected = isset($_REQUEST['form_name']) && sanitize_text_field(wp_unslash($_REQUEST['form_name'])) == $form ? 'selected="selected"' : '';
                                    ?>
                                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <option <?php echo $selected; ?> value="<?php echo esc_attr($form); ?>">
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" name="preview_page" class="button-secondary" value="<?php esc_attr_e('Preview', 'sb-elementor-contact-form-db'); ?>" />
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
                            </form>
                        </div>

                        <!-- Export by Form ID -->
                        <?php if (!empty($form_ids)): ?>
                        <div style="flex: 1; min-width: 300px;">
                            <form method="POST">
                                <p><strong><?php esc_html_e('Export by Form ID', 'sb-elementor-contact-form-db'); ?></strong></p>
                                <select style="margin-right: 10px; width: 200px;" name="form_id">
                                    <?php 
                                    ksort($form_ids);
                                    foreach ($form_ids as $form): 
                                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                        $selected = isset($_REQUEST['form_id']) && $_REQUEST['form_id'] == $form ? 'selected="selected"' : '';
                                    ?>
                                    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <option <?php echo $selected; ?> value="<?php echo esc_attr($form); ?>">
                                            <?php echo esc_html($form); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" name="preview_form_id" class="button-secondary" value="<?php esc_attr_e('Preview', 'sb-elementor-contact-form-db'); ?>" />
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php $this->render_preview(); ?>

                <?php else: ?>
                    <p><?php esc_html_e('No old submissions found.', 'sb-elementor-contact-form-db'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_preview() {
        if (empty($_POST['fdbgp_old_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fdbgp_old_export_nonce'])), 'fdbgp_old_export')) {
            return; // Invalid or missing nonce - silently exit
        }
        
        if (isset($_REQUEST['preview_page']) && isset($_REQUEST['form_name'])) {
            $form_name = sanitize_text_field(wp_unslash($_REQUEST['form_name']));
            $rows = $this->helper->get_export_rows_by_page($form_name, 50);
            $this->render_preview_table($rows, 'form_name', $form_name, __('CSV Content (by Submitted Page)', 'sb-elementor-contact-form-db'));
        } elseif (isset($_REQUEST['preview_form_id']) && isset($_REQUEST['form_id'])) {
            $form_id = sanitize_text_field(wp_unslash($_REQUEST['form_id']));
            $rows = $this->helper->get_export_rows_by_form_id($form_id, 50);
            $this->render_preview_table($rows, 'form_id', $form_id, __('CSV Content (by Form ID)', 'sb-elementor-contact-form-db'));
        }
    }

    private function render_preview_table($rows, $field_name, $field_value, $title) {
        if (empty($rows)) {
            return;
        }
        ?>
        <div style="margin-top: 30px;">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php esc_html_e('Please review the data below and press "Download CSV File" to start the download. This preview shows up to 50 submissions. The export will include all submissions.', 'sb-elementor-contact-form-db'); ?></p>
            
            <div style="margin-top: 20px; min-height: 150px; max-height: 350px; overflow: auto; margin-bottom: 10px; border: 1px solid #ddd; padding: 20px; background: #f9f9f9; font-family: monospace; font-size: 12px;">
                <?php 
                foreach ($rows as $row) {
                    echo esc_html($row) . '<br />';
                }
                ?>
            </div>

            <form method="POST">
                <input type="hidden" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($field_value); ?>" />
                <input type="submit" name="download_old_csv" class="button-primary" value="<?php esc_attr_e('Download CSV File', 'sb-elementor-contact-form-db'); ?>" />
                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
            </form>
        </div>
        <?php
    }

    private function render_sidebar() {
        ?>
        <div class="fdbgp-card cfk-left">
            <h2 class="fdbgp-card-title">
                <span class="fdbgp-icon">📁</span> <?php esc_html_e('About Old Submissions', 'sb-elementor-contact-form-db'); ?>
            </h2>

            <div class="fdbgp-steps">
                <div class="fdbgp-step">
                    <div class="fdbgp-step-number">ℹ️</div>
                    <div class="fdbgp-step-content">
                        <h3><?php esc_html_e('Legacy Data', 'sb-elementor-contact-form-db'); ?></h3>
                        <p><?php esc_html_e('These are form submissions from a previous version of the plugin.', 'sb-elementor-contact-form-db'); ?></p>
                    </div>
                </div>

                <div class="fdbgp-step">
                    <div class="fdbgp-step-number">📤</div>
                    <div class="fdbgp-step-content">
                        <h3><?php esc_html_e('Export Options', 'sb-elementor-contact-form-db'); ?></h3>
                        <p><?php esc_html_e('Export your old data to CSV format for backup or migration to a new system.', 'sb-elementor-contact-form-db'); ?></p>
                    </div>
                </div>

                <div class="fdbgp-step">
                    <div class="fdbgp-step-number">🔒</div>
                    <div class="fdbgp-step-content">
                        <h3><?php esc_html_e('Read Only', 'sb-elementor-contact-form-db'); ?></h3>
                        <p><?php esc_html_e('New submissions are no longer saved in this format. Use Google Sheets or Post Type integration for new forms.', 'sb-elementor-contact-form-db'); ?></p>
                    </div>
                </div>

                <div class="fdbgp-step">
                    <div class="fdbgp-step-number">📝</div>
                    <div class="fdbgp-step-content">
                        <h3><?php esc_html_e('New Submissions', 'sb-elementor-contact-form-db'); ?></h3>
                        <p>
                            <?php 
                            printf(
                                /* translators: %s: link to Elementor Submissions tab */
                                esc_html__( 'Please visit the %s for your new form entries.', 'sb-elementor-contact-form-db' ),
                                '<a href="' . esc_url( admin_url( 'admin.php?page=e-form-submissions' ) ) . '" target="_blank">' . esc_html__( 'Elementor Submissions tab', 'sb-elementor-contact-form-db' ) . '</a>'
                            ); 
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="fdbgp-help-box">
                <h4><?php esc_html_e('NEED HELP?', 'sb-elementor-contact-form-db'); ?></h4>
                <ul>
                    <li><a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db" target="_blank">🎧 Contact Support</a></li>
                </ul>
            </div>
        </div>
        <?php
    }
}

new FDBGP_Old_Submission_View();
