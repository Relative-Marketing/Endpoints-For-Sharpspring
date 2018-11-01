<?php
/**
* Endpoints for Sharpspring
*
* @package     RelativeMarketing\EndpointsForSharpspring
* @author      Daniel Gregory
* @copyright   2018 Relative Marketing
*
* @wordpress-plugin
* Plugin Name: Endpoints For Sharpspring
* Plugin URI:  https://relativemarketing.co.uk
* Description: Adds endpoints to your wordpress site that allows you to communicate with the Sharpspring API
* Version:     1.0.0
* Author:      Relative Marketing
* Author URI:  https://relativemarketing.co.uk
* Text Domain: endpoints-for-sharpring
*/

namespace RelativeMarketing\EndpointsForSharpspring;

defined('ABSPATH') or die();

/**
 * Registers a new endpoint to allow us to connect to sharsprings
 * serverside and keep our secret key secret
 * 
 * url should take the form of:
 * 
 * www.example.com/wp-json/relativemarketing/sharpspring/v1/addLead/email/$email/name/$name
 * 
 */
add_action( 'rest_api_init', __NAMESPACE__ . '\\init_endpoint' );

function init_endpoint() {
	register_rest_route(
		'relativemarketing/sharpspring/v1',
		'/addLead/',
		[
			'methods' => 'POST',
			'callback' => __NAMESPACE__ . '\\handle_newsletter_subscribe',
			'args' => [
				'name' => [
					'validate_callback' => __NAMESPACE__ . '\\validate_name_string'
				],
				'email' => [
					'validate_callback' => function( $param ) {
						return is_email( $param );
					}
				],
			],
		],
	);
}

/**
 * Attempt to add the email subscription to sharspring as a new lead
 */
function handle_newsletter_subscribe( $params ) {
	// Sort out name and email params
	$split_name = explode( '%20', $params->get_param( 'name' ) );
	$first_name = $split_name[0];
	$last_name  = $split_name[ count( $split_name ) - 1 ];
	$email      = $params->get_param('email');

	// Data for the request
	$account_id  = get_option( 'endpoints_for_sharpspring_api_key' );
	$secret_key  = get_option( 'endpoints_for_sharpspring_secret_key' );

	if ( empty($secret_key) || empty($account_id) ) {
		return new \WP_Error('Either account ID or Secret Key is not set');
	}

	$request_id  = session_id();
	$method      = 'createLeads';
	
	// If we have sharsprings(ss) tracking cookie then make sure it is attached
	// to the lead when the lead is created
	$tracking_id = array_key_exists( '__ss_tk', $_COOKIE ) ? $_COOKIE['__ss_tk'] : '';
	$campaign_id = $params['campaignId'];

	// Format the params according to ss api requirements
	$paramData = [ 
		"objects" => [
			[
				"firstName"     => $first_name,
				"lastName"      => $last_name,
				"emailAddress"  => $email,
				"trackingID"    => $tracking_id,
				"campaignID"    => $campaign_id,
			]
		]
	];

	$data = json_encode( [
		'method' => $method,
		'params' => $paramData,
		'id'     => $request_id,
	] );

	$query_string = http_build_query( ['accountID' => $account_id, 'secretKey' => $secret_key] );
	$url = "https://api.sharpspring.com/pubapi/v1/?$query_string";

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen( $data )
	] );

	$result = curl_exec( $ch );
	curl_close( $ch );

	return json_decode( $result  );

}

/**
 * Validates a given full name string
 */
function validate_name_string( $name ) {
	/**
	 * ^          - Beginning of the line
	 * [a-zA-Z- ] - Allowed characters
	 * +          - At least one or more of the preceding
	 * $          - End of line
	 * 
	 * Everything must match from the start and end to be true
	 */
	return (bool) preg_match( "/^[a-zA-Z- ]+$/", urldecode( $name ) );
}

/**
 * Add the options page to allow users to setup the plugin
 */
require plugin_dir_path( __FILE__ ) . 'inc/class-options-page.php';
Options_Page::get_instance();
