<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Elementor\Plugin as ElementorPlugin;
use \Elementor\Controls_Manager as ElementorControls;
use \Elementor\Repeater as ElementorRepeater;
use ElementorPro\Modules\Forms\Classes\Action_Base;

/**
 * Register post after form submit.
 */
class FDBGP_Register_Post extends Action_Base {
	public function __construct() {
		\add_action( 'elementor/element/form/section_form_fields/before_section_end', array( $this, 'add_control_fields' ), 100, 2 );
	}
	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eef-register-post';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Save Submissions in Post Type';
	}

	/**
	 * Get public post types
	 */
	private function get_registered_post_types() {
		$registered_post_types = get_post_types( array( 'public' => true ), 'objects' );
		$post_type_options =  array();
		foreach ( $registered_post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->label;
		}

		return $post_type_options;
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'eef-register-post-section',
			[
				'label' => \esc_html__( 'Save Submissions In Post Type', 'sb-elementor-contact-form-db' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'eef-register-post-info',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => \esc_html__( 'Important: To save form fields as post data, you must map each field from the "Advanced" tab of the respective form field settings.', 'sb-elementor-contact-form-db' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
		/**
		 * TODO:
		 * 1. Add or edit existent post.
		 * 2. Post terms (taxonomy).
		 * 3. Redirect to post after submit/created?.
		 * 4. Se post status === private add campo de senha.
		 */
		$widget->add_control(
			'eef-register-post-post-type',
			[
				'label' => \esc_html__( 'Post Type', 'sb-elementor-contact-form-db' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $this->get_registered_post_types(),
			]
		);

		$widget->add_control(
			'eef-register-post-post-status',
			[
				'label' => \esc_html__( 'Post Status', 'sb-elementor-contact-form-db' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'draft',
				'options' => [
					'draft'  => \esc_html__( 'Draft', 'sb-elementor-contact-form-db' ),
					'publish' => \esc_html__( 'Publish', 'sb-elementor-contact-form-db' ),
					'pending' => \esc_html__( 'Pending', 'sb-elementor-contact-form-db' ),
				],
			]
		);

		$widget->add_control(
			'eef-register-post-user-permission',
			[
				'label' => \esc_html__( 'Run only to logged in users?', 'sb-elementor-contact-form-db' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => \esc_html__( 'Yes', 'sb-elementor-contact-form-db' ),
				'label_off' => \esc_html__( 'No', 'sb-elementor-contact-form-db' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => \esc_html__( 'Warning: Save data from not logged in users can be a security risk', 'sb-elementor-contact-form-db' ),
			]
		);

		$widget->end_controls_section();
	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['settings']['eef-register-post-post-type'],
			$element['settings']['eef-register-post-post-status'],
			$element['settings']['eef-register-post-user-permission'],
		);

		return $element;
	}

	/**
	 * Runs the action after submit
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		// 1. Resolve form document ID and verify form owner
		$doc_id = (int) $record->get_form_settings( 'form_post_id' );
		if ( ! $doc_id && isset( $_POST['form_post_id'] ) ) {
			$doc_id = absint( $_POST['form_post_id'] );
		}
		if ( ! $doc_id && isset( $_POST['post_id'] ) ) {
			$doc_id = absint( $_POST['post_id'] );
		}
		if ( ! $doc_id ) {
			$doc_id = (int) get_the_ID();
		}

		$owner = $doc_id ? (int) get_post_field( 'post_author', $doc_id ) : 0;
		// Backward compatibility & migration filter for custom setups or imported templates
		$owner = apply_filters( 'fdbgp_register_post_owner', $owner, $doc_id, $record );

		// If post has no author (e.g. imported page) and submitter is admin, allow admin as owner
		if ( ! $owner && current_user_can( 'manage_options' ) ) {
			$owner = get_current_user_id();
		}

		if ( ! $owner ) {
			return;
		}

		$raw_type   = (string) $record->get_form_settings( 'eef-register-post-post-type' );
		$raw_status = (string) $record->get_form_settings( 'eef-register-post-post-status' );

		$type   = ! empty( $raw_type ) ? sanitize_key( $raw_type ) : 'post';
		$status = ! empty( $raw_status ) ? sanitize_key( $raw_status ) : 'draft';

		$allowed_post_types = get_post_types( array( 'public' => true ), 'names' );
		$allowed_statuses   = array( 'publish', 'pending', 'draft', 'private' );
		$pto                = get_post_type_object( $type );

		if ( ! $pto || ! in_array( $type, $allowed_post_types, true ) || ! in_array( $status, $allowed_statuses, true ) ) {
			return;
		}

		// 2. Privilege Check: Verify form owner has permissions for this post type and status
		if ( ! user_can( $owner, $pto->cap->edit_posts ) ) {
			return;
		}
		if ( 'publish' === $status && ! user_can( $owner, $pto->cap->publish_posts ) ) {
			return;
		}

		$form_name = sanitize_text_field( $record->get_form_settings( 'form_name' ) );

		$new_post_data = array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => ! empty( $form_name ) ? $form_name : __( 'Form Submission', 'sb-elementor-contact-form-db' ),
			'post_content' => ! empty( $form_name ) ? $form_name : '',
			'post_author'  => $owner,
		);

		$form_fields     = $record->get( 'fields' );
		$fields_settings = $record->get_form_settings( 'form_fields' );
		$formated_fields = array();

		if ( is_array( $fields_settings ) && is_array( $form_fields ) ) {
			foreach ( $fields_settings as $key => $field ) {
				if ( isset( $field['custom_id'], $form_fields[ $field['custom_id'] ] ) ) {
					$formated_fields[ $key ] = $form_fields[ $field['custom_id'] ];
					$formated_fields[ $key ]['field-to-register']        = isset( $field['eef-register-post-field'] ) ? $field['eef-register-post-field'] : '';
					$formated_fields[ $key ]['custom-field-to-register'] = isset( $field['eef-register-post-custom-field'] ) ? $field['eef-register-post-custom-field'] : '';
				}
			}
		}

		// 3. Strict allowlist to prevent mass assignment (drops ID, post_status override, etc.)
		$allowed_targets           = array( 'post_title', 'post_content', 'post_excerpt', 'post_author' );
		$custom_fields_to_register = array();

		foreach ( $formated_fields as $field ) {
			$target  = isset( $field['field-to-register'] ) ? $field['field-to-register'] : '';
			$raw_val = isset( $field['value'] ) ? $field['value'] : '';

			if ( 'custom_field' === $target ) {
				$meta_key = isset( $field['custom-field-to-register'] ) ? sanitize_key( $field['custom-field-to-register'] ) : '';
				if ( ! empty( $meta_key ) ) {
					// Disallow protected meta unless form owner has manage_options
					if ( is_protected_meta( $meta_key, 'post' ) ) {
						$allow_protected = apply_filters( 'fdbgp_allow_protected_meta_mapping', user_can( $owner, 'manage_options' ), $meta_key, $owner );
						if ( ! $allow_protected ) {
							continue;
						}
					}

					// Handle arrays (checkboxes/multiselect) as well as scalar values
					if ( is_array( $raw_val ) ) {
						$custom_fields_to_register[ $meta_key ] = array_map( 'sanitize_text_field', $raw_val );
					} else {
						$custom_fields_to_register[ $meta_key ] = is_scalar( $raw_val ) ? sanitize_text_field( (string) $raw_val ) : '';
					}
				}
			} elseif ( in_array( $target, $allowed_targets, true ) ) {
				// Normalize arrays to comma-separated string for core post fields
				$string_val = is_array( $raw_val ) ? implode( ', ', array_map( 'sanitize_text_field', $raw_val ) ) : (string) $raw_val;

				if ( 'post_title' === $target ) {
					$new_post_data['post_title'] = sanitize_text_field( $string_val );
				} elseif ( 'post_content' === $target ) {
					$new_post_data['post_content'] = wp_kses_post( $string_val );
				} elseif ( 'post_excerpt' === $target ) {
					$new_post_data['post_excerpt'] = sanitize_textarea_field( $string_val );
				} elseif ( 'post_author' === $target ) {
					$requested_author = absint( $string_val );
					if ( $requested_author && user_can( $owner, $pto->cap->edit_others_posts ) ) {
						$new_post_data['post_author'] = $requested_author;
					} elseif ( is_user_logged_in() && $requested_author === get_current_user_id() ) {
						$new_post_data['post_author'] = $requested_author;
					} else {
						$new_post_data['post_author'] = $owner;
					}
				}
			}
		}

		$is_restrict_to_loggedin_users = $record->get_form_settings( 'eef-register-post-user-permission' );
		if ( 'yes' === $is_restrict_to_loggedin_users && ! is_user_logged_in() ) {
			return;
		}

		// 4. Create post and assign sanitized custom fields
		$post_id = wp_insert_post( $new_post_data, true );
		if ( ! is_wp_error( $post_id ) && $post_id ) {
			foreach ( $custom_fields_to_register as $meta_key => $meta_value ) {
				if ( is_array( $meta_value ) ) {
					delete_post_meta( $post_id, $meta_key );
					foreach ( $meta_value as $sub_val ) {
						add_post_meta( $post_id, $meta_key, $sub_val );
					}
				} else {
					add_post_meta( $post_id, $meta_key, $meta_value );
				}
			}
		}
	}

	/**
	 * Add create post fields
	 *
	 * @param $element
	 * @param $args
	 */
	public function add_control_fields( $element, $args ) {
		$elementor = ElementorPlugin::instance();
		$control_data = $elementor->controls_manager->get_control_from_stack( $element->get_name(), 'form_fields' );

		if ( is_wp_error( $control_data ) ) {
			return;
		}

		$new_control = [
			'label' => \esc_html__( 'Field to Register', 'sb-elementor-contact-form-db' ),
			'type' => ElementorControls::SELECT,
			'tab' => 'content',
			'tabs_wrapper' => 'form_fields_tabs',
			'inner_tab' => 'form_fields_advanced_tab',
			'classes' => 'elementor-hidden-control',
			'description' => \esc_html__( 'Use this input to define what post field will receive this data when post is registered', 'sb-elementor-contact-form-db' ),
			'default' => 'select',
			'options' => [
				'select' => \esc_html__( 'Select', 'sb-elementor-contact-form-db' ),
				'post_title' => \esc_html__( 'Post Title', 'sb-elementor-contact-form-db' ),
				'post_content' => \esc_html__( 'Post Content', 'sb-elementor-contact-form-db' ),
				'post_excerpt' => \esc_html__( 'Post Excerpt', 'sb-elementor-contact-form-db' ),
				'post_author' => \esc_html__( 'Post Author', 'sb-elementor-contact-form-db' ),
				'custom_field' => \esc_html__( 'Custom Field', 'sb-elementor-contact-form-db' ),
			],
		];

		$new_control_2 = [
			'label' => \esc_html__( 'Custom Field Name', 'sb-elementor-contact-form-db' ),
			'type' => ElementorControls::TEXT,
			'placeholder' => \esc_html__( 'custom_field_name', 'sb-elementor-contact-form-db' ),
			'tab' => 'content',
			'tabs_wrapper' => 'form_fields_tabs',
			'inner_tab' => 'form_fields_advanced_tab',
			'description' => \esc_html__( 'Add the Custom Field name here. You can use default fields or custom created with ACF or similars', 'sb-elementor-contact-form-db' ),
			'condition' => [
					'eef-register-post-field' => 'custom_field',
			],
		];

		$mask_control = new ElementorRepeater();
		$mask_control->add_control( 'eef-register-post-field', $new_control );
		$mask_control->add_control( 'eef-register-post-custom-field', $new_control_2 );
		$pattern_field = $mask_control->get_controls();

		/**
		 * Register control in form advanced tab.
		 */
		$this->register_control_in_form_advanced_tab( $element, $control_data, $pattern_field );
	}

	/**
	 * Register control in form advanced tab
	 *
	 * @param object $element
	 * @param array $control_data
	 * @param array $pattern_field
	 */
	public function register_control_in_form_advanced_tab( $element, $control_data, $pattern_field ) {
		foreach( $pattern_field as $key => $control ) {
			if( $key !== '_id' ) {
				$new_order = [];
				foreach ( $control_data['fields'] as $field_key => $field ) {
					if ( 'field_value' === $field['name'] ) {
						$new_order[$key] = $control;
					}
					$new_order[ $field_key ] = $field;
				}

				$control_data['fields'] = $new_order;
			}
		}

		return $element->update_control( 'form_fields', $control_data );
	}
}
