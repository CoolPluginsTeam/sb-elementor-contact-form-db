<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FDBGP_Form_To_Sheet_Settings {

    /**
     * Render settings UI
     */
    private $per_page = 10;

    /**
     * Render settings UI
     */
    public function __construct() {
        $forms = $this->get_all_forms();
        
        $total_items = count( $forms );
        $current_page = $this->get_current_page();
        $offset = ( $current_page - 1 ) * $this->per_page;
        
        $forms_to_show = array_slice( $forms, $offset, $this->per_page );
        $this->render_page( $forms_to_show, $total_items, $current_page );
    }

    private function get_current_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- get the current page, no data modification.
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        return max( 1, $paged );
    }

    private function get_spreadsheet_tab_url( $spreadsheet_id, $sheet_title ) {
        if ( empty( $spreadsheet_id ) || empty( $sheet_title ) ) {
            return '';
        }

        try {
            $api = new \Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions();
            $client = $api->getClient();
            
            if(gettype($client) === 'string' || gettype($client) === 'boolean'){
                return '';
            }

            $service = new \Google_Service_Sheets( $client );
            $spreadsheet = $service->spreadsheets->get( $spreadsheet_id );

            foreach ( $spreadsheet->getSheets() as $sheet ) {
                $properties = $sheet->getProperties();

                if ( $properties->getTitle() === $sheet_title ) {
                    $sheet_id = $properties->getSheetId();

                    return sprintf(
                        'https://docs.google.com/spreadsheets/d/%s/edit#gid=%d',
                        $spreadsheet_id,
                        $sheet_id
                    );
                }
            }

        } catch ( \Exception $e ) {
            // Optional: log error for debugging
        }

        return '';
    }

    /**
     * Render page
     *
     * @param array $forms
     * @param int $total_items
     * @param int $current_page
     */
    private function render_page( array $forms, $total_items = 0, $current_page = 1 ) {
        ?>
        <div class='cfk-promo'>
            <div class="cfk-box cfk-left">
                <div class="wrapper-header">
                    <div class="cfkef-save-all">
                        <div class="cfkef-title-desc">
                            <h2><?php esc_html_e( 'Save Form Submissions to Google Sheets', 'sb-elementor-contact-form-db' ); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="wrapper-body">
                    <?php
                    if ( ! empty( $forms ) || $total_items > 0 ) {
                        $this->render_forms_table( $forms, $total_items, $current_page );
                    } else {
                        $this->render_empty_state();
                    }
                    ?>
                </div>
            </div>
            <?php $this->render_google_sheets_sidebar(); ?>
        </div>
        <?php
    }


    private function render_google_sheets_sidebar() {
        ?>
            <div class="fdbgp-card cfk-left">
                <h2 class="fdbgp-card-title">
                    <span class="fdbgp-icon">🎓</span> How to use
                </h2>

                <div class="fdbgp-steps">
                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">1</div>
                    <div class="fdbgp-step-content">
                        <h3>Configure Google API</h3>
                        <p>Navigate to the Settings tab and authenticate your Google Account.</p>
                        <a href="admin.php?page=formsdb&tab=settings">Go to Settings →</a>
                    </div>
                    </div>

                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">2</div>
                    <div class="fdbgp-step-content">
                        <h3>Edit your Form</h3>
                        <p>Open your page in Elementor and select your form widget.</p>
                    </div>
                    </div>

                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">3</div>
                    <div class="fdbgp-step-content">
                        <h3>Add Action</h3>
                        <p>Under <strong>'Actions After Submit'</strong>, add <strong>Save Submissions in Google Sheet</strong>.</p>
                    </div>
                    </div>

                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">4</div>
                    <div class="fdbgp-step-content">
                        <h3>Map Fields</h3>
                        <p>Select your spreadsheet and map form fields to columns.</p>
                    </div>
                    </div>
                </div>

                <div class="fdbgp-help-box">
                    <h4>NEED HELP?</h4>
                    <ul>
                    <li><a href="https://docs.coolplugins.net/doc/formsdb-video-tutorials/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer">▶ Watch Video Tutorial</a></li>
                    <li><a href="https://docs.coolplugins.net/doc/sync-form-submissions-google-sheets/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer">📄 Read Documentation</a></li>
                    <li><a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db" target="_blank">🎧 Contact Support</a></li>
                    </ul>
                </div>
            </div>
        <?php
    }


    /**
     * Render table of forms
     *
     * @param array $forms
     * @param int   $total_items
     * @param int   $current_page
     */
    private function render_forms_table( array $forms, $total_items, $current_page ) {
        ?>
        <p><?php esc_html_e( 'View all your Elementor forms here and manage their connection with Google Sheets. Automatically send new form submissions to your selected Google Sheet.', 'sb-elementor-contact-form-db' ); ?></p>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">
                <?php
                echo '<table class="widefat striped">';
                echo '<thead>
                        <tr>
                            <th>' . esc_html__( 'Form Name', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Page Title', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Status', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Google Sheet', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Action', 'sb-elementor-contact-form-db' ) . '</th>
                        </tr>
                    </thead><tbody>';

                foreach ( $forms as $form ) {
                    $sheet_status = '<a href="' . esc_url( $form['edit_url'] ) . '" target="_blank" class="button button-secondary">
                            <span>❌</span> ' . esc_html__( 'Connect Sheet', 'sb-elementor-contact-form-db' ) . '
                        </a>';

                    if ( ! empty( $form['spreadsheet_url'] ) ) {
                        $sheet_status = '<a href="' . esc_url( $form['spreadsheet_url'] ) . '" target="_blank" class="button button-secondary">
                            <span>✅</span> ' . esc_html__( 'View Sheet', 'sb-elementor-contact-form-db' ) . '
                        </a>';
                    }

                    echo '<tr>
                            <td>' . esc_html( $form['form_name'] ) . '</td>
                            <td><a href="' . esc_url( $form['frontend_url'] ) . '" target="_blank">' . esc_html( $form['post_title'] ) . '</a></td>
                            <td>' . ( $form['status'] ? '<span style="color:green;">Enabled</span>' : '<span style="color:red;">Disabled</span>') . '</td>
                            <td>' . wp_kses_post($sheet_status) . '</td>
                            <td>
                                <a class="button button-primary" href="' . esc_url( $form['edit_url'] ) . '" target="_blank">
                                    ' . esc_html__( 'Edit Form', 'sb-elementor-contact-form-db' ) . '
                                </a>
                            </td>
                        </tr>';
                }

                echo '</tbody></table>';
                $this->render_pagination( $total_items, $current_page );
                ?>
            </div>

        </div>
        <?php
    }


    /**
     * Render empty state
     */
    private function render_empty_state() {

        $create_form_url = admin_url( 'admin.php?action=fdbgp_create_elementor_page' );
        ?>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">

                <p>
                    <?php esc_html_e(
                        'No Elementor form is using the "Save Submissions in Google Sheet" action.',
                        'sb-elementor-contact-form-db'
                    ); ?>
                </p>

                <p>
                    <a class="button button-primary" href="<?php echo esc_url( $create_form_url ); ?>" target="_blank">
                        <?php esc_html_e( 'Create New Form', 'sb-elementor-contact-form-db' ); ?>
                    </a>
                </p>

                <p class="description">
                    <?php esc_html_e(
                        'Create a new Elementor Form and enable the "Save Submissions in Google Sheet" action under Actions After Submit.',
                        'sb-elementor-contact-form-db'
                    ); ?>
                </p>

            </div>

        </div>
        <?php
    }


    /**
     * Get All Elementor forms
     *
     * @return array
     */
    /**
     * Get All Elementor forms
     *
     * @return array
     */
    private function get_all_forms() {
        
        // Try to get from cache first
        $cached_forms = get_transient( 'fdbgp_forms_sheet_data' );
        if ( false !== $cached_forms ) {
            return $cached_forms;
        }

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

        // Cache the result for 24 hours (will be flushed on save)
        set_transient( 'fdbgp_forms_sheet_data', $forms, DAY_IN_SECONDS );

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

                    if ( ! empty( $element['settings']['fdbgp_sheet_list'] ) ) {
                        $sheet_title = $element['settings']['fdbgp_sheet_list'];
                    }else{
                        $sheet_title = '';
                    }                    
                    if ( ! empty( $spreadsheet_id ) && 'new' !== $spreadsheet_id ) {
                        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheet_id;
                    }

                    $spreadsheet_url = $this->get_spreadsheet_tab_url(
                        $spreadsheet_id,
                        $sheet_title
                    );
                }
            }

            if ($is_found) {
                $forms[] = [
                    'post_id'         => $post->ID,
                    'post_title'      => get_the_title( $post->ID ),
                    'frontend_url'    => get_permalink( $post->ID ),
                    'form_name'       => $element['settings']['form_name'] ?? esc_html__( 'Unnamed Form', 'sb-elementor-contact-form-db' ),
                    'edit_url'        => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
                    'widget_type'     => strtoupper($element['widgetType']),
                    'spreadsheet_url' => $spreadsheet_url,
                    'status' => in_array( 'Save Submissions in Google Sheet', $submit_actions, true ),
                ];
            }

            if ( ! empty( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $post, $forms );
            }
        }
    }

    /**
     * Render pagination
     *
     * @param int $total_items
     * @param int $current_page
     */
    private function render_pagination( $total_items, $current_page ) {
        $total_pages = ceil( $total_items / $this->per_page );

        if ( $total_pages > 1 ) {
            $pagination_args = [
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ];

            echo '<div class="formsdb-tablenav bottom"><div class="tablenav-pages" style="margin: 1em 0;">';
            echo wp_kses_post(paginate_links( $pagination_args ));
            echo '</div></div>';
        }
    }
}

new FDBGP_Form_To_Sheet_Settings();