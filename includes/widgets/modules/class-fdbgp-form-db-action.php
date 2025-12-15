<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Action_Base;

class FDBGP_Form_DB_Action extends Action_Base {

	public function get_name() {
		return 'fdbgp_save_to_db';
	}

	public function get_label() {
		return __( 'Save to Database', 'elementor-contact-form-db' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_fdbgp_save_to_db',
			array(
				'label' => __( 'Save to Database', 'elementor-contact-form-db' ),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$widget->add_control(
			'fdbgp_save_to_db_notice',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw' => __( 'Form submissions will be saved to the local database.', 'elementor-contact-form-db' ),
			)
		);

		$widget->end_controls_section();
	}

	public function on_export( $element ) {}

	public function run( $record, $ajax_handler ) {
		$raw_fields = $record->get( 'fields' );
		$settings   = $record->get( 'form_settings' );

		// Normalize fields
		$fields = array();
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		$post_title = 'Submission #' . uniqid();
		if ( isset( $fields['name'] ) ) {
			$post_title = $fields['name'];
		} elseif ( isset( $fields['email'] ) ) {
			$post_title = $fields['email'];
		}

		$post_data = array(
			'post_title'  => $post_title,
			'post_type'   => 'fdbgp_submission',
			'post_status' => 'publish', // or 'private'
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$ajax_handler->add_admin_error_message( 'Failed to save submission: ' . $post_id->get_error_message() );
			return;
		}

		// Save fields as meta
		foreach ( $fields as $key => $value ) {
			add_post_meta( $post_id, $key, $value );
		}

		// Save raw fields data for reference
		add_post_meta( $post_id, '_fdbgp_raw_fields', $raw_fields );
		
		// Save form meta
		add_post_meta( $post_id, '_fdbgp_form_id', $record->get_form_settings( 'id' ) );
		add_post_meta( $post_id, '_fdbgp_form_name', $record->get_form_settings( 'form_name' ) );
		add_post_meta( $post_id, '_fdbgp_date', current_time( 'mysql' ) );

	}
}
