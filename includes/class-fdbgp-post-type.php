<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class FDBGP_Post_Type {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Form Submissions', 'Post Type General Name', 'elementor-contact-form-db' ),
			'singular_name'         => _x( 'Form Submission', 'Post Type Singular Name', 'elementor-contact-form-db' ),
			'menu_name'             => __( 'Form Submissions', 'elementor-contact-form-db' ),
			'all_items'             => __( 'All Submissions', 'elementor-contact-form-db' ),
			'add_new_item'          => __( 'Add New Submission', 'elementor-contact-form-db' ),
			'edit_item'             => __( 'View Submission', 'elementor-contact-form-db' ),
			'update_item'           => __( 'Update Submission', 'elementor-contact-form-db' ),
			'view_item'             => __( 'View Submission', 'elementor-contact-form-db' ),
			'search_items'          => __( 'Search Submission', 'elementor-contact-form-db' ),
		);
		$args = array(
			'label'                 => __( 'Form Submission', 'elementor-contact-form-db' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => 'edit.php', // Default to posts menu for now to avoid issues, or 'formsdb' if we can find parent.
			'menu_position'         => 20,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
		);
		register_post_type( 'fdbgp_submission', $args );
	}
}
