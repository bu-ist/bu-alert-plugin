<?php
/**
 * BU Alert
 *
 * Main functions.
 *
 * @package BU_Alert
 */

namespace BU\Plugins\Alert;

// Define constants for the alert option key.
const SITE_OPT_ALERT        = 'bu-active-alert';
const SITE_OPT_ANNOUNCEMENT = 'bu-active-announcement';

/**
 * Get all open alerts for the entire WP multi-site network
 *
 * Directly queries the site_meta table for the entire network
 * and returns any open alerts on any site.  Also processes the
 * result object.  We can't use native get_option functions, because
 * we want to scan ALL sites on the network.
 *
 * @return array An array of site options for open alerts.
 */
function get_all_open_alerts() {
	global $wpdb;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = %s OR meta_key = %s",
			SITE_OPT_ALERT,
			SITE_OPT_ANNOUNCEMENT
		)
	);

	// Parse raw results and return an associative array.
	return array_map(
		function ( $option ) {
			return array(
				'meta_id'    => $option->meta_id,
				'site_id'    => $option->site_id,
				'meta_key'   => $option->meta_key,
				'meta_value' => unserialize( $option->meta_value ),
			);
		},
		$results
	);
}

/**
 * Get the site id for a specific domain
 *
 * @param string $domain Domain name of a site on the WP network.
 * @return int The id for the domain
 */
function get_id_for_domain( $domain ) {
	global $wpdb;

	$site_id = $wpdb->get_var(
		$wpdb->prepare( "SELECT id FROM {$wpdb->site} WHERE domain = %s", $domain )
	);

	return $site_id;
}

/**
 * Removes the specified alert site option
 *
 * @param array $alert_option The loaded site option for the alert to be deleted.
 * @return void
 */
function remove_alert_option( $alert_option ) {
	if( function_exists( 'switch_to_network' ) ) {
		switch_to_network( $alert_option['site_id'] );
	}
	delete_site_option( $alert_option['meta_key'] );
	if( function_exists( 'restore_current_network' ) ) {
		restore_current_network();
	}
}
