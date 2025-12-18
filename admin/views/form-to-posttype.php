<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FDBGP_Form_To_Post_Settings {

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
        echo '<h2>' . esc_html__( 'Forms with "Save Submissions In Post Type" Action', 'elementor-contact-form-db' ) . '</h2>';
        echo '<p>' . esc_html__( 'This section allows you to manage forms that are configured to save submissions as custom post types. You can view and edit the settings for each form here.', 'elementor-contact-form-db' ) . '</p>';
        if ( ! empty( $forms ) ) {
            $this->render_forms_table( $forms );
        } else {
            $this->render_empty_state();
        }
        ?>
        </div>
        <?php
    }
    
    /**
     * Render page
     *
     * @param array $forms
     */


    private function render_right_sidebar() {
        ?>


            <div class="fdbgp-card">
                <h2 class="fdbgp-card-title">
                    <span class="fdbgp-icon">🎓</span> How to use
                </h2>

                <div class="fdbgp-steps">
                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">1</div>
                    <div class="fdbgp-step-content">
                        <h3>Create Page</h3>
                        <p>Create a page with Elementor</p>
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
                        <p>Under <strong>'Actions After Submit'</strong>, add <strong>Save Submissions In Post Type</strong>.</p>
                    </div>
                    </div>

                    <div class="fdbgp-step">
                    <div class="fdbgp-step-number">4</div>
                    <div class="fdbgp-step-content">
                        <h3>Post Type</h3>
                        <p>Select post type and post status in settings.</p>
                    </div>
                    </div>
                </div>

                <div class="fdbgp-help-box">
                    <h4>NEED HELP?</h4>
                    <ul>
                    <li>▶ Watch Video Tutorial</li>
                    <li>📄 Read Documentation</li>
                    <li>🎧 Contact Support</li>
                    </ul>
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
                                <th>' . esc_html__( 'Status', 'elementor-contact-form-db' ) . '</th>
                                <th>' . esc_html__( 'Post Type', 'elementor-contact-form-db' ) . '</th>
                                <th>' . esc_html__( 'Action', 'elementor-contact-form-db' ) . '</th>
                            </tr>
                        </thead><tbody>';

                    foreach ( $forms as $form ) {
                        $post_type_status = '<a href="' . esc_url( $form['edit_url'] ) . '" target="_blank" class="button button-secondary">
                                <span>❌</span> ' . esc_html__( 'Connect Post Type', 'elementor-contact-form-db' ) . '
                            </a>';

                        if ( ! empty( $form['post_type_url'] ) && ! empty( $form['post_type_label'] ) ) {
                            $post_type_status = '<a href="' . esc_url( $form['post_type_url'] ) . '" target="_blank" class="button button-secondary">
                                <span>✅</span> ' . esc_html( $form['post_type_label'] ) . '
                            </a>';
                        }
                        
                        echo '<tr>
                                <td>' . esc_html( $form['form_name'] ) . '</td>
                                <td><a href="' . esc_url( $form['frontend_url'] ) . '" target="_blank">' . esc_html( $form['post_title'] ) . '</a></td>
                                <td>' . ( $form['status'] ? '<span style="color:green;">Enabled</span>' : '<span style="color:red;">Disabled</span>') . '</td>
                                <td>' . $post_type_status . '</td>
                                <td>
                                    <a class="button button-primary" href="' . esc_url( $form['edit_url'] ) . '" target="_blank">
                                        ' . esc_html__( 'Edit Form', 'elementor-contact-form-db' ) . '
                                    </a>
                                </td>
                            </tr>';
                    }

                    echo '</tbody></table>';
                    ?>
                </div>

                <?php $this->render_right_sidebar(); ?>
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

                <p><?php esc_html_e(
                    'No Elementor form is using the "Save Submissions In Post Type" action.',
                    'elementor-contact-form-db'
                ); ?></p>

                <p>
                    <a class="button button-primary" href="<?php echo esc_url( $create_form_url ); ?>">
                        <?php esc_html_e( 'Create New Form', 'elementor-contact-form-db' ); ?>
                    </a>
                </p>

                <p class="description">
                    <?php esc_html_e(
                        'Create a new Elementor Form and enable the "Save Submissions In Post Type" action under Actions After Submit.',
                        'elementor-contact-form-db'
                    ); ?>
                </p>

            </div>

            <?php $this->render_right_sidebar(); ?>
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
            $post_type_url = '';
            $post_type_label = '';

            if ( isset( $element['widgetType'] ) && ( 'form' === $element['widgetType'] || 'ehp-form' === $element['widgetType'] ) ) {
                $is_found = true;
                
                // Check if Register Post action is enabled
                $submit_actions = [];
                if ( 'form' === $element['widgetType'] && ! empty( $element['settings']['submit_actions'] ) ) {
                    $submit_actions = $element['settings']['submit_actions'];
                } elseif ( 'ehp-form' === $element['widgetType'] && ! empty( $element['settings']['cool_formkit_submit_actions'] ) ) {
                    $submit_actions = $element['settings']['cool_formkit_submit_actions'];
                }

                if ( in_array( 'eef-register-post', $submit_actions, true ) ) {
                    // Get Post Type
                    if ( ! empty( $element['settings'] ) ) {
                        $post_type_slug = $element['settings']['eef-register-post-post-type'] ?? 'post';
                        $post_type_obj = get_post_type_object( $post_type_slug );      
                        
                        if ( $post_type_obj ) {
                            $post_type_label = $post_type_obj->labels->name;
                            $post_type_url = admin_url( 'edit.php?post_type=' . $post_type_slug );
                        } else {
                            // Fallback if post type object not found (e.g. inactive CPT)
                            $post_type_label = ucfirst( $post_type_slug );
                            $post_type_url = admin_url( 'edit.php?post_type=' . $post_type_slug );
                        }
                    }
                }
            }

            if ($is_found) {
                $forms[] = [
                    'post_id'         => $post->ID,
                    'post_title'      => get_the_title( $post->ID ),
                    'frontend_url'    => get_permalink( $post->ID ),
                    'form_name'       => $element['settings']['form_name'] ?? esc_html__( 'Unnamed Form', 'elementor-contact-form-db' ),
                    'edit_url'        => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
                    'widget_type'     => strtoupper($element['widgetType']),
                    'post_type_label' => $post_type_label,
                    'post_type_url'   => $post_type_url,
                    'status' => in_array( 'eef-register-post', $submit_actions, true ),
                ];
            }

            if ( ! empty( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $post, $forms );
            }
        }
    }
}

new FDBGP_Form_To_Post_Settings();