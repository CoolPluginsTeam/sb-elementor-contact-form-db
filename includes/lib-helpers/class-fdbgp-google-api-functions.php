<?php
namespace Formsdb_Elementor_Forms\Lib_Helpers;

if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API;

/**
 * FDBGP_Google_API_Functions class.
 *
 * @since 1.0.0
 */
class FDBGP_Google_API_Functions extends FDBGP_Google_API {

	/**
	 * Google Sheets Service Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_service = null;

	/**
	 * Google Drive Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_drive = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( self::checkcredenatials() ) {
			self::loadobject();
		}
	}

	/**
	 * Load Google Service and Drive Object.
	 *
	 * @since 1.0.0
	 */
	public function loadobject() {
		self::$instance_service = self::get_client_object();
		self::$instance_drive   = self::get_drive_object();
	}

	/**
	 * Load Google API Library.
	 *
	 * @since 1.0.0
	 */
	public function wpssle_load_library() {
		if ( ! class_exists( 'ComposerAutoloaderInit7069485509ab5fae88aff156e01db0be' ) ) {
			require_once FDBGP_PLUGIN_DIR . 'vendor/autoload.php';
		}
	}

	/**
	 * Get Google Sheets Service Object.
	 *
	 * @since 1.0.0
	 */
	public function get_client_object() {
		if ( null === self::$instance_service ) {
			$client                 = self::getClient();
			self::$instance_service = new \Google_Service_Sheets( $client );
		}
		return self::$instance_service;
	}

	/**
	 * Regenerate Google Sheets Service Object.
	 *
	 * @since 1.0.0
	 */
	public function refreshobject() {
		self::$instance_service = null;
		self::get_client_object();
	}

	/**
	 * Get Google Drive Object.
	 *
	 * @since 1.0.0
	 */
	public function get_drive_object() {
		if ( null === self::$instance_drive ) {
			$client               = self::getClient();
			self::$instance_drive = new \Google_Service_Drive( $client );
		}
		return self::$instance_drive;
	}

	/**
	 * Regenerate Google Drive Object.
	 *
	 * @since 1.0.0
	 */
	public static function get_object_drive_object() {
		self::$instance_drive = null;
	}

	/**
	 * Check Google API Credentials.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function checkcredenatials() {
		$wpssle_google_settings_value = $this->get_google_creds();
		$clientid                     = isset( $wpssle_google_settings_value['client_id'] ) ? $wpssle_google_settings_value['client_id'] : '';
		$clientsecert                 = isset( $wpssle_google_settings_value['client_secret'] ) ? $wpssle_google_settings_value['client_secret'] : '';
		$auth_token                   = isset( $wpssle_google_settings_value['client_token'] ) ? $wpssle_google_settings_value['client_token'] : '';
		if ( empty( $clientid ) || empty( $clientsecert ) || empty( $auth_token ) ) {
			return false;
		} else {
			try {
				if ( self::getClient() ) {
					return true;
				} else {
					return false;
				}
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

	public function get_google_creds(){
		return get_option('fdbgp_google_settings', array(
			'client_id' => '',
			'client_secret' => '',
			'client_token' => ''
		));
	}
	/**
	 * Get meta vlaue.
	 *
	 * @param object $key plugin meta key.
	 * @param string $type boolean value.
	 */
	public static function wpssle_option( $key = '', $type = '' ) {
		$wpssle_old_keys     = self::wpssle_old_option_keys();
		$wpssle_oldmeta_keys = array_keys( $wpssle_old_keys );
		if ( in_array( $key, $wpssle_oldmeta_keys, true ) ) {
			self::wpssle_database_update();
		}
		$value = parent::wpssle_option( $key, $type );
		return $value;
	}

	/**
	 * Update meta value.
	 *
	 * @param object $key plugin meta key.
	 * @param string $value plugin meta value.
	 */
	public static function wpssle_update_option( $key = '', $value = '' ) {
		$wpssle_old_keys     = self::wpssle_old_option_keys();
		$wpssle_oldmeta_keys = array_keys( $wpssle_old_keys );
		if ( in_array( $key, $wpssle_oldmeta_keys, true ) ) {
			self::wpssle_database_update();
		}
		$value = parent::wpssle_update_option( $key, $value );
		return $value;
	}

	/**
	 * Old meta keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function wpssle_old_option_keys() {
		return array(
			'wpsse_google_accessToken' => 'elementorsheets_google_accessToken',
			'wpsse_google_settings'    => 'elementorsheets_google_settings',
		);
	}

	/**
	 * Update old meta keys to new meta keys.
	 *
	 * @since 1.0.0
	 */
	public static function wpssle_database_update() {
		$wpssle_is_updated = self::wpssle_option( 'wpsse_database_updated' );
		$wpssle_old_keys   = self::wpssle_old_option_keys();
		global $wpdb;
		if ( ! $wpssle_is_updated ) {
			$table_name = $wpdb->prefix . 'options';
			foreach ( $wpssle_old_keys as $newkey => $oldkey ) {
				// @codingStandardsIgnoreStart
				$sql    = $wpdb->prepare( "UPDATE `$table_name` SET `option_name`=%s WHERE `option_name`=%s", $newkey, $oldkey );
				$result = $wpdb->get_results( $sql );
				// @codingStandardsIgnoreEnd
			}
			self::wpssle_update_option( 'wpsse_database_updated', 1 );
		}
	}

	/**
	 * Get Google Client Object.
	 *
	 * @param int $flag flag to return error message.
	 * @since 1.0.0
	 *
	 * @return object|string.
	 */
	public function getClient( $flag = 0 ) {

		$this->wpssle_load_library();
		$wpssle_google_settings_value = $this->get_google_creds();
		$clientid                     = isset( $wpssle_google_settings_value['client_id'] ) ? $wpssle_google_settings_value['client_id'] : '';
		$clientsecert                 = isset( $wpssle_google_settings_value['client_secret'] ) ? $wpssle_google_settings_value['client_secret'] : '';
		$auth_token                   = isset( $wpssle_google_settings_value['client_token'] ) ? $wpssle_google_settings_value['client_token'] : '';
		$client       = new \Google_Client();
		$client->setApplicationName( 'FormsDB - Elementor Google Spreadsheet Addon' );
		$client->setScopes( \Google_Service_Sheets::SPREADSHEETS_READONLY );
		$client->setScopes( \Google_Service_Drive::DRIVE_METADATA_READONLY );
		$client->addScope( \Google_Service_Sheets::SPREADSHEETS );
		$client->setClientId( $clientid );
		$client->setClientSecret( $clientsecert );
		$client->setRedirectUri( esc_html( admin_url( 'admin.php?page=formsdb' ) ) );
		$client->setAccessType( 'offline' );
		$client->setPrompt('consent');
		// Load previously authorized credentials from a database.
		try {
			if ( empty( $auth_token ) ) {
				$auth_url = $client->createAuthUrl();
				return $auth_url;
			}
			$wpssle_accesstoken = get_option( 'fdbgp_google_access_token' );

			if ( ! empty( $wpssle_accesstoken ) ) {
				$accesstoken = json_decode( $wpssle_accesstoken, true );
			} else {
				if ( empty( $auth_token ) ) {
					$auth_url = $client->createAuthUrl();
					return $auth_url;
				} else {
					$authcode = trim( $auth_token );
					// Exchange authorization code for an access token.
					$accesstoken = $client->fetchAccessTokenWithAuthCode( $authcode );
					if(! isset( $accesstoken['refresh_token'] ) || empty( $accesstoken['refresh_token'] ) ){
						$accesstoken['refresh_token'] = $client->getRefreshToken();
					}
					// Store the credentials to disk.
					update_option( 'fdbgp_google_access_token', wp_json_encode( $accesstoken ) );
				}
			}

			// Check for invalid token.
			if ( is_array( $accesstoken ) && isset( $accesstoken['error'] ) && ! empty( $accesstoken['error'] ) ) {
				if ( $flag ) {
					return $accesstoken['error'];
				}
				return false;
			}

			$client->setAccessToken( $accesstoken );
			// Refresh the token if it's expired.
			if ( $client->isAccessTokenExpired() ) {
				// save refresh token to some variable.				
				$refreshtokensaved = ( isset( $accesstoken['refresh_token'] ) && !empty( $accesstoken['refresh_token'] ) ) ? $accesstoken['refresh_token'] : $client->getRefreshToken();
				if( Null === $refreshtokensaved || empty( $refreshtokensaved ) ){
					if ( $flag ) {
						$m = 'Please revoke the token and generate it again.';
						return $m;
					} else {
						return false;
					}
				}
				$newaccesstoken = $client->fetchAccessTokenWithRefreshToken( $refreshtokensaved );

				if ( is_array( $newaccesstoken ) && isset( $newaccesstoken['error'] ) && ! empty( $newaccesstoken['error'] ) ) {
					if ( $flag ) {
						return $newaccesstoken['error'];
					}
					return false;
				}
				
				// pass access token to some variable.
				$accesstokenupdated = $client->getAccessToken();
				if(! isset( $accesstokenupdated['refresh_token'] ) ){
					// append refresh token.
					$accesstokenupdated['refresh_token'] = $refreshtokensaved;
				}
				// Set the new access token.
				update_option( 'fdbgp_google_access_token', wp_json_encode( $accesstokenupdated ) );
				$accesstoken = json_decode( wp_json_encode( $accesstokenupdated ), true );
				$client->setAccessToken( $accesstoken );
			}
		} catch ( Exception $e ) {
			if ( $flag ) {
				return $e->getMessage();
			} else {
				return false;
			}
		}
		return $client;
	}

	/**
	 * Get Spreadsheet Listing.
	 *
	 * @param array $sheetarray sheet array.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_spreadsheet_listing( $sheetarray = array() ) {
		
		if ( self::checkcredenatials() ) {
			self::get_object_drive_object();
			self::loadobject();
		} else {
			return $sheetarray;
		}
		// Print the names and IDs of files.
		$optparams = array(
			'fields' => 'nextPageToken, files(id, name, mimeType)',
			'q'      => "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false",
		);

		$results = self::$instance_drive->files->listFiles( $optparams );

		if ( count( $results->getFiles() ) > 0 ) {
			foreach ( $results->getFiles() as $file ) {
				$sheetarray[ $file->getId() ] = $file->getName();
			}
		}
		$sheetarray['new'] = esc_html__( 'Create New Spreadsheet', 'wpsse' );
		return $sheetarray;
	}

	/**
	 * Fetch sheet listing from Google Sheet.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_sheet_listing( $spreadsheetid = '' ) {
		self::refreshobject();
		return parent::get_sheets( self::$instance_service, $spreadsheetid );
	}

	/**
	 * Fetch row listing from Google Sheet.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @param string $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_row_list( $spreadsheetid, $sheetname ) {
		self::refreshobject();
		$param                  = array();
		$param['spreadsheetid'] = trim( $spreadsheetid );
		$param['sheetname']     = trim( $sheetname );
		return parent::get_values( self::$instance_service, $param );
	}

	/**
	 * Create sheet array.
	 *
	 * @param object $response_object google sheet object.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_sheet_list( $response_object ) {
		$sheets = array();
		foreach ( $response_object->getSheets() as $key => $value ) {
			$sheets[ $value['properties']['title'] ] = $value['properties']['sheetId'];
		}
		return $sheets;
	}

	/**
	 * Create sheet array.
	 *
	 * @param object $response_object google sheet object.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_sheetname_list( $response_object ) {
		$sheets = array();
		foreach ( $response_object->getSheets() as $key => $value ) {
			$sheets[] = $value['properties']['title'];
		}
		return $sheets;
	}

	/**
	 * Create sheet array.
	 *
	 * @param object $response_object google sheet object.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_sheetid_list( $response_object ) {
		$sheets = array();
		foreach ( $response_object->getSheets() as $key => $value ) {
			$sheets[ $value['properties']['sheetId'] ] = $value['properties']['title'];
		}
		return $sheets;
	}

	/**
	 * Create insertDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function insertdimensionobject( $param = array() ) {
		$requests           = new \Google_Service_Sheets_Request(
			array(
				'insertDimension' => array(
					'range' => array(
						'sheetId'    => $param['sheetid'],
						'dimension'  => 'ROWS',
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
				),
			)
		);
		$batchupdaterequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => $requests,
			)
		);
		return $batchupdaterequest;
	}

	/**
	 * Create insertDimension Column Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function insertdimensioncolumnobject( $param = array() ) {
		$requests           = new \Google_Service_Sheets_Request(
			array(
				'insertDimension' => array(
					'range'             => array(
						'sheetId'    => $param['sheetid'],
						'dimension'  => 'COLUMNS',
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
					'inheritFromBefore' => true,
				),
			)
		);
		$batchupdaterequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => $requests,
			)
		);
		return $batchupdaterequest;
	}

	/**
	 * Create Google_Service_Sheets_BatchUpdateSpreadsheetRequest Object
	 * to freeze first row.
	 *
	 * @param int $sheetid Sheet ID.
	 * @param int $wpssle_freeze number of rows to be freezed.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function freezeobject( $sheetid = 0, $wpssle_freeze = 0 ) {
		$requestbody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'updateSheetProperties' => array(
						'properties' => array(
							'sheetId'        => $sheetid,
							'gridProperties' => array(
								'frozenRowCount' => $wpssle_freeze,
							),
						),
						'fields'     => 'gridProperties.frozenRowCount',
					),
				),
			)
		);
		return $requestbody;
	}

	/**
	 * Create Google_Service_Sheets_Spreadsheet Object
	 *
	 * @param string $spreadsheetname Spreadsheet Name.
	 * @param string $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function newspreadsheetobject( $spreadsheetname = '', $sheetname = '' ) {
		$requestbody = new \Google_Service_Sheets_Spreadsheet(
			array(
				'properties' => array(
					'title' => $spreadsheetname,
				),
				'sheets'     => array(
					'properties' => array(
						'title' => $sheetname,
					),
				),
			)
		);
		return $requestbody;
	}

	/**
	 * Prepare parameter array.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @param string $range Range.
	 * @param array  $requestbody Request Body.
	 * @param array  $params Params.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function setparamater( $spreadsheetid = '', $range = '', $requestbody = array(), $params = array() ) {
		$param                  = array();
		$param['spreadsheetid'] = $spreadsheetid;
		$param['range']         = $range;
		$param['requestbody']   = $requestbody;
		$param['params']        = $params;
		return $param;
	}

	/**
	 * Create Google_Service_Sheets_ValueRange Object.
	 *
	 * @param array $values_data values data.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function valuerangeobject( $values_data = array() ) {
		$requestbody = new \Google_Service_Sheets_ValueRange( array( 'values' => $values_data ) );
		return $requestbody;
	}

	/**
	 * Google_Service_Sheets_Spreadsheet Object
	 *
	 * @param string $spreadsheetname Spreadsheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createspreadsheetobject( $spreadsheetname = '' ) {
		$wpssle_requestbody = new \Google_Service_Sheets_Spreadsheet(
			array(
				'properties' => array(
					'title' => $spreadsheetname,
				),
			)
		);
		return $wpssle_requestbody;
	}

	/**
	 * Google_Service_Sheets_BatchUpdateSpreadsheetRequest Object
	 *
	 * @param string $wpssle_sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createsheetobject( $wpssle_sheetname = '' ) {
		$wpssle_requestbody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'addSheet' => array(
						'properties' => array(
							'title' => $wpssle_sheetname,
						),
					),
				),
			)
		);
		return $wpssle_requestbody;
	}

	/**
	 * Create Google_Service_Sheets_BatchUpdateSpreadsheetRequest Object
	 * to delete sheet.
	 *
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function deletesheetobject() {
		$wpssle_requestbody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'deleteSheet' => array(
						'sheetId' => 0,
					),
				),
			)
		);
		return $wpssle_requestbody;
	}

	/**
	 * Create Google_Service_Sheets_ClearValuesRequest Object
	 *
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clearobject() {
		$requestbody = new \Google_Service_Sheets_ClearValuesRequest();
		return $requestbody;
	}

	/**
	 * Format google sheet.
	 *
	 * @param array $param contains spreadsheetid, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function formatsheet( $param = array() ) {
		return parent::batchupdate( self::$instance_service, $param );
	}

	/**
	 * Update entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function updateentry( $param = array() ) {
		return parent::update_entry( self::$instance_service, $param );
	}

	/**
	 * Append entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function appendentry( $param = array() ) {
		return parent::append_entry( self::$instance_service, $param );
	}

	/**
	 * Create Spreadsheet.
	 *
	 * @param array $requestbody request body.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createspreadsheet( $requestbody = array() ) {
		return parent::create_spreadsheet( self::$instance_service, $requestbody );
	}

	/**
	 * Clear Google Sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clear( $param = array() ) {
		return parent::clearsheet( self::$instance_service, $param );
	}

	/**
	 * Prepare parameter array.
	 *
	 * @param int $sheetid Sheet ID.
	 * @param int $startindex Start Index.
	 * @param int $endindex End Index.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function prepare_param( $sheetid, $startindex, $endindex ) {
		$param               = array();
		$param['sheetid']    = $sheetid;
		$param['startindex'] = $startindex;
		$param['endindex']   = $endindex;
		return $param;
	}

	/**
	 * Create moveDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex,destindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function moveDimensionrequests( $param = array() ) {
		$requests = new \Google_Service_Sheets_Request(
			array(
				'moveDimension' => array(
					'source'           => array(
						'dimension'  => 'COLUMNS',
						'sheetId'    => $param['sheetid'],
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
					'destinationIndex' => $param['destindex'],
				),
			)
		);
		return $requests;
	}
	/**
	 * Create insertDimension Object.
	 *
	 * @param array  $param contains sheetid,startindex,endindex.
	 * @param string $dimension either COLUMN or ROW for request dimension.
	 * @param bool   $inheritstyle inherit style from previous column or not.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function insertdimensionrequests( $param = array(), $dimension = 'COLUMNS', $inheritstyle = false ) {
		$requests = array(
			'insertDimension' => array(
				'range'             => array(
					'sheetId'    => $param['sheetid'],
					'dimension'  => $dimension,
					'startIndex' => $param['startindex'],
					'endIndex'   => $param['endindex'],
				),
				'inheritFromBefore' => false,
			),
		);
		return $requests;
	}
	/**
	 * Create deleteDimension Object.
	 *
	 * @param array  $param contains sheetid,startindex,endindex.
	 * @param string $dimension either COLUMN or ROW for request dimension.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function deleteDimensionrequests( $param = array(), $dimension = 'COLUMNS' ) {
		$requests = array(
			'deleteDimension' => array(
				'range' => array(
					'sheetId'    => $param['sheetid'],
					'dimension'  => $dimension,
					'startIndex' => $param['startindex'],
					'endIndex'   => $param['endindex'],
				),
			),
		);
		return $requests;
	}
	/**
	 * Batch update requests to google sheet.
	 *
	 * @param array $param contains spreadsheetid, requestarray.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function updatebachrequests( $param = array() ) {
		$flat_requests = array();
		$flat_requests = self::normalize_sheet_requests( $param['requestarray'] );
		unset($param['requestarray']); // free memory.
		$batchupdaterequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
			'requests' => $flat_requests,
		]);
		unset($flat_requests); // free memory.
		
		$requestobject['spreadsheetid'] = $param['spreadsheetid'];
		$requestobject['requestbody']   = $batchupdaterequest;
		unset($batchupdaterequest); // free memory.
		return parent::batchupdate( self::$instance_service, $requestobject );
	}
	/**
	 * Normalize sheet requests into an array of Google_Service_Sheets_Request objects.
	 *
	 * @param array $requests The input requests in various formats.
	 * @return array An array of Google_Service_Sheets_Request objects.
	 */
	public function normalize_sheet_requests( $requests ) {
	    $valid_keys = [
	        'addBanding', 'addChart', 'addConditionalFormatRule', 'addDataSource',
	        'addDimensionGroup', 'addFilterView', 'addNamedRange', 'addProtectedRange',
	        'addSheet', 'addSlicer', 'appendCells', 'appendDimension', 'autoFill',
	        'autoResizeDimensions', 'clearBasicFilter', 'copyPaste', 'createDeveloperMetadata',
	        'cutPaste', 'deleteBanding', 'deleteConditionalFormatRule', 'deleteDataSource',
	        'deleteDeveloperMetadata', 'deleteDimension', 'deleteDimensionGroup',
	        'deleteDuplicates', 'deleteEmbeddedObject', 'deleteFilterView', 'deleteNamedRange',
	        'deleteProtectedRange', 'deleteRange', 'deleteSheet', 'duplicateFilterView',
	        'duplicateSheet', 'findReplace', 'insertDimension', 'insertRange', 'mergeCells',
	        'moveDimension', 'pasteData', 'randomizeRange', 'refreshDataSource', 'repeatCell',
	        'setBasicFilter', 'setDataValidation', 'sortRange', 'textToColumns',
	        'trimWhitespace', 'unmergeCells', 'updateBanding', 'updateBorders', 'updateCells',
	        'updateChartSpec', 'updateConditionalFormatRule', 'updateDataSource',
	        'updateDeveloperMetadata', 'updateDimensionGroup', 'updateDimensionProperties',
	        'updateEmbeddedObjectBorder', 'updateEmbeddedObjectPosition', 'updateFilterView',
	        'updateNamedRange', 'updateProtectedRange', 'updateSheetProperties',
	        'updateSlicerSpec', 'updateSpreadsheetProperties'
	    ];

	    $flat_requests = [];

	    foreach ( (array) $requests as $key => $request ) {
	        // Case 1: Request is already a Google_Service_Sheets_Request object
	        if ( $request instanceof \Google_Service_Sheets_Request ) {
	            $flat_requests[] = $request;
	            continue;
	        }

	        // Case 2: Single associative request: e.g., ['deleteDimension' => [...]]
	        if ( is_string( $key ) && in_array( $key, $valid_keys, true ) && is_array( $request ) ) {
	            $flat_requests[] = new \Google_Service_Sheets_Request( [ $key => $request ] );
	            continue;
	        }

	        // Case 3: A list of request arrays (numerically indexed)
	        if ( is_array( $request ) && array_keys( $request ) === range( 0, count( $request ) - 1 ) ) {
	            foreach ( $request as $sub_request ) {
	                $flat_requests = array_merge(
	                    $flat_requests,
	                    self::normalize_sheet_requests( [ $sub_request ] )
	                );
	            }
	            continue;
	        }

	        // Case 4: Possibly another nested single request
	        if ( is_array( $request ) ) {
	            $request_key = key( $request );
	            if ( in_array( $request_key, $valid_keys, true ) ) {
	                $flat_requests[] = new \Google_Service_Sheets_Request( $request );
	            }
	        }
	    }

	    return $flat_requests;
	}
}