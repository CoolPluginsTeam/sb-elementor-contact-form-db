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
                    <select class="elementor-control-dynamic-select2" data-setting="{{ data.name }}" multiple="multiple" style="width:100%"></select>
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
		
		// Load scripts in editor
		// add_action( 'elementor/editor/footer', array( $this, 'fdbgp_admin_footer_scripts' ) );
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
public function render_editor_script() {

	wp_enqueue_script( 
        'fdbgp-editor-script', // Unique Handle
        FDBGP_PLUGIN_URL. 'assets/js/fdbgp-editor.js', // Path to your new JS file
        [ 'elementor-editor', 'jquery' ], // Dependencies: waits for Elementor & jQuery
        '1.0.0', // Version
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
		$wpssle_settings = $record->get( 'form_settings' );
		// Get sumitetd Form data.
		$wpssle_raw_fields = $record->get( 'fields' );
		$instance_api      = new FDBGP_Google_API_Functions();
		if ( ! $instance_api->checkcredenatials() ) {
			return;
		}
		if ( isset( $wpssle_settings['submit_actions'] ) && in_array( $this->get_name(), $wpssle_settings['submit_actions'], true ) ) {
			$wpssle_spreadsheetid = $wpssle_settings[$this->add_prefix('spreadsheetid')];
			
			if ( 'new' === $wpssle_spreadsheetid ) {
				$wpssle_sheetname = $wpssle_settings[$this->add_prefix('sheet_name')];
			} else {
				$wpssle_sheetname = isset( $wpssle_settings[$this->add_prefix('sheet_list')] ) ? $wpssle_settings[$this->add_prefix('sheet_list')] : '';
				// Fallback to text field if list is empty (backward compatibility)
				if ( empty( $wpssle_sheetname ) && isset( $wpssle_settings[$this->add_prefix('sheet_name')] ) ) {
					$wpssle_sheetname = $wpssle_settings[$this->add_prefix('sheet_name')];
				}
			}
			
			
			$is_newly_created = false;
			
			// Handle new spreadsheet creation
			if ( $wpssle_spreadsheetid === 'new' ) {
				try {
					$wpssle_new_spreadsheet_name = isset( $wpssle_settings[$this->add_prefix('new_spreadsheet_name')] ) ? $wpssle_settings[$this->add_prefix('new_spreadsheet_name')] : '';
					
					// Validate required fields with helpful error messages
					if ( empty( $wpssle_new_spreadsheet_name ) && empty( $wpssle_sheetname ) ) {
						$ajax_handler->add_admin_error_message( 'WPSyncSheets: Please enter both a Spreadsheet Name and a Sheet Name to create a new spreadsheet.' );
						return;
					} elseif ( empty( $wpssle_new_spreadsheet_name ) ) {
						$ajax_handler->add_admin_error_message( 'WPSyncSheets: Please enter a Spreadsheet Name to create a new spreadsheet.' );
						return;
					} elseif ( empty( $wpssle_sheetname ) ) {
						$ajax_handler->add_admin_error_message( 'WPSyncSheets: Please enter a Sheet Name to create a new spreadsheet.' );
						return;
					}
					
					// Create new spreadsheet with initial sheet
					$spreadsheet_object = $instance_api->newspreadsheetobject( $wpssle_new_spreadsheet_name, $wpssle_sheetname );
					$created_spreadsheet = $instance_api->createspreadsheet( $spreadsheet_object );
					$wpssle_spreadsheetid = $created_spreadsheet->getSpreadsheetId();
					$is_newly_created = true;
					
					// Get the sheet ID of the newly created sheet
					$sheets = $created_spreadsheet->getSheets();
					$sheet_id = $sheets[0]['properties']['sheetId'];
					
					// Add headers to the new spreadsheet
					$wpssle_headers_data = $wpssle_settings[$this->add_prefix('sheet_headers')];
					if ( is_array( $wpssle_headers_data ) && ! empty( $wpssle_headers_data ) ) {
						// Get form fields to map headers
						$wpssle_raw_fields = $record->get( 'fields' );
						$wpssle_header_labels = array();
						
						foreach ( $wpssle_headers_data as $field_id ) {
							if ( isset( $wpssle_raw_fields[ $field_id ] ) ) {
								$wpssle_header_labels[] = $wpssle_raw_fields[ $field_id ]['title'];
							} else {
								$wpssle_header_labels[] = ucfirst( str_replace( '_', ' ', $field_id ) );
							}
						}
						
						// Add header row
						$wpssle_header_range = $wpssle_sheetname . '!A1';
						$wpssle_header_body = $instance_api->valuerangeobject( array( $wpssle_header_labels ) );
						$wpssle_header_params = FDBGP_Google_API_Functions::get_row_format();
						$header_param = $instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_header_range, $wpssle_header_body, $wpssle_header_params );
						$instance_api->updateentry( $header_param );
						
						// Freeze header row if enabled
						$wpssle_freeze_header = isset( $wpssle_settings[$this->add_prefix('freeze_header')] ) ? $wpssle_settings[$this->add_prefix('freeze_header')] : '';
						if ( $wpssle_freeze_header === 'yes' ) {
							$freeze_object = $instance_api->freezeobject( $sheet_id, 1 );
							$freeze_param = array(
								'spreadsheetid' => $wpssle_spreadsheetid,
								'requestbody' => $freeze_object
							);
							$instance_api->formatsheet( $freeze_param );
						}
					}
					
					// Update the form settings to save the new spreadsheet ID
					// This ensures subsequent form submissions use the created spreadsheet
					$post_id = get_the_ID();
					if ( $post_id ) {
						$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
						if ( ! empty( $elementor_data ) ) {
							$elementor_data = json_decode( $elementor_data, true );
							$this->update_spreadsheet_id_in_data( $elementor_data, $record->get( 'form_settings' )['id'], $wpssle_spreadsheetid );
							update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
						}
					}
					
				} catch ( Exception $e ) {
					$ajax_handler->add_admin_error_message( 'WPSyncSheets: Failed to create spreadsheet - ' . $e->getMessage() );
					return;
				}
			}
			
			// Only validate existence for existing spreadsheets
			// Newly created ones might not appear in the list immediately due to caching/latency
			if ( ! $is_newly_created ) {
				$wpssle_sheetarray    = $instance_api->get_spreadsheet_listing();
				if ( ! empty( $wpssle_spreadsheetid ) && ! array_key_exists( $wpssle_spreadsheetid, $wpssle_sheetarray ) ) {
					error_log( 'WPSyncSheets Error: Spreadsheet ID not found in listing.' );
					return;
				} elseif ( ! empty( $wpssle_spreadsheetid ) ) {
					$wpssle_sheets = array(); // Initialize $wpssle_sheets here
					$response = $instance_api->get_sheet_listing( $wpssle_spreadsheetid );
					foreach ( $response->getSheets() as $s ) {
						$wpssle_sheets[] = $s['properties']['title'];
					}
					
					// Fix for legacy settings: If sheet name is an index (e.g. "0"), try to resolve it to the actual name
					if ( is_numeric( $wpssle_sheetname ) && ! in_array( $wpssle_sheetname, $wpssle_sheets, true ) ) {
						$index = (int) $wpssle_sheetname;
						if ( isset( $wpssle_sheets[ $index ] ) ) {
							$wpssle_sheetname = $wpssle_sheets[ $index ];
						}
					}
					
					if ( ! empty( $wpssle_sheetname ) && ! in_array( $wpssle_sheetname, $wpssle_sheets, true ) ) {
						error_log( 'WPSyncSheets Error: Sheet Name not found in spreadsheet.' );
						return;
					}
				}
			}
			if ( ( empty( $wpssle_spreadsheetid ) && $wpssle_spreadsheetid !== '0' ) || ( empty( $wpssle_sheetname ) && $wpssle_sheetname !== '0' ) ) {
				error_log( 'WPSyncSheets Error: Missing Spreadsheet ID or Sheet Name.' );
				return;
			}
			// Normalize the Form Data.
			$wpssle_fields = array();
			foreach ( $wpssle_raw_fields as $id => $field ) {
				$wpssle_fields[ $id ] = $field['value'];
			}
			try {
				$wpssle_headers = isset($wpssle_settings[$this->add_prefix('sheet_headers')]) ? $wpssle_settings[$this->add_prefix('sheet_headers')] : [];
				
				// Fallback: If no headers selected, send ALL fields
				if ( empty($wpssle_headers) || !is_array($wpssle_headers) ) {
					$wpssle_headers = array_keys($wpssle_fields);
				}

				$wpssle_value_data = array();
				foreach ( $wpssle_headers as $wpssle_fieldvalue ) {
					if ( array_key_exists( $wpssle_fieldvalue, $wpssle_fields ) ) {
						if ( is_array( $wpssle_fields[ $wpssle_fieldvalue ] ) ) {
							$wpssle_value_data[] = implode( ',', $wpssle_fields[ $wpssle_fieldvalue ] );
						} else {
							$wpssle_value_data[] = $wpssle_fields[ $wpssle_fieldvalue ];
						}
					} else {
						$wpssle_value_data[] = '';
					}
				}
				$wpssle_sheet         = "'" . $wpssle_sheetname . "'!A:A";
				$wpssle_allentry      = $instance_api->get_row_list( $wpssle_spreadsheetid, $wpssle_sheet );
				$wpssle_data          = $wpssle_allentry->getValues();
				
				// Fix: Handle null data (empty sheet) to avoid PHP 8 fatal error in array_map
				if ( is_null( $wpssle_data ) ) {
					$wpssle_data = array();
				}

				$wpssle_data          = array_map(
					function ( $wpssle_element ) {
						if ( isset( $wpssle_element['0'] ) ) {
							return $wpssle_element['0'];
						} else {
							return '';
						}
					},
					$wpssle_data
				);
				// Safely quote the sheet name for the update range as well
				$wpssle_rangetoupdate = "'" . $wpssle_sheetname . "'!A" . ( count( $wpssle_data ) + 1 );
				$wpssle_requestbody   = $instance_api->valuerangeobject( array( $wpssle_value_data ) );
				$wpssle_params        = FDBGP_Google_API_Functions::get_row_format();
				$param                = $instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_rangetoupdate, $wpssle_requestbody, $wpssle_params );
				$instance_api->updateentry( $param );
			} catch ( Exception $e ) {
				error_log( 'WPSyncSheets Exception: ' . $e->getMessage() );
				$ajax_handler->add_admin_error_message( 'WPSyncSheets ' . $e->getMessage() );
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
			$wpssle_google_settings = $instance_api->get_google_creds();
			
			// Local variables to replace globals
			$local_spreadsheet_id = '';
			$local_sheet_name = '';
			$local_sheet_headers = array();
			$local_headers = array();
// -------------------------------------------------------------
// $settings = $widget->get_settings_for_display();
			$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );
			
			$existincurrentpage        = 'no';
			$wpssle_sheetheaders       = array();
			$wpssle_sheetheaders_new   = array();
			$wpssle_form_fields        = array();
			$wpssle_document           = Plugin::elementor()->documents->get( get_the_ID() );
			
			if ( $wpssle_document ) {
				$wpssle_data        = $wpssle_document->get_elements_data();
				$wpssle_data_global = $wpssle_data;
				global $wpssle_type;
				$wpssle_type = '';
				
				$widget_id = $widget->get_id();
				
				$wpssle_data = Plugin::elementor()->db->iterate_data(
					$wpssle_data,
					function( $element ) use ( &$do_update, &$local_headers, &$local_spreadsheet_id, &$local_sheet_name, &$local_sheet_headers, $wpssle_exclude_headertype, $widget_id ) {
						if ( isset( $element['widgetType'] ) && 'form' === (string) $element['widgetType'] ) {
							// Strict check removed for debugging/fallback. 
							// We will try to match ID if present, but if we are arguably the only form or ID is glitchy, we take what we find.
							/*
							if ( ! empty( $element['id'] ) && (string)$widget_id !== (string)$element['id'] ) {
								return $element;
							}
							*/
							// Also check if this element HAS the settings we need. If not, don't overwrite local vars with emptiness.
							
							if ( isset( $element['settings'][$this->add_prefix('spreadsheetid')] ) ) {
								$local_spreadsheet_id = $element['settings'][$this->add_prefix('spreadsheetid')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_list')] ) ) {
								$local_sheet_name = $element['settings'][$this->add_prefix('sheet_list')];
							} elseif ( isset( $element['settings'][$this->add_prefix('sheet_name')] ) ) {
								$local_sheet_name = $element['settings'][$this->add_prefix('sheet_name')];
							}
							if ( isset( $element['settings'][$this->add_prefix('sheet_headers')] ) ) {
								$local_sheet_headers = $element['settings'][$this->add_prefix('sheet_headers')];
							}
							foreach ( $element['settings']['form_fields'] as $formdata ) {
								if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $wpssle_exclude_headertype, true ) ) ) {
									$local_headers[ $formdata['custom_id'] ] = $formdata['field_label'] ? $formdata['field_label'] : ucfirst( $formdata['custom_id'] );
								}
							}
							return $element;
						}
					}
				);
				
				if ( empty( $local_headers ) ) {
					Plugin::elementor()->db->iterate_data(
						$wpssle_data_global,
						function( $element ) use ( &$do_update, &$local_headers, &$local_spreadsheet_id, &$local_sheet_name, &$local_sheet_headers, $wpssle_exclude_headertype ) {
							if ( isset( $element['widgetType'] ) && 'global' === (string) $element['widgetType'] ) {
								if ( ! empty( $element['templateID'] ) ) {
									$global_form      = get_post_meta( $element['templateID'], '_elementor_data', true );
									$global_form_meta = json_decode( $global_form, true );
									if ( $global_form_meta ) {
										
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('spreadsheetid')] ) ) {
											$local_spreadsheet_id = $global_form_meta[0]['settings'][$this->add_prefix('spreadsheetid')];
										}
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('sheet_list')] ) ) {
											$local_sheet_name = $global_form_meta[0]['settings'][$this->add_prefix('sheet_list')];
										} elseif ( isset( $global_form_meta[0]['settings'][$this->add_prefix('sheet_name')] ) ) {
											$local_sheet_name = $global_form_meta[0]['settings'][$this->add_prefix('sheet_name')];
										}
										if ( isset( $global_form_meta[0]['settings'][$this->add_prefix('sheet_headers')] ) ) {
											$local_sheet_headers = $global_form_meta[0]['settings'][$this->add_prefix('sheet_headers')];
										}
										if ( is_array( $global_form_meta[0]['settings']['form_fields'] ) ) {
											foreach ( $global_form_meta[0]['settings']['form_fields'] as $formdata ) {
												if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $wpssle_exclude_headertype, true ) ) ) {
													$local_headers[ $formdata['custom_id'] ] = $formdata['field_label'] ? $formdata['field_label'] : ucfirst( $formdata['custom_id'] );
												}
											}
										}
										return $element;
									}
								}
							}
						}
					);
				}
			}
			  $wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html', 'step' ); // Added 'step'

			if ( ! is_array( $wpssle_sheetheaders ) ) {
				$wpssle_sheetheaders = array();
			}
			if ( empty( $wpssle_google_settings['client_token'] ) ) {
				$wpssle_html = sprintf(
					'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
					esc_html__( 'Please genearate authentication code from Google Sheet Setting', 'wpsse' ),
					esc_html__( 'Click Here', 'wpsse' )
				);
				$widget->start_controls_section(
					'section_google_sheets',
					array(
						'label'     => esc_html__( 'Google Sheets', 'wpsse' ),
						'tab'       => 'connect_google_sheets_tab',
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);

				$widget->add_control(
					'wpssle_html',
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw'  => $wpssle_html,
					)
				);
				$widget->end_controls_section();
			} elseif ( ! empty( $wpssle_google_settings['client_token'] ) && ! $instance_api->checkcredenatials() ) {
				$wpssle_error = $instance_api->getClient( 1 );
				if ( 'Invalid token format' === (string) $wpssle_error || 'invalid_grant' === (string) $wpssle_error ) {
					$wpssle_html = sprintf(
						'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
						esc_html__( 'Error: Invalid Token - Revoke Token with Google Sheet Setting and try again.', 'wpsse' ),
						esc_html__( 'Click Here', 'wpsse' )
					);
				} else {
					$wpssle_html = sprintf(
						'<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s</div>',
						'Error: ' . $wpssle_error
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
						'raw'  => $wpssle_html,
					)
				);
				$widget->end_controls_section();
			} else {
				$widget->start_controls_section(
					'section_google_sheets',
					array(
						'label'     => esc_html__( 'Connect Google Sheets', 'wpsse' ),
						'tab'       => 'connect_google_sheets_tab',
						'condition' => array(
							'submit_actions' => $this->get_name(),
						),
					)
				);
				
				$wpssle_spreadsheets = array();
				try {
					$wpssle_spreadsheets = $instance_api->get_spreadsheet_listing();
				} catch ( Exception $e ) {
					$wpssle_spreadsheets = array();
					error_log("WPSyncSheets Error fetching spreadsheets: " . $e->getMessage());
				}
				
				$widget->add_control(
					$this->add_prefix('spreadsheetid'),
					array(
						'label'       => esc_attr__( 'Select Spreadsheet', 'wpsse' ),
						'type'        => Controls_Manager::SELECT,
						'options'     => $wpssle_spreadsheets,
						'label_block' => true,
						'render_type' => 'ui', // Ensure UI update triggers AJAX
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
					'label'       => esc_attr__( 'Sheet Tab Name', 'wpsse' ),
						'type'        => Controls_Manager::TEXT,
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
					)
				);
			
				// Add Create Spreadsheet Now button using RAW_HTML
				$widget->add_control(
					$this->add_prefix('create_spreadsheet_button'),
					array(
						'type' => Controls_Manager::RAW_HTML,
						'raw' => '<button type="button" class="elementor-button elementor-button-success" style="width:100%; margin-top:10px;" onclick="fdbgpCreateSpreadsheet()">
							<span class="elementor-button-text">Create Spreadsheet Now</span>
						</button>
						<div id="fdbgp-message" style="margin-top:10px; padding:10px; border-radius:3px; display:none;"></div>',
						'condition'   => array(
							$this->add_prefix('spreadsheetid') => 'new',
						),
					)
				);


			
				$wpssle_sheets = array();
				
				// Use local sheet ID to fetch sheets
				if ( ! empty( $local_spreadsheet_id ) && $local_spreadsheet_id !== 'new' ) {
					try {
						$response = $instance_api->get_sheet_listing( $local_spreadsheet_id );
						foreach ( $response->getSheets() as $s ) {
							$title = $s['properties']['title'];
							$wpssle_sheets[ $title ] = $title;
						}
					} catch ( Exception $e ) {
						error_log("WPSyncSheets Error fetching sheets for ID $local_spreadsheet_id: " . $e->getMessage());
					}
				}
				
				// Fallback: If we have a saved sheet name but the list is empty (e.g. API fail),
				// add the saved name to the list so the dropdown isn't blank.
				if ( ! empty( $local_sheet_name ) && ! isset( $wpssle_sheets[ $local_sheet_name ] ) ) {
					$wpssle_sheets[ $local_sheet_name ] = $local_sheet_name;
				}



				$widget->add_control(
					$this->add_prefix('sheet_list'),
					array(
						'label'       => esc_attr__( 'Select Sheet Tab Name', 'wpsse' ),
						'type'        => Controls_Manager::SELECT,
						'options'     => $wpssle_sheets,
						'label_block' => true,
						'condition'   => array(
							$this->add_prefix('spreadsheetid') . '!' => 'new',
						),
						'render_type' => 'ui', // Ensure UI update triggers AJAX
					)
				);

				// Restore Sheet Headers Control
				if ( empty( $local_headers ) ) {
					$local_headers = array( '' => '-- No form fields found --' );
				}
				
				// Fallback: Ensure saved headers are present in options to prevent them from disappearing
				if ( ! empty( $local_sheet_headers ) && is_array( $local_sheet_headers ) ) {
					$available_keys = array_keys( $local_headers ); // e.g. ['name', 'email']
					$available_labels = array_values( $local_headers ); // e.g. ['Name', 'Email']
					
					foreach ( $local_sheet_headers as $saved_header ) {
						if ( ! isset( $local_headers[ $saved_header ] ) ) {
							// If the saved header is numeric (legacy index), try to map it to current fields by position
							if ( is_numeric( $saved_header ) && isset( $available_labels[ (int)$saved_header ] ) ) {
								$mapped_label = $available_labels[ (int)$saved_header ];
								// Remove the default 'placeholder' if it exists (index 0 might conflict if array was empty, but here keys are IDs)
								$local_headers[ $saved_header ] = $mapped_label . ' (Legacy)';
							} else {
								// Standard fallback for unknown IDs
								$local_headers[ $saved_header ] = $saved_header . ' (Saved)';
							}
						}
					}
				}
// var_dump($local_headers);
			// $widget->add_control(
			// 	$this->add_prefix('sheet_headers'),
			// 	array(
			// 		'label'       => esc_attr__( 'Sheet Headers', 'wpsse' ),
			// 		'type'        => Controls_Manager::SELECT2,
			// 		'multiple'    => true,
			// 		'options'     => $local_headers,
			// 		'label_block' => true,
			// 		'description' => esc_attr__( 'Select which form fields to send to Google Sheets. If fields don\'t appear, click Update to save the page.', 'wpsse' ),
			// 	)
			// );
				$widget->add_control(
					$this->add_prefix('sheet_headers'),
					array(
						'label'       => esc_attr__( 'Sheet Headers', 'wpsse' ),
						'type'        => 'fdbgp_dynamic_select2', // Matches the get_type() in Step 1
						'label_block' => true,
						'description' => esc_attr__( 'Fields update automatically as you add them!', 'wpsse' ),
					)
				);

				// Restore Freeze Header Control
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
				
				// End of register_settings_section
				$widget->end_controls_section();
			}
		// }
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
				'message' => 'Spreadsheet created successfully!',
				'spreadsheet_id' => $spreadsheet_id,
				'spreadsheet_name' => $spreadsheet_name,
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
			
			foreach ( $response->getSheets() as $s ) {
				$sheets[] = $s['properties']['title'];
			}
			
			wp_send_json_success( array( 'sheets' => $sheets ) );
			
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}
	
	/**
	 * AJAX Handler: Get Headers from Sheet
	 * Returns list of headers (first row) from a given sheet
	 *
	 * @access public
	 */
	public function ajax_get_headers() {
		// Check nonce and permissions
		check_ajax_referer( 'elementor_ajax', '_nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}
		
		$spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( $_POST['spreadsheet_id'] ) : '';
		$sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
		
		if ( empty( $spreadsheet_id ) || empty( $sheet_name ) ) {
			wp_send_json_error( array( 'message' => 'Spreadsheet ID and Sheet Name are required' ) );
		}
		
		try {
			$instance_api = new FDBGP_Google_API_Functions();
			
			if ( ! $instance_api->checkcredenatials() ) {
				wp_send_json_error( array( 'message' => 'Google API credentials not configured' ) );
			}
			
			$param = array(
				'spreadsheetid' => $spreadsheet_id,
				'sheetname'     => "'" . $sheet_name . "'!A1:Z1", // Quote sheet name to handle spaces
			);
			
			$response = $instance_api->get_values( $instance_api->get_client_object(), $param );
			$values = $response->getValues();
			// Log logic for debugging if needed
			$headers = isset( $values[0] ) ? $values[0] : array();
			
			wp_send_json_success( array( 'headers' => $headers ) );
			
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
}