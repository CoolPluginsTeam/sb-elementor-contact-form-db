<?php
if (!defined('ABSPATH')) {
    die;
}
/**
 * Custom elementor form action after submit to add a records to
 * Google Spreadsheet
 *
 * @since 1.0.0
 * @package wpsyncsheets-elementor
 */

use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Action_Base;
use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;

/**
 * Class WPSSLE_Form_Sheets_Action
 */
class FDBGP_Form_Sheets_Action extends Action_Base {
	/**
	 * Get Name
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return esc_html( 'Connect Google Sheets' );
	}
	/**
	 * Get Label
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Connect Google Sheets', 'elementor-contact-form-db' );
	}

	/**
	 * Add Prefix
	 * Adds prefix to avoid conflicts
	 *
	 * @access public
	 * @param string $id id.
	 * @return string
	 */
	public function add_prefix($id){
		return 'fdbgp_' . $id;
	}
	/**
	 * Run
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record Record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler Ajax handler.
	 */
	public function run( $record, $ajax_handler ) {
		$fdbgp_settings = $record->get( 'form_settings' );
		// Get sumitetd Form data.
		$fdbgp_raw_fields = $record->get( 'fields' );
		$instance_api      = new FDBGP_Google_API_Functions();
		if ( ! $instance_api->checkcredenatials() ) {
			return;
		}
		if ( isset( $fdbgp_settings['submit_actions'] ) && in_array( $this->get_name(), $fdbgp_settings['submit_actions'], true ) ) {
			$fdbgp_spreadsheetid = $fdbgp_settings[$this->add_prefix('spreadsheetid')];
			$fdbgp_sheetname     = $fdbgp_settings[$this->add_prefix('sheet_name')];
			$fdbgp_sheetarray    = $instance_api->get_spreadsheet_listing();
			if ( ! empty( $fdbgp_spreadsheetid ) && ! array_key_exists( $fdbgp_spreadsheetid, $fdbgp_sheetarray ) ) {
				return;
			} elseif ( ! empty( $fdbgp_spreadsheetid ) ) {
				if($fdbgp_spreadsheetid !== 'new'){
					$response = $instance_api->get_sheet_listing( $fdbgp_spreadsheetid );
					foreach ( $response->getSheets() as $s ) {
						$fdbgp_sheets[] = $s['properties']['title'];
					}
				}
				if ( ! empty( $fdbgp_sheetname ) && ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
					return;
				}
			}
			if ( empty( $fdbgp_spreadsheetid ) || empty( $fdbgp_sheetname ) ) {
				return;
			}
			// Normalize the Form Data.
			$fdbgp_fields = array();
			foreach ( $fdbgp_raw_fields as $id => $field ) {
				$fdbgp_fields[ $id ] = $field['value'];
			}
			try {
				$fdbgp_headers    = $fdbgp_settings[$this->add_prefix('sheet_headers')];
				$fdbgp_value_data = array();
				foreach ( $fdbgp_headers as $fdbgp_fieldvalue ) {
					if ( array_key_exists( $fdbgp_fieldvalue, $fdbgp_fields ) ) {
						if ( is_array( $fdbgp_fields[ $fdbgp_fieldvalue ] ) ) {
							$fdbgp_value_data[] = implode( ',', $fdbgp_fields[ $fdbgp_fieldvalue ] );
						} else {
							$fdbgp_value_data[] = $fdbgp_fields[ $fdbgp_fieldvalue ];
						}
					} else {
						$fdbgp_value_data[] = '';
					}
				}
				$fdbgp_sheet         = "'" . $fdbgp_sheetname . "'!A:A";
				$fdbgp_allentry      = $instance_api->get_row_list( $fdbgp_spreadsheetid, $fdbgp_sheet );
				$fdbgp_data          = $fdbgp_allentry->getValues();
				$fdbgp_data          = array_map(
					function ( $fdbgp_element ) {
						if ( isset( $fdbgp_element['0'] ) ) {
							return $fdbgp_element['0'];
						} else {
							return '';
						}
					},
					$fdbgp_data
				);
				$fdbgp_rangetoupdate = $fdbgp_sheetname . '!A' . ( count( $fdbgp_data ) + 1 );
				$fdbgp_requestbody   = $instance_api->valuerangeobject( array( $fdbgp_value_data ) );
				$fdbgp_params        = FDBGP_Google_API_Functions::get_row_format();
				$param                = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_rangetoupdate, $fdbgp_requestbody, $fdbgp_params );
				$instance_api->updateentry( $param );
			} catch ( Exception $e ) {
				$ajax_handler->add_admin_error_message($e->getMessage() );
			}
		}
	}
	/**
	 * Register Settings Section
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget settings.
	 */
	public function register_settings_section( $widget ) {
		// if ( current_user_can( 'edit_wpsyncsheets_elementor_lite_form_settings' ) ) {
			$instance_api           = new FDBGP_Google_API_Functions();
			$fdbgp_google_settings = $instance_api->get_google_creds();
			global $fdbgp_headers, $fdbgp_exclude_headertype;
			global $fdbgp_spreadsheetid, $fdbgp_sheetname, $fdbgp_sheet_headers, $fdbgp_sheetheaders, $existincurrentpage, $fdbgp_sheetheaders_new, $fdbgp_form_fields;
			$existincurrentpage        = 'no';
			$fdbgp_sheetheaders       = array();
			$fdbgp_sheetheaders_new   = array();
			$fdbgp_form_fields        = array();
			$fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );
			$fdbgp_document           = Plugin::elementor()->documents->get( get_the_ID() );
			if ( $fdbgp_document ) {
				$fdbgp_data        = $fdbgp_document->get_elements_data();
				$fdbgp_data_global = $fdbgp_data;
				global $fdbgp_type;
				$fdbgp_type = '';
				$fdbgp_data = Plugin::elementor()->db->iterate_data(
					$fdbgp_data,
					function( $element ) use ( &$do_update ) {
						if ( isset( $element['widgetType'] ) && 'form' === (string) $element['widgetType'] ) {
							global $fdbgp_headers, $fdbgp_exclude_headertype;
							global $fdbgp_spreadsheetid , $fdbgp_sheetname, $fdbgp_sheet_headers;
							$fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );
							if ( isset( $element['settings'][$this->add_prefix('spreadsheetid')] ) ) {
								$fdbgp_spreadsheetid = $element['settings'][$this->add_prefix('spreadsheetid')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_name')] ) ) {
								$fdbgp_sheetname = $element['settings'][$this->add_prefix('sheet_name')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_headers')] ) ) {
								$fdbgp_sheet_headers = $element['settings'][$this->add_prefix('sheet_headers')];
							}
							foreach ( $element['settings']['form_fields'] as $formdata ) {
								if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $fdbgp_exclude_headertype, true ) ) ) {
									$fdbgp_headers[ $formdata['custom_id'] ] = $formdata['field_label'] ? $formdata['field_label'] : ucfirst( $formdata['custom_id'] );
								}
							}
							return $fdbgp_headers;
						}
					}
				);
				if ( empty( $fdbgp_headers ) ) {
					Plugin::elementor()->db->iterate_data(
						$fdbgp_data_global,
						function( $element ) use ( &$do_update ) {
							if ( isset( $element['widgetType'] ) && 'global' === (string) $element['widgetType'] ) {
								if ( ! empty( $element['templateID'] ) ) {
									$global_form      = get_post_meta( $element['templateID'], '_elementor_data', true );
									$global_form_meta = json_decode( $global_form, true );
									if ( $global_form_meta ) {
										global $fdbgp_headers, $fdbgp_exclude_headertype;
										global $fdbgp_spreadsheetid , $fdbgp_sheetname, $fdbgp_sheet_headers;
										$fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('spreadsheetid')] ) ) {
											$fdbgp_spreadsheetid = $global_form_meta[0]['settings'][$this->add_prefix('spreadsheetid')];
										}
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('sheet_name')] ) ) {
											$fdbgp_sheetname = $global_form_meta[0]['settings'][$this->add_prefix('sheet_name')];
										}
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('sheet_headers')] ) ) {
											$fdbgp_sheet_headers = $global_form_meta[0]['settings'][$this->add_prefix('sheet_headers')];
										}
										if ( is_array( $global_form_meta[0]['settings']['form_fields'] ) ) {
											foreach ( $global_form_meta[0]['settings']['form_fields'] as $formdata ) {
												if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $fdbgp_exclude_headertype, true ) ) ) {
													$fdbgp_headers[ $formdata['custom_id'] ] = $formdata['field_label'] ? $formdata['field_label'] : ucfirst( $formdata['custom_id'] );
												}
											}
										}
										return $fdbgp_headers;
									}
								}
							}
						}
					);
				}
			}
			if ( ! is_array( $fdbgp_sheetheaders ) ) {
				$fdbgp_sheetheaders = array();
			}
			if ( empty( $fdbgp_google_settings['client_token'] ) ) {
				$fdbgp_html = sprintf(
					'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
					esc_html__( 'Please genearate authentication code from Google Sheet Setting', 'wpsse' ),
					esc_html__( 'Click Here', 'wpsse' )
				);
				$widget->start_controls_section(
					$this->add_prefix('section_notice'),
					array(
						'label'     => esc_attr__( 'Connect Google Sheets', 'wpsse' ),
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);
				$widget->add_control(
					$this->add_prefix('setup_clientidsecret'),
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw'  => $fdbgp_html,
					)
				);
				$widget->end_controls_section();
			} elseif ( ! empty( $fdbgp_google_settings['client_token'] ) && ! $instance_api->checkcredenatials() ) {
				$fdbgp_error = $instance_api->getClient( 1 );
				if ( 'Invalid token format' === (string) $fdbgp_error || 'invalid_grant' === (string) $fdbgp_error ) {
					$fdbgp_html = sprintf(
						'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
						esc_html__( 'Error: Invalid Token - Revoke Token with Google Sheet Setting and try again.', 'wpsse' ),
						esc_html__( 'Click Here', 'wpsse' )
					);
				} else {
					$fdbgp_html = sprintf(
						'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s</div>',
						'Error: ' . $fdbgp_error
					);
				}
				$widget->start_controls_section(
					$this->add_prefix('section_notice'),
					array(
						'label'     => esc_attr__( 'Connect Google Sheets', 'wpsse' ),
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);
				$widget->add_control(
					$this->add_prefix('setup_clientidsecret'),
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw'  => $fdbgp_html,
					)
				);
				$widget->end_controls_section();
			} else {
				$fdbgp_spreadsheets = $instance_api->get_spreadsheet_listing();
				$fdbgp_sheets       = array();
				if ( ! empty( $fdbgp_spreadsheetid ) && array_key_exists( $fdbgp_spreadsheetid, $fdbgp_spreadsheets ) ) {
					if($fdbgp_spreadsheetid !== 'new'){
						$response = $instance_api->get_sheet_listing( $fdbgp_spreadsheetid );
						foreach ( $response->getSheets() as $s ) {
							$fdbgp_sheets[] = $s['properties']['title'];
						}
					}
				}
				$widget->start_controls_section(
					$this->add_prefix('section'),
					array(
						'label'     => esc_attr__( 'Connect Google Sheets', 'wpsse' ),
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);
				$widget->add_control(
					$this->add_prefix('spreadsheetid'),
					array(
						'label'       => esc_attr__( 'Select Spreadsheet', 'wpsse' ),
						'type'        => Controls_Manager::SELECT,
						'options'     => $fdbgp_spreadsheets,
						'label_block' => true,
						'separator'   => 'before',
					)
				);
				$widget->add_control(
					$this->add_prefix('new_spreadsheet_name'),
					array(
						'label'       => esc_attr__( 'Spreadsheet Name', 'wpsse' ),
						'type'        => Controls_Manager::TEXT,
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
					)
				);
				$widget->add_control(
					$this->add_prefix('sheet_name'),
					array(
						'label'       => esc_attr__( 'Sheet Name', 'wpsse' ),
						'type'        => Controls_Manager::TEXT,
						'label_block' => true,
					)
				);
				$widget->add_control(
					$this->add_prefix('sheet_list'),
					array(
						'label'       => esc_attr__( 'Select Sheet Name', 'wpsse' ),
						'type'        => Controls_Manager::SELECT,
						'label_block' => true,
						'options'     => $fdbgp_sheets,
					)
				);
				$widget->add_control(
					$this->add_prefix('sheet_headers'),
					array(
						'label'       => esc_attr__( 'Sheet Headers', 'wpsse' ),
						'type'        => Controls_Manager::SELECT2,
						'multiple'    => true,
						'options'     => $fdbgp_headers,
						'label_block' => true,
					)
				);
				$widget->add_control(
					$this->add_prefix('freeze_header'),
					array(
						'label'        => esc_attr__( 'Freeze Headers', 'wpsse' ),
						'type'         => Controls_Manager::SWITCHER,
						'label_off'    => 'No',
						'label_on'     => 'Yes',
						'return_value' => 'yes',
					)
				);
				$widget->end_controls_section();
			}
		// }
	}
	/**
	 * On Export
	 * Clears form settings on export
	 *
	 * @access public
	 * @param array $element_sheets clear settings.
	 */
	public function on_export( $element_sheets ) {
	}
}