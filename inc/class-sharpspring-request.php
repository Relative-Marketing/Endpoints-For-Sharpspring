<?php
/**
 * Class - Sharpspring
 * 
 * For handling calls to the sharpspring API
 */

namespace RelativeMarketing\EndpointsForSharpspring;

/**
 * Class to handle requests to the Sharpspring API.
 *
 * This is used to send data to Sharpsprings. The data will be used to update
 * account information
 *
 *
 * @since      1.0.1
 * @package    endpoints-for-sharpsring
 * @author     Daniel Gregory <daniel@relativemarketing.co.uk>
 */
class Sharpspring_Request {

	private $api_url = 'https://api.sharpspring.com/pubapi/v1';

	private $account_id_option = 'endpoints_for_sharpspring_api_key';
	private $secret_key_option = 'endpoints_for_sharpspring_secret_key';

	private $account_id;
	private $secret_key;

	private $request_url;
	private $request_id;
	private $request_data;
	private $method;
	private $params;

	private $errors = [];

	/**
	 * Sets up the class and attempts to setup the data for the request if it's provided.
	 * 
	 * Construct arguments are optional at the point as the user may want to supply them
	 * the information for the request later.
	 *
	 * @param    string  $method      The sharpspring api method you want to perform
	 * @param    array   $params      The data to send, formatted according to sharpspring API docs
	 *   
	 * @since    1.0.1
	 */
	public function __construct( $method = '', $params = [] ) {
		
		$this->method     = $method;
		$this->params     = $params;

		$this->run();

	}

	public function run() {
		$this->setup_request_id();
		$this->setup_api_info();
		$this->setup_request_url();
		$this->set_request_data();
	}

	public function make_request() {
		/**
		 * Before we start with the request make sure we don't have any
		 * errors. If we have errors send them back instead.
		 */
		if ( count( $this->errors ) ) {
			return array( 'error' => true, 'details' => $this->errors );
		}


		/**
		 * Get the data and perform the request
		 */
		$data = $this->request_data;

		$ch = curl_init( $this->request_url );

		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $data )
		] );

		$result = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $result );
	}

	public function setup_request_id() {
		// Attempt to set the session
		$session_id = session_id();
		// If we don't have a session then create a 'unique' id
		$session_id = empty( $session_id ) ? uniqid( 'ss-req-' ) : $session_id;

		$this->request_id = $session_id;
	}

	public function set_request_data() {
		/**
		 * Flag to detect if an error occurred whilst testing
		 */
		$has_error = 0;

		/**
		 * The data needed for the request to work
		 */
		$data = array(
			'id' => $this->request_id,
			'method' => $this->method,
			'params' => $this->params,
		);

		/**
		 * Check the values of data to make sure they all have some value
		 */
		foreach ($data as $name => $test) {
			if ( empty( $test ) ) {
				/**
				 * Create the error and set the error flag to true
				 */
				$this->add_error( ucfirst( $name ) . ' does not have a meaningful value when calling ' . __FUNCTION__ . ' in ' . __CLASS__, [], $name );
				$has_error = 1;
			}
		}

		/**
		 * If we detected an error stop processing the request data
		 */
		if ( $has_error ) {
			return false;
		}

		$this->request_data = json_encode( $data );

		return true;
	}

	/**
	 * Attempts to get the users sharpspring account id and secret key
	 * 
	 * If the user has set the account id and secret key in the options
	 * panel set the properties on this instance. If they haven't send back
	 * an error whenever the user tries to make a request.
	 */
	private function setup_api_info() {
		$account_id = get_option( $this->account_id_option );
		
		if ( empty( $account_id ) ) {
			$this->add_error(  'Sharpspring account id is not set, please add your api key in Options > Endpoints for Sharpspring', [], 'account-id' );
			return false;
		}

		$this->account_id = $account_id;
		
		$secret_key = get_option( $this->secret_key_option );
		
		if ( empty( $secret_key ) ) {
			$this->add_error( 'Sharpspring secret key is not set, please add your api key in Options > Endpoints for Sharpspring', [], 'secret-key' );
			return false;
		}
		$this->secret_key = $secret_key;
	}

	/**
	 * Sets up the request url to include the Sharpspring account id and secret key
	 */
	private function setup_request_url() {
		$query_string = http_build_query( ['accountID' => $this->account_id, 'secretKey' => $this->secret_key] );
		$this->request_url = $this->api_url . '/?' . $query_string;
	}

	/**
	 * Utility methods
	 */

	/**
	 * Add errors when they occur
	 */
	private function add_error( $message, $data = [], $id = '') {
		$this->errors[ 'code-' . $id ] = new \WP_Error( 'sharpspring-connection', $message, $data );
	}
}