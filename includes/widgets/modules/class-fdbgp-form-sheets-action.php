<?php
if (!defined('ABSPATH')) {
    die;
}
/**
 * Custom elementor form action after submit to add a records to
 * Google Spreadsheet
 *
 * @since 1.0.0
 */

use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Action_Base;
use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;

/**
 * Class fdbgp_Form_Sheets_Action
 */
add_action( 'elementor/controls/register', function( $controls_manager ) {
    class FDBGP_Control_Dynamic_Select2 extends \Elementor\Base_Data_Control {
        public function get_type() {
            return 'fdbgp_dynamic_select2';
        }

        public function content_template() {
            ?>
			<div class="elementor-control-field">
        <label class="elementor-control-title">{{{ data.label }}}</label>
        <div class="elementor-control-input-wrapper">
            <select class="elementor-control-dynamic-select2" data-setting="{{ data.name }}" multiple="multiple" style="width:100%">
                <# if ( data.options ) { 
                    _.each( data.options, function( option_title, option_value ) { #>
                        <option value="{{ option_value }}">{{{ option_title }}}</option>
                    <# } );
                } #>
            </select>
        </div>
        <div class="elementor-control-field-description">{{{ data.description }}}</div>
    </div>
        
            <?php
        }
    }
    $controls_manager->register( new FDBGP_Control_Dynamic_Select2() );
} );
class FDBGP_Form_Sheets_Action extends Action_Base {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Register AJAX actions
		add_action( 'wp_ajax_fdbgp_get_sheets', array( $this, 'ajax_get_sheets' ) );
		add_action( 'wp_ajax_fdbgp_create_spreadsheet', array( $this, 'ajax_create_spreadsheet' ) );
		add_action( 'wp_ajax_fdbgp_update_sheet_headers', array( $this, 'ajax_update_sheet_headers' ) );
		add_action( 'wp_ajax_fdbgp_check_sheet_headers', array( $this, 'ajax_check_sheet_headers' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'render_editor_script' ] );
	}
	
	
	/**
	 * Get Name
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return esc_html( 'Save Data in Google Sheets' );
	}
	/**
	 * Get Label
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Save Data in Google Sheets', 'elementor-contact-form-db' );
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

	public function render_editor_script() {

		wp_enqueue_script( 
			'fdbgp-editor-script', // Unique Handle
			FDBGP_PLUGIN_URL. 'assets/js/fdbgp-editor.js', // Path to your new JS file
			[ 'elementor-editor', 'jquery' ], // Dependencies: waits for Elementor & jQuery
			FDBGP_PLUGIN_VERSION, // Version
			true // Load in footer
		);
		
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
			
			if ( 'new' === $fdbgp_spreadsheetid ) {
				$fdbgp_sheetname = $fdbgp_settings[$this->add_prefix('sheet_name')];
			} else {
				$fdbgp_sheetname = isset( $fdbgp_settings[$this->add_prefix('sheet_list')] ) ? $fdbgp_settings[$this->add_prefix('sheet_list')] : '';
				// Fallback to text field if list is empty (backward compatibility)
				if ( empty( $fdbgp_sheetname ) && isset( $fdbgp_settings[$this->add_prefix('sheet_name')] ) ) {
					$fdbgp_sheetname = $fdbgp_settings[$this->add_prefix('sheet_name')];
				}
			}
			
			
			$is_newly_created = false;
			
			// Handle new spreadsheet creation
			if ( $fdbgp_spreadsheetid === 'new' ) {
				try {
					$fdbgp_new_spreadsheet_name = isset( $fdbgp_settings[$this->add_prefix('new_spreadsheet_name')] ) ? $fdbgp_settings[$this->add_prefix('new_spreadsheet_name')] : '';
					
					// Validate required fields with helpful error messages
					if ( empty( $fdbgp_new_spreadsheet_name ) && empty( $fdbgp_sheetname ) ) {
						$ajax_handler->add_admin_error_message( 'Please enter both a Spreadsheet Name and a Sheet Name to create a new spreadsheet.' );
						return;
					} elseif ( empty( $fdbgp_new_spreadsheet_name ) ) {
						$ajax_handler->add_admin_error_message( 'Please enter a Spreadsheet Name to create a new spreadsheet.' );
						return;
					} elseif ( empty( $fdbgp_sheetname ) ) {
						$ajax_handler->add_admin_error_message( 'Please enter a Sheet Name to create a new spreadsheet.' );
						return;
					}
					
					// Create new spreadsheet with initial sheet
					$spreadsheet_object = $instance_api->newspreadsheetobject( $fdbgp_new_spreadsheet_name, $fdbgp_sheetname );
					$created_spreadsheet = $instance_api->createspreadsheet( $spreadsheet_object );
					$fdbgp_spreadsheetid = $created_spreadsheet->getSpreadsheetId();
					$is_newly_created = true;
					
					// Get the sheet ID of the newly created sheet
					$sheets = $created_spreadsheet->getSheets();
					$sheet_id = $sheets[0]['properties']['sheetId'];
					
					// Add headers to the new spreadsheet
					$fdbgp_headers_data = $fdbgp_settings[$this->add_prefix('sheet_headers')];
					if ( is_array( $fdbgp_headers_data ) && ! empty( $fdbgp_headers_data ) ) {
						// Get form fields to map headers
						$fdbgp_raw_fields = $record->get( 'fields' );
						$fdbgp_header_labels = array();
						
						foreach ( $fdbgp_headers_data as $field_id ) {
							if ( isset( $fdbgp_raw_fields[ $field_id ] ) ) {
								$fdbgp_header_labels[] = $fdbgp_raw_fields[ $field_id ]['title'];
							} else {
								$fdbgp_header_labels[] = ucfirst( str_replace( '_', ' ', $field_id ) );
							}
						}
						
						// Add header row
						$fdbgp_header_range = $fdbgp_sheetname . '!A1';
						$fdbgp_header_body = $instance_api->valuerangeobject( array( $fdbgp_header_labels ) );
						$fdbgp_header_params = FDBGP_Google_API_Functions::get_row_format();
						$header_param = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_header_range, $fdbgp_header_body, $fdbgp_header_params );
						$instance_api->updateentry( $header_param );
						
							$freeze_object = $instance_api->freezeobject( $sheet_id, 1 );
							$freeze_param = array(
								'spreadsheetid' => $fdbgp_spreadsheetid,
								'requestbody' => $freeze_object
							);
							$instance_api->formatsheet( $freeze_param );
						
					}
					
					// Update the form settings to save the new spreadsheet ID
					// This ensures subsequent form submissions use the created spreadsheet
					$post_id = get_the_ID();
					if ( $post_id ) {
						$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
						if ( ! empty( $elementor_data ) ) {
							$elementor_data = json_decode( $elementor_data, true );
							$this->update_spreadsheet_id_in_data( $elementor_data, $record->get( 'form_settings' )['id'], $fdbgp_spreadsheetid );
							update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
						}
					}
					
				} catch ( Exception $e ) {
					$ajax_handler->add_admin_error_message( 'Failed to create spreadsheet - ' . $e->getMessage() );
					return;
				}
			}
			
			// Only validate existence for existing spreadsheets
			// Newly created ones might not appear in the list immediately due to caching/latency
			if ( ! $is_newly_created ) {
				$fdbgp_sheetarray    = $instance_api->get_spreadsheet_listing();
				if ( ! empty( $fdbgp_spreadsheetid ) && ! array_key_exists( $fdbgp_spreadsheetid, $fdbgp_sheetarray ) ) {
					error_log( 'Error: Spreadsheet ID not found in listing.' );
					return;
				} elseif ( ! empty( $fdbgp_spreadsheetid ) ) {
					$fdbgp_sheets = array(); // Initialize $fdbgp_sheets here
					$response = $instance_api->get_sheet_listing( $fdbgp_spreadsheetid );
					foreach ( $response->getSheets() as $s ) {
						$fdbgp_sheets[] = $s['properties']['title'];
					}
					
					// Handle "Create New Tab" Logic
					if ( 'create_new_tab' === $fdbgp_sheetname ) {
						$fdbgp_new_tab_name = isset( $fdbgp_settings[$this->add_prefix('new_sheet_tab_name')] ) ? $fdbgp_settings[$this->add_prefix('new_sheet_tab_name')] : '';
						
						if ( ! empty( $fdbgp_new_tab_name ) ) {
							// Update the main sheetname variable
							$fdbgp_sheetname = $fdbgp_new_tab_name;
							
							// Check if it exists
							if ( ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
								try {
									// Create the new sheet
									$sheet_req_obj = $instance_api->createsheetobject( $fdbgp_sheetname );
									$sheet_param = array(
										'spreadsheetid' => $fdbgp_spreadsheetid,
										'requestbody'   => $sheet_req_obj
									);
									$sheet_response = $instance_api->formatsheet( $sheet_param );
									
									// Get new sheet ID (needed for freezing rows)
									// The response structure depends on BatchUpdate response
									// Usually: $response->getReplies()[0]->getAddSheet()->getProperties()->getSheetId()
									$new_sheet_id = 0;
									if ( method_exists( $sheet_response, 'getReplies' ) ) {
										$replies = $sheet_response->getReplies();
										if ( isset( $replies[0] ) ) {
											$addSheet = $replies[0]->getAddSheet();
											if ( $addSheet ) {
												$new_sheet_id = $addSheet->getProperties()->getSheetId();
											}
										}
									}

									// Add Headers
									$fdbgp_headers_data = $fdbgp_settings[$this->add_prefix('sheet_headers')];
									if ( is_array( $fdbgp_headers_data ) && ! empty( $fdbgp_headers_data ) ) {
										$fdbgp_header_labels = array();
										// Map fields to labels
										// Note: $record->get('fields') is what we have access to
										$fields_refs = $record->get( 'fields' );
										foreach ( $fdbgp_headers_data as $field_id ) {
											if ( isset( $fields_refs[ $field_id ] ) ) {
												$fdbgp_header_labels[] = $fields_refs[ $field_id ]['title'];
											} else {
												$fdbgp_header_labels[] = ucfirst( str_replace( '_', ' ', $field_id ) );
											}
										}
										
										// Add header row
										$fdbgp_header_range = $fdbgp_sheetname . '!A1';
										$fdbgp_header_body = $instance_api->valuerangeobject( array( $fdbgp_header_labels ) );
										$fdbgp_header_params = FDBGP_Google_API_Functions::get_row_format();
										$header_param = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_header_range, $fdbgp_header_body, $fdbgp_header_params );
										$instance_api->updateentry( $header_param );
										
										// Freeze header row
										$freeze_object = $instance_api->freezeobject( $new_sheet_id, 1 );
										$freeze_param = array(
											'spreadsheetid' => $fdbgp_spreadsheetid,
											'requestbody' => $freeze_object
										);
										$instance_api->formatsheet( $freeze_param );
									}
									
									// Add to valid lists
									$fdbgp_sheets[] = $fdbgp_sheetname;
									
								} catch ( Exception $e ) {
									error_log( 'Failed to create new tab: ' . $e->getMessage() );
									// Fallback: Proceed, maybe it exists and we missed it, or it will fail on insert.
								}
							}
						}
					}
					
					// Fix for legacy settings: If sheet name is an index (e.g. "0"), try to resolve it to the actual name
					if ( is_numeric( $fdbgp_sheetname ) && ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
						$index = (int) $fdbgp_sheetname;
						if ( isset( $fdbgp_sheets[ $index ] ) ) {
							$fdbgp_sheetname = $fdbgp_sheets[ $index ];
						}
					}
					
					if ( ! empty( $fdbgp_sheetname ) && ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
						error_log( 'Error: Sheet Name not found in spreadsheet.' );
						return;
					}
				}
			}
			if ( ( empty( $fdbgp_spreadsheetid ) && $fdbgp_spreadsheetid !== '0' ) || ( empty( $fdbgp_sheetname ) && $fdbgp_sheetname !== '0' ) ) {
				error_log( 'Error: Missing Spreadsheet ID or Sheet Name.' );
				return;
			}
			
			// Validate that at least one field/header is selected
			$fdbgp_sheetheaders = isset($fdbgp_settings[$this->add_prefix('sheet_headers')]) ? $fdbgp_settings[$this->add_prefix('sheet_headers')] : [];
			if ( empty( $fdbgp_sheetheaders ) || ! is_array( $fdbgp_sheetheaders ) || count( $fdbgp_sheetheaders ) === 0 ) {
				
				return;
			}
			// Normalize the Form Data.
			$fdbgp_fields = array();
			foreach ( $fdbgp_raw_fields as $id => $field ) {
				$fdbgp_fields[ $id ] = $field['value'];
			}

			// Add System Fields for mapping
			$fdbgp_fields['user_ip']         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
			$fdbgp_fields['user_agent']      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
			$fdbgp_fields['submission_date'] = current_time( 'mysql' );
			$fdbgp_fields['page_url']        = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '';
			try {
				$fdbgp_headers = isset($fdbgp_settings[$this->add_prefix('sheet_headers')]) ? $fdbgp_settings[$this->add_prefix('sheet_headers')] : [];
				
				// Fallback: If no headers selected, send ALL fields
				if ( empty($fdbgp_headers) || !is_array($fdbgp_headers) ) {
					$fdbgp_headers = array_keys($fdbgp_fields);
				}

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
				
				// Fix: Handle null data (empty sheet) to avoid PHP 8 fatal error in array_map
				if ( is_null( $fdbgp_data ) ) {
					$fdbgp_data = array();
				}

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
				// Safely quote the sheet name for the update range as well
				// Use A1 range for append, letting Google determine the next empty row
				$fdbgp_rangetoupdate = "'" . $fdbgp_sheetname . "'!A1";
				$fdbgp_requestbody   = $instance_api->valuerangeobject( array( $fdbgp_value_data ) );
				$fdbgp_params        = FDBGP_Google_API_Functions::get_row_format();
				
				// Add insertDataOption for safe appending
				$fdbgp_params['insertDataOption'] = 'INSERT_ROWS';
				
				$param                = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_rangetoupdate, $fdbgp_requestbody, $fdbgp_params );
				$instance_api->appendentry( $param );
			} catch ( Exception $e ) {
				error_log( 'Exception: ' . $e->getMessage() );
				$ajax_handler->add_admin_error_message( 'Error: ' . $e->getMessage() );
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

		// if ( current_user_can( 'edit_posts' ) ) {
			$instance_api           = new FDBGP_Google_API_Functions();
			$fdbgp_google_settings = $instance_api->get_google_creds();
			
			// Local variables to replace globals
			$local_spreadsheet_id = '';
			$local_sheet_name = '';
			$local_sheet_headers = array();
			$local_headers = array();

			$fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );
			
			$existincurrentpage        = 'no';
			$fdbgp_sheetheaders       = array();
			$fdbgp_sheetheaders_new   = array();
			$fdbgp_form_fields        = array();
			
			  $fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html', 'step' ); // Added 'step'
			

			// Retrieve saved settings from Elementor data
			$fdbgp_document = Plugin::elementor()->documents->get( get_the_ID() );
			if ( $fdbgp_document ) {
				$fdbgp_data = $fdbgp_document->get_elements_data();
				$widget_id = $widget->get_id();
				
				Plugin::elementor()->db->iterate_data(
					$fdbgp_data,
					function( $element ) use ( &$local_spreadsheet_id, &$local_sheet_name, &$local_sheet_headers, &$local_headers, $widget_id, $fdbgp_exclude_headertype ) {
						if ( isset( $element['id'] ) && (string)$widget_id === (string)$element['id'] ) {
							if ( isset( $element['settings'][$this->add_prefix('spreadsheetid')] ) ) {
								$local_spreadsheet_id = $element['settings'][$this->add_prefix('spreadsheetid')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_list')] ) ) {
								$local_sheet_name = $element['settings'][$this->add_prefix('sheet_list')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_headers')] ) ) {
								$local_sheet_headers = $element['settings'][$this->add_prefix('sheet_headers')];
							}
							if ( isset( $element['settings']['form_fields'] ) ) {
								foreach ( $element['settings']['form_fields'] as $formdata ) {
									if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $fdbgp_exclude_headertype, true ) ) ) {
										$local_headers[ $formdata['custom_id'] ] = isset($formdata['field_label']) && $formdata['field_label'] ? $formdata['field_label'] : ucfirst( $formdata['custom_id'] );
									}
								}
							}
						}
						return $element;
					}
				);
			}

			if ( ! is_array( $fdbgp_sheetheaders ) ) {
				$fdbgp_sheetheaders = array();
			}
			if ( empty( $fdbgp_google_settings['client_token'] ) ) {
				$fdbgp_html = sprintf(
					'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
					esc_html__( 'Please genearate authentication code from Google Sheet Setting', 'elementor-contact-form-db' ),
					esc_html__( 'Click Here', 'elementor-contact-form-db' )
				);
				$widget->start_controls_section(
					'section_google_sheets',
					array(
						'label'     => esc_html__( 'Google Sheets', 'elementor-contact-form-db' ),
						'tab'       => 'connect_google_sheets_tab',
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);

				$widget->add_control(
					'fdbgp_html',
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
						esc_html__( 'Error: Invalid Token - Revoke Token with Google Sheet Setting and try again.', 'elementor-contact-form-db' ),
						esc_html__( 'Click Here', 'elementor-contact-form-db' )
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
						'label'     => esc_attr__( 'Save Data in Google Sheets', 'elementor-contact-form-db' ),
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
				$widget->start_controls_section(
					'section_google_sheets',
					array(
						'label'     => esc_html__( 'Save Data in Google Sheets', 'elementor-contact-form-db' ),
						'tab'       => 'connect_google_sheets_tab',
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);
				
				$fdbgp_spreadsheets = array();
				try {
					$fdbgp_spreadsheets = $instance_api->get_spreadsheet_listing();
				} catch ( Exception $e ) {
					$fdbgp_spreadsheets = array();
					error_log("Error fetching spreadsheets: " . $e->getMessage());
				}
				
				$fdbgp_spreadsheets = array( '' => esc_html__( 'Please Select a Spreadsheet', 'elementor-contact-form-db' ) ) + $fdbgp_spreadsheets;

				$widget->add_control(
					$this->add_prefix('spreadsheetid'),
					array(
						'label'       => esc_attr__( 'Select Spreadsheet', 'elementor-contact-form-db' ),
						'type'        => Controls_Manager::SELECT,
						'default'     => '',
						'options'     => $fdbgp_spreadsheets,
						'label_block' => true,
						'render_type' => 'ui', // Ensure UI update triggers AJAX
					)
				);
				
				$widget->add_control(
					$this->add_prefix('new_spreadsheet_name'),
					array(
						'label'       => esc_attr__( 'Spreadsheet Name', 'elementor-contact-form-db' ),
						'type'        => Controls_Manager::TEXT,
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
						'placeholder' => 'Please Select a Spreadsheet Name',
					)
				);
				$widget->add_control(
					$this->add_prefix('sheet_name'),
					array(
					'label'       => esc_attr__( 'Sheet Tab Name', 'elementor-contact-form-db' ),
						'type'        => Controls_Manager::TEXT,
						// 'placeholder' => 'Please Select a Sheet Tab Name',
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
						'placeholder' => 'Please Select a Sheet Tab Name',
					)
				);
				
				// $widget->add_control(
				// 	$this->add_prefix('new_sheet_tab_name'),
				// 	array(
				// 		'label'       => esc_attr__( 'New Sheet Tab Name', 'elementor-contact-form-db' ),
				// 		'type'        => Controls_Manager::TEXT,
				// 		'label_block' => true,
				// 		'condition'   => array(
				// 			$this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
				// 			$this->add_prefix('sheet_list') => 'create_new_tab',
				// 		),
				// 		'placeholder' => 'e.g. Sheet2',
				// 	)
				// );
				// $widget->add_control(
				// 	$this->add_prefix('sheet_headers'),
				// 	array(
				// 		'label'       => esc_attr__( 'Sheet Headers', 'elementor-contact-form-db' ),
				// 		'type'        => 'fdbgp_dynamic_select2', // Matches the get_type() in Step 1
				// 		'label_block' => true,
						
				// 	)
				// );
				// var_dump($fdbgp_seets);die();
// ... (Previous controls like 'sheet_name' text field) ...

        // PASTE THE MOVED CODE HERE:
        $fdbgp_sheets = array( '' => esc_html__( 'Please Select Sheet Tab Name', 'elementor-contact-form-db' ) );
        $fdbgp_sheets['create_new_tab'] = esc_html__( 'Create New Tab', 'elementor-contact-form-db' );
        
        if ( ! empty( $local_spreadsheet_id ) && $local_spreadsheet_id !== 'new' ) {
            try {
                $response = $instance_api->get_sheet_listing( $local_spreadsheet_id );
                foreach ( $response->getSheets() as $s ) {
                    $title = $s['properties']['title'];
                    $fdbgp_sheets[ $title ] = $title;
                }
            } catch ( Exception $e ) {
                error_log("Error fetching sheets for ID $local_spreadsheet_id: " . $e->getMessage());
            }
        }
        
        if ( ! empty( $local_sheet_name ) && ! isset( $fdbgp_sheets[ $local_sheet_name ] ) && $local_sheet_name !== 'create_new_tab' ) {
            $fdbgp_sheets[ $local_sheet_name ] = $local_sheet_name;
        }
        // END OF PASTED CODE

        // NOW ADD THE CONTROL (Because $fdbgp_sheets is now full of data)
        $widget->add_control(
            $this->add_prefix('sheet_list'),
            array(
                'label'       => esc_attr__( 'Select Sheet Tab Name', 'elementor-contact-form-db' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => '',
                'options'     => $fdbgp_sheets, // This will now work!
                'label_block' => true,
                'condition'   => array(
                    $this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
                ),
                'render_type' => 'ui',
            )
        );
// $widget->add_control(
//             $this->add_prefix('sheet_sssslist'),
//             array(
//                 'label'       => esc_attr__( 'Select Sheet Tab Name', 'elementor-contact-form-db' ),
//                 'type'        => Controls_Manager::SELECT,
//                 'default'     => '',
//                 'options'     => array('1'=>'1','2'=>'2','3'=>'3'), // This will now work!
//                 'label_block' => true,
//                 'condition'   => array(
//                     $this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
//                 ),
//                 'render_type' => 'ui',
//             )
//         );
        // ... (Continue with 'new_sheet_tab_name', 'sheet_headers', etc.) ...
				// $widget->add_control(
				// 	$this->add_prefix('sheet_list'),
				// 	array(
				// 		'label'       => esc_attr__( 'Select Sheet Tab Name', 'elementor-contact-form-db' ),
				// 		'type'        => Controls_Manager::SELECT,
				// 		'default'     => '',
				// 		'options'     => $fdbgp_sheets,
				// 		'label_block' => true,
				// 		'condition'   => array(
				// 			$this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
				// 		),
				// 		'render_type' => 'ui', // Ensure UI update triggers AJAX
				// 	)
				// );

				$widget->add_control(
					$this->add_prefix('new_sheet_tab_name'),
					array(
						'label'       => esc_attr__( 'New Sheet Tab Name', 'elementor-contact-form-db' ),
						'type'        => Controls_Manager::TEXT,
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
							$this->add_prefix('sheet_list') => 'create_new_tab',
						),
						'placeholder' => 'e.g. Sheet2',
					)
				);
				$widget->add_control(
					$this->add_prefix('sheet_headers'),
					array(
						'label'       => esc_attr__( 'Select a data to save in sheet', 'fdbgp' ),
						'type'        => 'fdbgp_dynamic_select2',
						'label_block' => true,
						'multiple'    => true, 
						// These are the extra fields you requested
						'options'     => array(
							'user_ip'         => esc_html__( 'User IP', 'fdbgp' ),
							'user_agent'      => esc_html__( 'User Agent', 'fdbgp' ),
							
							'page_url'        => esc_html__( 'Page URL', 'fdbgp' ),
							'submission_date' => esc_html__( 'Submission Date & Time', 'fdbgp' ),
						),
						'condition'   => array(
							$this->add_prefix('spreadsheetid') . '!' => array('' ),
						),
						'placeholder' => 'Please Select a data to save in sheet',
					)
    			);
				// $widget->add_control(
				// 	$this->add_prefix('sheet_headers'),
				// 	array(
				// 		'label'       => esc_attr__( 'Sheet Headers', 'elementor-contact-form-db' ),
				// 		'type'        => 'fdbgp_dynamic_select2',
				// 		'label_block' => true,
				// 		'multiple'    => true, // Allows selecting multiple headers
				// 		'options'     => array(
				// 			'user_ip'         => esc_html__( 'User IP', 'elementor-contact-form-db' ),
				// 			'user_agent'      => esc_html__( 'User Agent', 'elementor-contact-form-db' ),
				// 			'submission_date' => esc_html__( 'Submission Date & Time', 'elementor-contact-form-db' ),
				// 			'page_url'        => esc_html__( 'Page URL', 'elementor-contact-form-db' ),
				// 		),
				// 	)
				// );
			
				// Add Create Spreadsheet Now button using RAW_HTML
				$widget->add_control(
					$this->add_prefix('create_spreadsheet_button'),
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw' => '<button type="button" class="elementor-button elementor-button-info fdbgp-create-spreadsheet">
							<span class="elementor-button-text">Create Spreadsheet</span>
						</button>
						<div id="fdbgp-message" class="elementor-control-alert elementor-panel-alert elementor-panel-alert-success" style="display:none;margin-top: 10px;"></div>',
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
					)
				);


			
			
				// $fdbgp_sheets = array( '' => esc_html__( 'Please Select Sheet Tab Name', 'elementor-contact-form-db' ) );
				// // Add '' option first
				// $fdbgp_sheets['create_new_tab'] = esc_html__( 'Create New Tab', 'elementor-contact-form-db' );
				
				// // Use local sheet ID to fetch sheets
				// if ( ! empty( $local_spreadsheet_id ) && $local_spreadsheet_id !== 'new' ) {
				// 	try {
				// 		$response = $instance_api->get_sheet_listing( $local_spreadsheet_id );
				// 		foreach ( $response->getSheets() as $s ) {
				// 			$title = $s['properties']['title'];
				// 			$fdbgp_sheets[ $title ] = $title;
				// 		}
				// 	} catch ( Exception $e ) {
				// 		error_log("Error fetching sheets for ID $local_spreadsheet_id: " . $e->getMessage());
				// 	}
				// }
				
				// // Fallback: If we have a saved sheet name but the list is empty (e.g. API fail),
				// // add the saved name to the list so the dropdown isn't blank.
				// // Careful not to overwrite if saved name is 'create_new_tab'
				// // Also, checking if it is already in the array keys (titles are keys here? No, in the loop above: $fdbgp_sheets[ $title ] = $title;)
				// // So keys are titles.
				// if ( ! empty( $local_sheet_name ) && ! isset( $fdbgp_sheets[ $local_sheet_name ] ) && $local_sheet_name !== 'create_new_tab' ) {
				// 	$fdbgp_sheets[ $local_sheet_name ] = $local_sheet_name;
				// }

		

				$widget->add_control(
					$this->add_prefix('update_sheet_button'),
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw' => '<button type="button" class="elementor-button elementor-button-info fdbgp-update-sheet">
							<span class="elementor-button-text">Update Sheet</span>
						</button>
						<div id="fdbgp-update-message" class="elementor-control-alert elementor-panel-alert elementor-panel-alert-danger" style="margin-top:10px;display:none;"></div>',
						// 'condition'   => array(
							'condition'   => array(
							$this->add_prefix('spreadsheetid') . '!' => array( 'new', '' ),
						),
							// $this->add_prefix('spreadsheetid') . '!' => 'new',
						// ),
					)
				);

				
		
				
				// End of register_settings_section
				$widget->end_controls_section();
			}
		
	}



	public function ajax_create_spreadsheet() {
		// Check nonce and permissions
		check_ajax_referer( 'elementor_ajax', '_nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$spreadsheet_name = isset( $_POST['spreadsheet_name'] ) ? sanitize_text_field( $_POST['spreadsheet_name'] ) : '';
		$sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
		$headers = isset( $_POST['headers'] ) && is_array( $_POST['headers'] ) ? array_map( 'sanitize_text_field', $_POST['headers'] ) : array();
		
		// Validate input
		if ( empty( $spreadsheet_name ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a Spreadsheet Name' ) );
		}
		
		if ( empty( $sheet_name ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a Sheet Name' ) );
		}
		
		try {
			$instance_api = new FDBGP_Google_API_Functions();
			
			if ( ! $instance_api->checkcredenatials() ) {
				wp_send_json_error( array( 'message' => 'Google API credentials not configured' ) );
			}
			
			// Create new spreadsheet with initial sheet
			$spreadsheet_object = $instance_api->newspreadsheetobject( $spreadsheet_name, $sheet_name );
			$created_spreadsheet = $instance_api->createspreadsheet( $spreadsheet_object );
			$spreadsheet_id = $created_spreadsheet->getSpreadsheetId();
			
			// Get the sheet ID of the newly created sheet
			$sheets = $created_spreadsheet->getSheets();
			$sheet_id = $sheets[0]['properties']['sheetId'];
			
			// Add headers if provided
			if ( ! empty( $headers ) ) {
				$header_range = $sheet_name . '!A1';
				$header_body = $instance_api->valuerangeobject( array( $headers ) );
				$header_params = FDBGP_Google_API_Functions::get_row_format();
				$header_param = $instance_api->setparamater( $spreadsheet_id, $header_range, $header_body, $header_params );
				$instance_api->updateentry( $header_param );
				
				// Freeze header row
				$freeze_object = $instance_api->freezeobject( $sheet_id, 1 );
				$freeze_param = array(
					'spreadsheetid' => $spreadsheet_id,
					'requestbody' => $freeze_object
				);
				$instance_api->formatsheet( $freeze_param );
			}
			
			wp_send_json_success( array(
				'message'          => 'Spreadsheet created successfully!',
				'spreadsheet_id'   => $spreadsheet_id,
				'spreadsheet_name' => $spreadsheet_name,
				'sheet_name'       => $sheet_name,
			) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}
	
	/**
	 * AJAX Handler: Get Sheets from Spreadsheet
	 * Returns list of sheets from a given spreadsheet
	 *
	 * @access public
	 */
	public function ajax_get_sheets() {
		// Check nonce and permissions
		check_ajax_referer( 'elementor_ajax', '_nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( $_POST['spreadsheet_id'] ) : '';
		
		if ( empty( $spreadsheet_id ) ) {
			wp_send_json_error( array( 'message' => 'Spreadsheet ID is required' ) );
		}
		
		try {
			$instance_api = new FDBGP_Google_API_Functions();
			
			if ( ! $instance_api->checkcredenatials() ) {
				wp_send_json_error( array( 'message' => 'Google API credentials not configured' ) );
			}
			
			$response = $instance_api->get_sheet_listing( $spreadsheet_id );
			$sheets = array();
			
			// Add Create New Tab option
			$sheets['create_new_tab'] = esc_html__( 'Create New Tab', 'elementor-contact-form-db' );
			
			foreach ( $response->getSheets() as $s ) {
				$title = $s['properties']['title'];
				$sheets[ $title ] = $title;
			}
			
			wp_send_json_success( array( 'sheets' => $sheets ) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}
	
	
	/**
	 * Update Spreadsheet ID in Elementor Data
	 * Recursively searches and updates the spreadsheet ID in Elementor data
	 *
	 * @access private
	 * @param array  &$data Reference to Elementor data array.
	 * @param string $form_id The form ID to match.
	 * @param string $new_spreadsheet_id The new spreadsheet ID.
	 * @return bool Whether the update was successful.
	 */
	private function update_spreadsheet_id_in_data( &$data, $form_id, $new_spreadsheet_id ) {
		
		if ( ! is_array( $data ) ) {
			return false;
		}
		
		foreach ( $data as &$element ) {
			// Check if this is the form we're looking for
			if ( isset( $element['id'] ) && $element['id'] === $form_id ) {
				if ( isset( $element['settings'][$this->add_prefix('spreadsheetid')] ) ) {
					$element['settings'][$this->add_prefix('spreadsheetid')] = $new_spreadsheet_id;
					return true;
				}
			}
			
			// Recursively check nested elements
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				if ( $this->update_spreadsheet_id_in_data( $element['elements'], $form_id, $new_spreadsheet_id ) ) {
					return true;
				}
			}
		}
		
		return false;
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
	public function ajax_update_sheet_headers() {
		check_ajax_referer( 'elementor_ajax', '_nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( $_POST['spreadsheet_id'] ) : '';
		$sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
		$new_sheet_name = isset( $_POST['new_sheet_name'] ) ? sanitize_text_field( $_POST['new_sheet_name'] ) : '';
		$headers = isset( $_POST['headers'] ) && is_array( $_POST['headers'] ) ? array_map( 'sanitize_text_field', $_POST['headers'] ) : array();

		if ( empty( $spreadsheet_id ) ) {
			wp_send_json_error( array( 'message' => 'Spreadsheet ID missing' ) );
		}

		try {
			$api = new FDBGP_Google_API_Functions();
			if ( ! $api->checkcredenatials() ) {
				wp_send_json_error( array( 'message' => 'API Error' ) );
			}

			$target_sheet_name = $sheet_name;

			// Handle Create New Tab
			if ( 'create_new_tab' === $sheet_name ) {
				if ( empty( $new_sheet_name ) ) {
					wp_send_json_error( array( 'message' => 'New Sheet Name is required' ) );
				}
				$target_sheet_name = $new_sheet_name;

				// Check existence
				$existing_sheets = $api->get_sheet_listing( $spreadsheet_id );
				$titles = array();
				foreach ( $existing_sheets->getSheets() as $s ) {
					$titles[] = $s['properties']['title'];
				}

				if ( ! in_array( $target_sheet_name, $titles, true ) ) {
					// Create
					$req = $api->createsheetobject( $target_sheet_name );
					$api->formatsheet( array(
						'spreadsheetid' => $spreadsheet_id,
						'requestbody'   => $req,
					) );
				}
			}

			// Update Headers
			if ( ! empty( $headers ) ) {
				$range = $target_sheet_name . '!A1';
				$body = $api->valuerangeobject( array( $headers ) );
				$params = FDBGP_Google_API_Functions::get_row_format();
				$api->updateentry( $api->setparamater( $spreadsheet_id, $range, $body, $params ) );

				// Freeze
				$sheet_id = 0;
				$refresh = $api->get_sheet_listing( $spreadsheet_id );
				foreach ( $refresh->getSheets() as $s ) {
					if ( $s['properties']['title'] === $target_sheet_name ) {
						$sheet_id = $s['properties']['sheetId'];
						break;
					}
				}
				$api->formatsheet( array(
					'spreadsheetid' => $spreadsheet_id,
					'requestbody'   => $api->freezeobject( $sheet_id, 1 ),
				) );
			}
			
			wp_send_json_success( array(
				'message'    => 'Sheet Updated Successfully!',
				'sheet_name' => $target_sheet_name,
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}
	public function ajax_check_sheet_headers() {

		check_ajax_referer( 'elementor_ajax', '_nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ) );
		}

		$spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( $_POST['spreadsheet_id'] ) : '';
		$sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';

		if ( empty( $spreadsheet_id ) || empty( $sheet_name ) || 'create_new_tab' === $sheet_name ) {
			wp_send_json_success( array( 'has_content' => false ) );
			return; // Explicit return to avoid further processing
		}

		try {
			$api = new FDBGP_Google_API_Functions();
			if ( ! $api->checkcredenatials() ) {
				wp_send_json_error( array( 'message' => 'API Error' ) );
			}

			// Quote sheet name to handle spaces
			$check_range = "'" . $sheet_name . "'!A1:Z1";
			$existing = $api->get_row_list( $spreadsheet_id, $check_range );
			$rows = $existing->getValues();

			if ( ! empty( $rows ) ) {
				wp_send_json_success( array(
					'has_content' => true,
					'message'     => 'Selected sheet is not empty. Backup recommended before updating.',
				) );
			} else {
				wp_send_json_success( array( 'has_content' => false ) );
			}

		} catch ( Exception $e ) {
			// If sheet doesn't exist or other error, assume no content safe to overwrite? 
			// Or return error.
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}
}