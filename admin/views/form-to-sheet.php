<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FDBGP_Form_To_Sheet_Settings {

    /**
     * Render settings UI
     */
    public function __construct(){
        $forms = $this->get_all_forms();
        $this->render_page($forms);
    }

    /**
     * Render page
     *
     * @param array $forms
     */
    private function render_page( array $forms ) {
        ?>
        <div class='status-wrapper'>
        <?php
        echo '<h2>' . esc_html__( 'Forms with "Save Submissions in Google Sheet" Action', 'elementor-contact-form-db' ) . '</h2>';
        if ( ! empty( $forms ) ) {
            $this->render_forms_table( $forms );
        } else {
            $this->render_empty_state();
        }
        ?>
        </div>
        <?php
    }


    private function render_google_sheets_sidebar() {
        ?>
        <div class="cool-formkit-right-side-info-bar">
            <div class="notice notice-info">
                <h3><?php esc_html_e('How to use Save Submissions in Google Sheet', 'elementor-contact-form-db'); ?></h3>
                <ol>
                    <li>
                        <?php esc_html_e('Configure Google API', 'elementor-contact-form-db'); ?>
                        <a href="admin.php?page=formsdb&tab=settings">
                            <?php esc_html_e('Here', 'elementor-contact-form-db'); ?>
                        </a>
                    </li>
                    <li><?php esc_html_e('Create a page with Elementor', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Use "Save Submissions in Google Sheet" in Action After Submit', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Select Spreadsheet, sheet headers and sheet tab name', 'elementor-contact-form-db'); ?></li>
                    <li><?php esc_html_e('Update the sheet', 'elementor-contact-form-db'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }


    /**
     * Render table of forms
     *
     * @param array $forms
     */
    private function render_forms_table( array $forms ) {
        ?>
            <div class="cool-formkit-setting-table-con">

                <div class="cool-formkit-left-side-setting">
                    <?php
                    echo '<table class="widefat striped">';
                    echo '<thead>
                            <tr>
                                <th>' . esc_html__( 'Form Name', 'elementor-contact-form-db' ) . '</th>
                                <th>' . esc_html__( 'Page Title', 'elementor-contact-form-db' ) . '</th>
                                <th>' . esc_html__( 'Google Sheet', 'elementor-contact-form-db' ) . '</th>
                                <th>' . esc_html__( 'Action', 'elementor-contact-form-db' ) . '</th>
                            </tr>
                        </thead><tbody>';

                    foreach ( $forms as $form ) {
                        $sheet_status = '<span>❌</span>';
                        if ( ! empty( $form['spreadsheet_url'] ) ) {
                            $sheet_status = '<a href="' . esc_url( $form['spreadsheet_url'] ) . '" target="_blank" class="button button-secondary">
                                <span>✅</span> ' . esc_html__( 'View Sheet', 'elementor-contact-form-db' ) . '
                            </a>';
                        }

                        echo '<tr>
                                <td>' . esc_html( $form['form_name'] ) . '</td>
                                <td>' . esc_html( $form['post_title'] ) . '</td>
                                <td>' . $sheet_status . '</td>
                                <td>
                                    <a class="button button-primary" href="' . esc_url( $form['edit_url'] ) . '" target="_blank">
                                        ' . esc_html__( 'Edit Page', 'elementor-contact-form-db' ) . '
                                    </a>
                                </td>
                            </tr>';
                    }

                    echo '</tbody></table>';
                    ?>
                </div>

                <?php $this->render_google_sheets_sidebar(); ?>

            </div>
        <?php
    }


    /**
     * Render empty state
     */
    private function render_empty_state() {

        $create_form_url = admin_url( 'post-new.php?post_type=page' );
        ?>
        <div class="cool-formkit-setting-table-con">

            <div class="cool-formkit-left-side-setting">

                <p>
                    <?php esc_html_e(
                        'No Elementor form is using the "Save Submissions in Google Sheet" action.',
                        'elementor-contact-form-db'
                    ); ?>
                </p>

                <p>
                    <a class="button button-primary" href="<?php echo esc_url( $create_form_url ); ?>">
                        <?php esc_html_e( 'Create New Form', 'elementor-contact-form-db' ); ?>
                    </a>
                </p>

                <p class="description">
                    <?php esc_html_e(
                        'Create a new Elementor Form and enable the "Save Submissions in Google Sheet" action under Actions After Submit.',
                        'elementor-contact-form-db'
                    ); ?>
                </p>

            </div>

            <?php $this->render_google_sheets_sidebar(); ?>

        </div>
        <?php
    }


    /**
     * Get All Elementor forms
     *
     * @return array
     */
    private function get_all_forms() {

        $forms = [];

        $posts = get_posts( [
            'post_type'      => 'any',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_key'       => '_elementor_data',
        ] );

        foreach ( $posts as $post ) {

            $data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( empty( $data ) ) {
                continue;
            }

            $elements = json_decode( $data, true );
            if ( ! is_array( $elements ) ) {
                continue;
            }

            $this->walk_elements( $elements, $post, $forms );
        }

        return $forms;
    }

    /**
     * Recursive Elementor element walker
     *
     * @param array   $elements
     * @param WP_Post $post
     * @param array   $forms
     */
    private function walk_elements( array $elements, $post, array &$forms ) {

        foreach ( $elements as $element ) {
            $is_found = false;
            $spreadsheet_url = '';

            if ( isset( $element['widgetType'] ) && ( 'form' === $element['widgetType'] || 'ehp-form' === $element['widgetType'] ) ) {
                $is_found = true;
                
                // Check if Google Sheet action is enabled
                $submit_actions = [];
                if ( 'form' === $element['widgetType'] && ! empty( $element['settings']['submit_actions'] ) ) {
                    $submit_actions = $element['settings']['submit_actions'];
                } elseif ( 'ehp-form' === $element['widgetType'] && ! empty( $element['settings']['cool_formkit_submit_actions'] ) ) {
                    $submit_actions = $element['settings']['cool_formkit_submit_actions'];
                }

                if ( in_array( 'Save Submissions in Google Sheet', $submit_actions, true ) ) {
                    // Get Spreadsheet ID
                    $spreadsheet_id = '';
                    if ( ! empty( $element['settings']['fdbgp_spreadsheetid'] ) ) {
                        $spreadsheet_id = $element['settings']['fdbgp_spreadsheetid'];
                    }
                    
                    if ( ! empty( $spreadsheet_id ) && 'new' !== $spreadsheet_id ) {
                        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheet_id;
                    }
                }
            }

            if ($is_found) {
                $forms[] = [
                    'post_id'         => $post->ID,
                    'post_title'      => get_the_title( $post->ID ),
                    'form_name'       => $element['settings']['form_name'] ?? esc_html__( 'Unnamed Form', 'elementor-contact-form-db' ),
                    'edit_url'        => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
                    'widget_type'     => strtoupper($element['widgetType']),
                    'spreadsheet_url' => $spreadsheet_url,
                ];
            }

            if ( ! empty( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $post, $forms );
            }
        }
    }
}

new FDBGP_Form_To_Sheet_Settings();