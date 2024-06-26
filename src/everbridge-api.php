<?php
/**
 * Interactions with the Everbridge API.
 *
 * Depends on global constants from wp-config through /etc/bu-ist/bu-wordpress.ini:
 * EB_ENDPOINT          Generally 'https://api.everbridge.net/'
 * EB_BU_ORG_ID         The Everbridge organization ID to use
 * EB_KEY_ID            Key ID of the API access account
 * EB_SECRET_ACCESS_KEY Secret key of the API access account
 *
 * @package BU_Alert
 */

namespace BU\Plugins\Alert;

/**
 * Calculates an Everbridge authentication token for a given API url path
 *
 * @param string $url_path Path section of the API request, used as the string to sign.
 * @return array Array of header values, including a valid HMAC token.
 */
function get_eb_auth_headers( $url_path ) {

	// Hash and encode the url path and access key.
	$hash      = hash_hmac( 'sha256', $url_path, \EB_SECRET_ACCESS_KEY, true );
	$signature = base64_encode( $hash );

	$key_id = \EB_KEY_ID;

	return array(
		'Accept'        => 'application/json',
		'Authorization' => "EVBG-HMAC-SHA256 ${key_id}:${signature}",
	);
}

/**
 * Get Open incidents in Everbridge from the API
 *
 * @return array Array of open incident ids, or an empty array if there are no open incidents.
 */
function get_eb_open_incidents() {
	$url_path = '/rest/incidents/' . \EB_BU_ORG_ID;

	$url_with_parameters = "${url_path}?status=Open";

	$request = wp_remote_get(
		\EB_ENDPOINT . $url_with_parameters,
		array( 'headers' => get_eb_auth_headers( $url_path ) )
	);

	$body     = wp_remote_retrieve_body( $request );
	$response = json_decode( $body );

	if ( 0 === $response->page->totalCount ) {
		return array();
	}

	$incidents = array_map(
		function( $incident ) {
			return get_eb_incident_details( $incident->id );
		},
		$response->page->data
	);

	return $incidents;
}

/**
 * Get identifying details for a given incident ID
 *
 * @param int $incident_id The ID of the incident to look up.
 * @return array Array of details from the API about the incident.
 */
function get_eb_incident_details( $incident_id ) {
	$url_path = '/rest/incidentNotifications/' . \EB_BU_ORG_ID . "/${incident_id}";

	$request = wp_remote_get(
		\EB_ENDPOINT . $url_path,
		array( 'headers' => get_eb_auth_headers( $url_path ) )
	);

	$body     = wp_remote_retrieve_body( $request );
	$response = json_decode( $body );
	$incident = $response->page->data[0];

	// Get the url that Everbridge used for the Web Posting, it is buried in the template information.
	$received_url = $incident
		->incidentPhase
		->phaseTemplate
		->broadcastTemplate
		->publicMessages
		->genericOneyWayMessage
		->genericOneWaySetting
		->receiveEndPoints[0]
		->receiveUrl;

	return array(
		'id'           => $incident_id,
		'title'        => $incident->message->title,
		'body'         => $incident->message->textMessage,
		'received_url' => $received_url,
	);
}

/**
 * Get just the incident ids for all open incident ids
 *
 * @return array Array of incident ids
 */
function get_open_incident_ids() {
	$open_incident_ids = array_map(
		function ( $incident ) {
			return $incident['id'];
		},
		get_eb_open_incidents()
	);

	return $open_incident_ids;
}
