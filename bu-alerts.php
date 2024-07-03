<?php
/**
 * Plugin Name: BU Alert
 * Description: Displays & stores BU Alert emergency messages
 * Author: Boston University (IS&T)
 * Version: 3.0.0
 * Author URI: http://www.bu.edu/
 *
 * @package BU Alert Plugin
 */

require_once 'src/bu-alert-endpoint.php';
require_once 'src/everbridge-api.php';
require_once 'src/bu-alert-main.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/src/alert-wp-cli.php';
}

/**
 * Main plugin class, handles initialzation and starting/stopping alerts
 */
class BU_AlertsPlugin {

	/* Site option names used to store alerts/announcements for a site */
	const SITE_OPT_ALERT        = 'bu-active-alert';
	const SITE_OPT_ANNOUNCEMENT = 'bu-active-announcement';

	/**
	 * Holds the alert text if there is any, otherwise null.
	 *
	 * @var string|null
	 */
	public static $alert_msg;

	/**
	 * Used to track the output buffer state (which I'm not sure is really useful or necessary)
	 *
	 * @var boolean
	 */
	public static $buffering_started;

	/**
	 * Enqueue alert style, and inject any active alerts to the page body
	 *
	 * @return void
	 */
	public static function init() {

		// Always enqueue alert css.
		wp_enqueue_style(
			'bu-alert-css',
			plugin_dir_url( __FILE__ ) . 'alert.css',
			array(),
			'3.0'
		);

		// Initialize state.
		self::$buffering_started = false;

		$active_alert = get_site_option( self::SITE_OPT_ALERT );
		if ( $active_alert ) {
			self::$alert_msg = sprintf(
				'<div id="bu-alert-wrapper"><div id="bu-alert-emergency" class="nocontent"><div id="bu-alert-emergency-inner"><p><span id="bu-alert-emergency-header">Emergency BU Alert</span> <span id="bu-alert-emergency-message">%s</span></p></div></div></div>',
				$active_alert['msg']
			);
		}

		$active_announcement = get_site_option( self::SITE_OPT_ANNOUNCEMENT );
		if ( $active_announcement ) {
			self::$alert_msg .= sprintf(
				'<div id="bu-alert-wrapper"><div id="bu-alert-non-emergency" class="nocontent">%s</div></div>',
				$active_announcement['msg']
			);
		}

		if ( self::$alert_msg ) {
			self::openBuffer();
		}
	}

	/**
	 * Returns the key of the site option to use for storing the alert
	 *
	 * @param string $type              The type of alert we are acting on, e.g. emergency or announcement.
	 * @return string The name of the site option to store the alert in
	 */
	public static function getSiteOptionByType( $type ) {
		return ( 'announcement' === $type ) ? self::SITE_OPT_ANNOUNCEMENT : self::SITE_OPT_ALERT;
	}

	/**
	 * Starts an alert by setting the alert site option and creating alert files
	 *
	 * @param string $alert_message The text message received from the external API.
	 * @param array  $site_ids An array of ids for sites that will display the alert.
	 * @param string $type Optional alert type, either 'emergency' or 'announcement'.
	 * @param mixed  $incident_id Optional incident id, either a numeric id or the string 'manual'.
	 * @return bool Returns true on success or false on failure
	 */
	public static function startAlert( $alert_message, $site_ids, $type = 'emergency', $incident_id = 0 ) {
		global $wp_object_cache;

		$site_option = self::getSiteOptionByType( $type );

		$alert = array(
			'msg'         => $alert_message,
			'started_on'  => time(),
			'incident_id' => $incident_id,
		);

		// Set network level site option for an alert, given the type and message.
		foreach ( $site_ids as $site_id ) {
			if( function_exists( 'switch_to_network' ) ) {
				switch_to_network( $site_id );
			}
			update_site_option( $site_option, $alert );
			if( function_exists( 'restore_current_network' ) ) {
				restore_current_network();
			}
		}

		// Flushing the cache should affect every site in every network?
		$flush_result = $wp_object_cache->flush( 0 );

		return array(
			'site_option'   => $site_option,
			'site_ids'      => $site_ids,
			'alert_message' => $alert_message,
			'flush_result'  => $flush_result,
		);
	}

	/**
	 * Stops alerts for an array of site ids.
	 *
	 * @param array  $site_ids An array of ids for sites that will stop displaying the alert.
	 * @param string $type Optional alert type, either 'emergency' or 'announcement'.
	 * @return array Returns a status result, as to whether the cache flush was successful.
	 */
	public static function stopAlert( $site_ids, $type = 'emergency' ) {
		global $wp_object_cache;

		$site_option = self::getSiteOptionByType( $type );

		foreach ( $site_ids as $site_id ) {
			if( function_exists( 'switch_to_network' ) ) {
				switch_to_network( $site_id );
			}
			delete_site_option( $site_option );
			if( function_exists( 'restore_current_network' ) ) {
				restore_current_network();
			}
		}

		// Flushing the cache should affect every site in every network?
		$flush_result = $wp_object_cache->flush( 0 );

		return array( 'flush_result' => $flush_result );
	}

	/**
	 * Fired by the WordPress 'wp' action.  This will begin output buffering.
	 */
	public static function openBuffer() {
		if ( self::$buffering_started !== true ) {
			ob_start( array( __CLASS__, 'bufferClosed' ) );
			self::$buffering_started = true;
		}
	}

	/**
	 * Fired when the output buffer is closed.  We use this, as opposed to WordPress hooks,
	 * to maintain the integrity of this plugin even when the theme does not appropriately
	 * fire the wp_footer action.
	 *
	 *  @param  string $buffer      The buffer, as provided by PHP.
	 *  @return string              The buffer with the alert message injected.
	 */
	public static function bufferClosed( $buffer ) {

		// Inject emergency alert and output.
		$buffer = preg_replace( '/(<body[^>]*>)/i', '\1' . self::$alert_msg, $buffer );

		return $buffer;
	}
}

add_action( 'init', array( 'BU_AlertsPlugin', 'init' ) );
