<?php

/**

 * Handle plugin installation upon activation.

 *

 * @package wpsyncsheets-elementor

 */

use ElementorPro\Plugin;

use Elementor\Controls_Manager;

use ElementorPro\Modules\Forms\Module;

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;

/**

 * Class FDBGP_Plugin_Setting.

 *

* @since 1.0.0

 */

class FDBGP_Plugin_Setting {

	/**

	 * Plugin documentation URL

	 *

	 * @var $documentation

	 */

	protected static $documentation = 'https://docs.wpsyncsheets.com/wpsse-setup-guide/';

	/**

	 * Url for plugin api settings documentation.

	 *

	 * @var $doc_sheet_setting .

	 */

	protected static $doc_sheet_setting = 'https://docs.wpsyncsheets.com/wpsse-google-sheets-api-settings/';

	/**

	 * Url for plugin support.

	 *

	 * @var $submit_ticket

	 */

	protected static $submit_ticket = 'https://wordpress.org/support/plugin/wpsyncsheets-elementor/';

	/**

	 * Instance of Plugin_Settings

	 *

	 * @var $instance

	 */

	private static $instance = null;

	/**

	 * Instance of WPSSLE_Feed_Settings

	 *

	 * @var $instanceaddon

	 */

	private static $instanceaddon = null;

	/**

	 * Instance of Google_API_Functions

	 *

	 * @var $instance_api

	 */

	private static $instance_api = null;


    public function __construct() {
        $this->wpssle_initilization();
    }
	/**

	 * Initialization

	 */

	public function wpssle_initilization() {
		add_action( 'elementor/editor/after_save', array($this,'wpssle_after_save_settings'), 9999 );

		$this->wpssle_google_api();
	}

	/**

	 * Main FDBGP_Plugin_Settings Instance.

	 *

	 * @since 1.0.0

	 *

	 * @return instance

	 */

	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

	/**

	 * Create Google Api Instance.

	 */

	public function wpssle_google_api() {

		if ( null === self::$instance_api ) {

			self::$instance_api = new FDBGP_Google_API_Functions();

		}

		return self::$instance_api;

	}

    public function add_prefix($id){
		return 'fdbgp_' . $id;
	}
	/**

	 * Action fire after Save from Elementor Editor.

	 *

	 * @param int $wpssle_post_id Post ID.

	 */

	public function wpssle_after_save_settings( $wpssle_post_id ) {

		global $wpssle_header_list, $wpssle_spreadsheetid, $wpssle_exclude_headertype;

		$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );

		// phpcs:ignore

		if ( ! isset( $_REQUEST['actions'] ) || empty( $_REQUEST['actions'] ) ) {

			return;

		}

		// phpcs:ignore

		$wpssle_data = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['actions'] ) ) , true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return;
        }

		$wpssle_data = Plugin::elementor()->db->iterate_data(

			$wpssle_data,

			function ( $wpssle_element ) use ( &$do_update ) {

				if ( 'form' === (string) $wpssle_element['widgetType'] || 'global' === (string) $wpssle_element['widgetType'] ) {

					global $wpssle_header_list, $wpssle_spreadsheetid, $wpssle_exclude_headertype;

					$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );

					if ( isset( $wpssle_element['settings'] ) && isset( $wpssle_element['settings']['submit_actions'] ) && in_array( 'Connect Google Sheets', $wpssle_element['settings']['submit_actions'], true ) ) {

						$wpssle_settings      = $wpssle_element['settings'] ?? array();

						$wpssle_header_list   = array();

						$wpssle_spreadsheetid = $wpssle_settings[$this->add_prefix('spreadsheetid')] ?? '';

						$wpssle_sheetname     = $wpssle_settings[$this->add_prefix('sheet_name')] ?? '';

						$wpssle_sheetheaders  = $wpssle_settings[$this->add_prefix('sheet_headers')] ?? array();

						$wpssle_freeze_header = $wpssle_settings[$this->add_prefix('freeze_header')] ?? '';

						if(isset($wpssle_settings['form_fields'])){

							foreach ( $wpssle_settings['form_fields'] as $wpssle_form_fields ) {

								if ( ( ! isset( $wpssle_form_fields['field_type'] ) || ( isset( $wpssle_form_fields['field_type'] ) && ! in_array( $wpssle_form_fields['field_type'], $wpssle_exclude_headertype, true ) ) ) && in_array( $wpssle_form_fields['custom_id'], $wpssle_sheetheaders, true ) ) {

									$wpssle_header_list[] = $wpssle_form_fields['field_label'] ? $wpssle_form_fields['field_label'] : ucfirst( $wpssle_form_fields['custom_id'] );

								}

							}

						}

						$wpssle_is_new = 0;

						if ( 'new' === (string) $wpssle_spreadsheetid ) {

							$wpssle_newsheetname = $wpssle_settings[$this->add_prefix(('new_spreadsheet_name'))] ? trim( $wpssle_settings[$this->add_prefix('new_spreadsheet_name')] ) : '';

							/*

							 *Create new spreadsheet

							 */

							$requestbody          = self::$instance_api->createspreadsheetobject( $wpssle_newsheetname );

							$wpssle_response      = self::$instance_api->createspreadsheet( $requestbody );

							$wpssle_spreadsheetid = $wpssle_response['spreadsheetId'];

							$wpssle_is_new        = 1;

						}

						$wpssle_existingsheetsnames = array();

						$response                   = self::$instance_api->get_sheet_listing( $wpssle_spreadsheetid );

						$wpssle_existingsheetsnames = self::$instance_api->get_sheet_list( $response );

						$wpsse_sheetid             = isset($wpssle_existingsheetsnames[ $wpssle_sheetname ]) ? $wpssle_existingsheetsnames[ $wpssle_sheetname ] : '';

						if (! $wpsse_sheetid ) {

							/*

							 *Create new sheet into spreadsheet

							 */

							$wpssle_body = self::$instance_api->createsheetobject( $wpssle_sheetname );

							try {

								$requestobject                  = array();

								$requestobject['spreadsheetid'] = $wpssle_spreadsheetid;

								$requestobject['requestbody']   = $wpssle_body;

								self::$instance_api->formatsheet( $requestobject );

							} catch ( Exception $e ) {

								echo esc_html( $e->getMessage() );

							}

							/*

							 * Insert Sheet Headers into sheet

							 */

							$wpssle_header_list = array_values( array_unique( $wpssle_header_list ) );

							$wpssle_range       = trim( $wpssle_sheetname ) . '!A1';

							$wpssle_requestbody = self::$instance_api->valuerangeobject( array( $wpssle_header_list ) );

							$wpssle_params      = self::$instance->get_row_format();

							$param              = self::$instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_range, $wpssle_requestbody, $wpssle_params );

							self::$instance_api->appendentry( $param );

							if ( $wpssle_is_new ) {

								$wpssle_requestbody             = self::$instance_api->deletesheetobject();

								$requestobject                  = array();

								$requestobject['spreadsheetid'] = $wpssle_spreadsheetid;

								$requestobject['requestbody']   = $wpssle_requestbody;

								self::$instance_api->formatsheet( $requestobject );

							}

						} else {

							$wpssle_range    = trim( $wpssle_sheetname ) . '!A1:ZZ1';

							$wpssle_response = self::$instance_api->get_row_list( $wpssle_spreadsheetid, $wpssle_range );

							$wpssle_data     = $wpssle_response->getValues();

							if ( empty( $wpssle_data ) ) {

								$wpssle_data = array();

								$existingheaders = array();

							}else{

								$existingheaders = $wpssle_data[0];

							}

							$deleterequestarray = array();

							$requestarray       = array();

							if ( $existingheaders !== $wpssle_header_list ) {

								// Delete deactivate column from sheet.

								$wpsse_column = array_diff( $existingheaders, $wpssle_header_list );

								if ( ! empty( $wpsse_column ) ) {

									$wpsse_column = array_reverse( $wpsse_column, true );

									foreach ( $wpsse_column as $columnindex => $columnval ) {

										unset( $existingheaders[ $columnindex ] );

										$existingheaders      = array_values( $existingheaders );

										$param                = array();

										$startindex           = $columnindex;

										$endindex             = $columnindex + 1;

										$param                = self::$instance_api->prepare_param( $wpsse_sheetid, $startindex, $endindex );

										$deleterequestarray[] = self::$instance_api->deleteDimensionrequests( $param );

									}

								}

								try {

									if ( ! empty( $deleterequestarray ) ) {

										$param                  = array();

										$param['spreadsheetid'] = $wpssle_spreadsheetid;

										$param['requestarray']  = $deleterequestarray;

										$wpsse_response         = self::$instance_api->updatebachrequests( $param );

									}

								} catch ( Exception $e ) {

									echo esc_html( 'Message: ' . $e->getMessage() );

								}

							}

							if ( $existingheaders !== $wpssle_header_list ) {

								foreach ( $wpssle_header_list as $key => $hname ) {

									$wpsse_startindex = array_search( $hname, $existingheaders, true );

									if ( false !== $wpsse_startindex && ( isset( $existingheaders[ $key ] ) && $existingheaders[ $key ] !== $hname ) ) {

										unset( $existingheaders[ $wpsse_startindex ] );

										$existingheaders = array_merge( array_slice( $existingheaders, 0, $key ), array( 0 => $hname ), array_slice( $existingheaders, $key, count( $existingheaders ) - $key ) );

										$wpsse_endindex     = $wpsse_startindex + 1;

										$wpsse_destindex    = $key;

										$param              = array();

										$param              = self::$instance_api->prepare_param( $wpsse_sheetid, $wpsse_startindex, $wpsse_endindex );

										$param['destindex'] = $wpsse_destindex;

										$requestarray[]     = self::$instance_api->moveDimensionrequests( $param );

									} elseif ( false === $wpsse_startindex ) {

										$existingheaders = array_merge( array_slice( $existingheaders, 0, $key ), array( 0 => $hname ), array_slice( $existingheaders, $key, count( $existingheaders ) - $key ) );

										$param                  = array();

										$wpsse_startindex       = $key;

										$wpsse_endindex         = $key + 1;

										$param                  = self::$instance_api->prepare_param( $wpsse_sheetid, $wpsse_startindex, $wpsse_endindex );

										$requestarray[] = self::$instance_api->insertdimensionrequests( $param, 'COLUMNS', false );

									}

								}

								if ( ! empty( $requestarray ) ) {

									$param                  = array();

									$param['spreadsheetid'] = $wpssle_spreadsheetid;

									$param['requestarray']  = $requestarray;

									$wpsse_response         = self::$instance_api->updatebachrequests( $param );

								}

								if ( count( $existingheaders ) > count( $wpssle_header_list ) ) {

									$diff = count( $existingheaders ) - count( $wpssle_header_list );

									$wpssle_header_list = array_merge( $wpssle_header_list, array_fill( 0, $diff, '' ) );

								}

							}

							$wpssle_range       = trim( $wpssle_sheetname ) . '!A1';

							$wpssle_requestbody = self::$instance_api->valuerangeobject( array( $wpssle_header_list ) );

							$wpssle_params      = self::$instance->get_row_format();

							$param              = self::$instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_range, $wpssle_requestbody, $wpssle_params );

							self::$instance_api->updateentry( $param );

						}

						if ( 'yes' === (string) $wpssle_freeze_header ) {

							$wpssle_freeze = 1;

						} else {

							$wpssle_freeze = 0;

						}

						self::$instance->wpssle_freeze_header( $wpssle_spreadsheetid, $wpssle_sheetname, $wpssle_freeze );

					}

				}

			}

		);

		if ( ! empty( $wpssle_spreadsheetid ) && 'new' !== (string) $wpssle_spreadsheetid ) {

			$wpssle_saved_data = get_post_meta( $wpssle_post_id, '_elementor_data' );

			$wpssle_data = json_decode( $wpssle_saved_data[0], true );

			global $existincurrentpage;

			$existincurrentpage = 'no';

			array_walk_recursive(

				$wpssle_data,

				function ( &$existvalue ) {

					if ( 'Connect Google Sheets' === (string) $existvalue ) {

						global $existincurrentpage;

						$existincurrentpage = 'yes';

					}

				}

			);

			array_walk_recursive(

				$wpssle_data,

				function ( &$existvalue, $existkey ) {

					if ( 'widgetType' === (string) $existkey ) {

						global $existincurrentpage;

						if ( 'form' === (string) $existvalue ) {

							$existincurrentpage = 'yes';

						} else {

							$existincurrentpage = 'no';

						}

					}

				}

			);

			array_walk_recursive(

				$wpssle_data,

				function ( &$value, $key ) {

					global $existincurrentpage, $wpssle_spreadsheetid;

					if ( 'yes' === (string) $existincurrentpage ) {

						if ( $this->add_prefix('spreadsheetid') === (string) $key ) {

							$value = $wpssle_spreadsheetid;

						}

						if ( $this->add_prefix('new_spreadsheet_name') === (string) $key ) {

							$value = '';

						}

					}

				}

			);

			if ( 'yes' === (string) $existincurrentpage ) {

				$wpssle_json_value = wp_slash( wp_json_encode( $wpssle_data ) );

				update_post_meta( $wpssle_post_id, '_elementor_data', $wpssle_json_value );

			}

		}

	}

	/**

	 * Freeze First Row of the Google Spreadsheet.

	 *

	 * @param string $wpssle_spreadsheetname Spreadsheet ID.

	 * @param string $wpssle_sheetname Sheet Name.

	 * @param int    $wpssle_freeze 1 - Freeze Header, 0 - Unfreeze header.

	 */

	public static function wpssle_freeze_header( $wpssle_spreadsheetname, $wpssle_sheetname, $wpssle_freeze ) {

		$response                   = self::$instance_api->get_sheet_listing( $wpssle_spreadsheetname );

		$wpssle_existingsheetsnames = self::$instance_api->get_sheetid_list( $response );

		$wpssle_is_exist = array_search( $wpssle_sheetname, $wpssle_existingsheetsnames, true );

		if ( $wpssle_is_exist ) {

			$requestbody                    = self::$instance_api->freezeobject( $wpssle_is_exist, $wpssle_freeze );

			$requestobject                  = array();

			$requestobject['spreadsheetid'] = $wpssle_spreadsheetname;

			$requestobject['requestbody']   = $requestbody;

			self::$instance_api->formatsheet( $requestobject );

		}

	}
	/**

	 * Prepare Google Spreadsheet list.

	 *

	 * @return array $sheetarray Spreadsheet List.

	 */

	public static function wpssle_list_googlespreedsheet() {

		/* Build choices array. */

		$sheetarray = array(

			'' => esc_html__( 'Select Google Spreeadsheet', 'wpsse' ),

		);

		$sheetarray = self::$instance_api->get_spreadsheet_listing( $sheetarray );

		return $sheetarray;

	}
	/**

	 * Change the row format of spreadsheet.

	 */

	public static function get_row_format() {

		$params = array( 'valueInputOption' => 'RAW' );

		return $params;

	}
}

FDBGP_Plugin_Setting::instance();