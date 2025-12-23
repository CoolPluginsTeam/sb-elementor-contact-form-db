<?php
/**
 * Old Submission Handler Class
 * 
 * Handles legacy form submissions from the old plugin version
 * stored in elementor_cf_db post type with sb_elem_cfd meta key.
 */

if (!defined('ABSPATH')) {
    die;
}

class FDBGP_Old_Submission {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'handle_csv_download'));
    }

    /**
     * Check if old submissions exist in the database
     * 
     * @return bool
     */
    public static function has_old_submissions() {
        $posts = get_posts(array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        return !empty($posts);
    }

    /**
     * Get all unique form IDs from old submissions
     * 
     * @return array
     */
    public function get_form_ids() {
        global $wpdb;

        $sql = "SELECT DISTINCT(pm.meta_value) AS form_id
                FROM {$wpdb->posts} p 
                JOIN {$wpdb->postmeta} pm ON (
                    p.ID = pm.post_id AND 
                    pm.meta_key = 'sb_elem_cfd_form_id'
                ) 
                WHERE 
                    p.post_type = 'elementor_cf_db'
                    AND p.post_status = 'publish'
                    AND pm.meta_value != ''";

        $results = $wpdb->get_results($sql);
        $form_ids = array();

        if ($results) {
            foreach ($results as $result) {
                if (!empty($result->form_id)) {
                    $form_ids[$result->form_id] = $result->form_id;
                }
            }
        }

        return $form_ids;
    }

    /**
     * Get all unique submitted pages from old submissions
     * 
     * @return array
     */
    public function get_submitted_pages() {
        global $wpdb;

        $sql = "SELECT DISTINCT(pm.meta_value) AS submitted_id
                FROM {$wpdb->posts} p 
                JOIN {$wpdb->postmeta} pm ON (
                    p.ID = pm.post_id AND 
                    pm.meta_key = 'sb_elem_cfd_submitted_on_id'
                ) 
                WHERE 
                    p.post_type = 'elementor_cf_db'
                    AND p.post_status = 'publish'";

        $results = $wpdb->get_results($sql);
        $pages = array();

        if ($results) {
            foreach ($results as $result) {
                if (!empty($result->submitted_id)) {
                    $pages[$result->submitted_id] = get_the_title($result->submitted_id);
                }
            }
        }

        return $pages;
    }

    /**
     * Get form submission meta data
     * 
     * @param int $post_id
     * @return array|false
     */
    public function get_submission_meta($post_id) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'sb_elem_cfd' AND post_id = %d",
            $post_id
        );

        $meta = $wpdb->get_var($sql);
        
        if ($meta) {
            return maybe_unserialize($meta);
        }

        return false;
    }

    /**
     * Get export rows by submitted page ID
     * 
     * @param int $submitted_id
     * @param int $limit
     * @return array
     */
    public function get_export_rows_by_page($submitted_id, $limit = -1) {
        $rows = array();
        $args = array(
            'post_type' => 'elementor_cf_db',
            'meta_key' => 'sb_elem_cfd_submitted_on_id',
            'posts_per_page' => $limit,
            'meta_value' => $submitted_id,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if ($posts) {
            $first_post = current($posts);
            $row = '"Date","Submitted On","Form ID","Submitted By",';

            $data = $this->get_submission_meta($first_post->ID);
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $field) {
                    $row .= '"' . esc_attr($field['label']) . '",';
                }
            }

            $rows[] = rtrim($row, ',');

            foreach ($posts as $post) {
                $data = $this->get_submission_meta($post->ID);
                if ($data) {
                    $row = '';
                    $form_id = get_post_meta($post->ID, 'sb_elem_cfd_form_id', true);
                    $submitted_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
                    $submitted_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';

                    $row .= '"' . esc_attr($post->post_date) . '","' . esc_attr($submitted_on) . '","' . esc_attr($form_id) . '","' . esc_attr($submitted_by) . '",';

                    if (isset($data['data'])) {
                        foreach ($data['data'] as $field) {
                            $row .= '"' . addslashes($field['value']) . '",';
                        }
                    }

                    $rows[] = rtrim($row, ',');
                }
            }
        }

        return $rows;
    }

    /**
     * Get export rows by form ID
     * 
     * @param string $form_id
     * @param int $limit
     * @return array
     */
    public function get_export_rows_by_form_id($form_id, $limit = -1) {
        $rows = array();
        $args = array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => $limit,
            'meta_key' => 'sb_elem_cfd_form_id',
            'meta_value' => $form_id,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if ($posts) {
            $row = '"Date","Submitted On","Form ID","Submitted By",';

            $first_post = current($posts);
            $data = $this->get_submission_meta($first_post->ID);

            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $field) {
                    $row .= '"' . esc_attr($field['label']) . '",';
                }
            }

            $rows[] = rtrim($row, ',');

            foreach ($posts as $post) {
                $data = $this->get_submission_meta($post->ID);
                if ($data) {
                    $row = '';
                    $submitted_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
                    $submitted_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';

                    $row .= '"' . esc_attr($post->post_date) . '","' . esc_attr($submitted_on) . '","' . esc_attr($form_id) . '","' . esc_attr($submitted_by) . '",';

                    if (isset($data['data'])) {
                        foreach ($data['data'] as $field) {
                            $row .= '"' . addslashes($field['value']) . '",';
                        }
                    }

                    $rows[] = rtrim($row, ',');
                }
            }
        }

        return $rows;
    }

    /**
     * Handle CSV download request
     */
    public function handle_csv_download() {
        if (!isset($_REQUEST['download_old_csv'])) {
            return;
        }

        if (empty($_POST['fdbgp_old_export_nonce']) || !wp_verify_nonce($_POST['fdbgp_old_export_nonce'], 'fdbgp_old_export')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $rows = array();
        $filename = 'old-submissions';

        if (isset($_REQUEST['form_name']) && !empty($_REQUEST['form_name'])) {
            $form_name = sanitize_text_field($_REQUEST['form_name']);
            $rows = $this->get_export_rows_by_page($form_name);
            $filename = sanitize_title($form_name);
        } elseif (isset($_REQUEST['form_id']) && !empty($_REQUEST['form_id'])) {
            $form_id = sanitize_text_field($_REQUEST['form_id']);
            $rows = $this->get_export_rows_by_form_id($form_id);
            $filename = sanitize_title($form_id);
        }

        if (!empty($rows)) {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            header('Pragma: no-cache');
            echo implode("\n", $rows);
            exit;
        }
    }

    /**
     * Get total submission count
     * 
     * @return int
     */
    public function get_submission_count() {
        $posts = get_posts(array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        return count($posts);
    }
}

// Initialize the class
FDBGP_Old_Submission::get_instance();
