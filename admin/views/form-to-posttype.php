<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FDBGP_Form_To_Post_Settings {

    /**
     * Render settings UI
     */
    public function __construct(){
        $forms = $this->get_forms_with_register_post_action();
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
        echo '<h2>' . esc_html__( 'Forms with "Register Post/Custom Post" Action', 'elementor-contact-form-db' ) . '</h2>';
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
     * Render table of forms
     *
     * @param array $forms
     */
    private function render_forms_table( array $forms ) {

        echo '<table class="widefat striped">';
        echo '<thead>
                <tr>
                    <th>' . esc_html__( 'Form Name', 'elementor-contact-form-db' ) . '</th>
                    <th>' . esc_html__( 'Page', 'elementor-contact-form-db' ) . '</th>
                    <th>' . esc_html__( 'Widget', 'elementor-contact-form-db' ) . '</th>
                    <th>' . esc_html__( 'Action', 'elementor-contact-form-db' ) . '</th>
                </tr>
              </thead><tbody>';

        foreach ( $forms as $form ) {
            echo '<tr>
                    <td>' . esc_html( $form['form_name'] ) . '</td>
                    <td>' . esc_html( $form['post_title'] ) . '</td>
                    <td>' . esc_html( $form['widget_type'] ) . '</td>
                    <td>
                        <a class="button button-primary" href="' . esc_url( $form['edit_url'] ) . '">
                            ' . esc_html__( 'Edit Form', 'elementor-contact-form-db' ) . '
                        </a>
                    </td>
                  </tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render empty state
     */
    private function render_empty_state() {

        $create_form_url = admin_url( 'post-new.php?post_type=page' );

        echo '<p>' . esc_html__(
            'No Elementor form is using the "Register Post/Custom Post" action.',
            'elementor-contact-form-db'
        ) . '</p>';

        echo '<p>
            <a class="button button-primary" href="' . esc_url( $create_form_url ) . '">
                ' . esc_html__( 'Create New Form', 'elementor-contact-form-db' ) . '
            </a>
        </p>';

        echo '<p class="description">' .
            esc_html__(
                'Create a new Elementor Form and enable the "Register Post/Custom Post" action under Actions After Submit.',
                'elementor-contact-form-db'
            ) .
        '</p>';
    }

    /**
     * Get Elementor forms that use Register Post action
     *
     * @return array
     */
    private function get_forms_with_register_post_action() {

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
            if (
                isset( $element['widgetType'] ) &&
                'form' === $element['widgetType'] &&
                ! empty( $element['settings']['submit_actions'] ) &&
                in_array( 'eef-register-post', $element['settings']['submit_actions'], true )
            ){
                $is_found = true;
            }

            if (
                isset( $element['widgetType'] ) &&
                'ehp-form' === $element['widgetType'] &&
                ! empty( $element['settings']['cool_formkit_submit_actions'] ) &&
                in_array( 'eef-register-post', $element['settings']['cool_formkit_submit_actions'], true )
            ){
                $is_found = true;
            }

            if ($is_found) {
                $forms[] = [
                    'post_id'    => $post->ID,
                    'post_title' => get_the_title( $post->ID ),
                    'form_name'  => $element['settings']['form_name'] ?? esc_html__( 'Unnamed Form', 'elementor-contact-form-db' ),
                    'edit_url'   => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
                    'widget_type'   => strtoupper($element['widgetType']),
                ];
            }

            if ( ! empty( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $post, $forms );
            }
        }
    }
}

new FDBGP_Form_To_Post_Settings();