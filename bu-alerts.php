<?php
/*
Plugin Name: BU Alert
Description: Displays & stores BU Alert emergency messages
Author: Boston University (IS&T)
Version: 2.3.2
Author URI: http://www.bu.edu/
*/

require_once 'bu-alert.php';
require_once 'alert-file.php';
require_once 'campus-map.php';


class BU_AlertsPlugin
{

	/* URL to global BU Alerts CSS file */
	const CSS_URL = 				'https://%s/alert/css/alert.css';

	/* Site option name used to store alerts for a site */
	const SITE_OPT_ALERT =                      'bu-active-alert';
	const SITE_OPT_IMPORTANT_ANNOUNCEMENT =     'bu-active-announcement';

	/* Holds a BU_AlertFile */
	static $alert_file;

	/* Holds the alert text if there is any, otherwise null */
	static $alert_msg;

	/* State stuff */
	static $buffering_started;

	public static function init()
	{
		global $bu_is_development_host;

		if (defined('BU_SUPPRESS_ALERTS') && BU_SUPPRESS_ALERTS && isset($bu_is_development_host) && ($bu_is_development_host === true)) {
			return;
		}

		// Initialize state.
		self::$buffering_started = false;
		if ($active = self::getActiveAlert(self::SITE_OPT_ALERT))
		{
			self::$alert_msg = sprintf('<div id="bu-alert-wrapper">%s</div>', $active['msg']);
		}
		if ($announcement = self::getActiveAlert(self::SITE_OPT_IMPORTANT_ANNOUNCEMENT))
		{
			self::$alert_msg .= sprintf('<div id="bu-alert-wrapper">%s</div>', $announcement['msg']);
		}

		if (self::$alert_msg)
		{
			self::openBuffer();
		}
	}

	protected static function getActiveAlert($alert_type)
	{
		return get_site_option($alert_type);
	}

	public static function getSiteOptionByType($type, $fallback_to_alert=false)
	{
		$site_option = self::SITE_OPT_ALERT;

		switch ($type) {
			case "emergency":
				$site_option = self::SITE_OPT_ALERT;
				break;
			case "announcement":
				$site_option = self::SITE_OPT_IMPORTANT_ANNOUNCEMENT;
				break;
			default:
				if ($fallback_to_alert === 'fallback_to_alert') {
					$site_option = self::SITE_OPT_ALERT;
				}
				else {
					$site_option = self::SITE_OPT_IMPORTANT_ANNOUNCEMENT;
				}
				error_log("BU Alert unknown type, " . $site_option . " type assumed");
				break;
		}

		return $site_option;
	}

	public static function startAlert($alert_message, $campus, $type = 'emergency')
	{
		$site_option = self::getSiteOptionByType($type, 'fallback_to_alert');

		$site_ids = bu_alert_get_campus_site_ids($campus);
		$alert = array(
			'msg' => $alert_message,
			'started_on' => time()
		);

		foreach ($site_ids as $site_id)
		{
			switch_to_network($site_id);
			update_site_option($site_option, $alert);
			restore_current_network();
		}

			try
			{
				global $bu_alert_campus_map;
				foreach ($bu_alert_campus_map[$campus] as $host)
				{
					$alert_file = new BU_AlertFile($host, $type);
					$alert_file->startAlert($alert_message);
				}
			}
			catch (Exception $e)
			{
				error_log('BU Alert: unable to write alert to file.');
				return false;
			}

		return true;
	}

	public static function stopAlert($campus, $type = 'announcement')
	{
		$site_ids = bu_alert_get_campus_site_ids($campus);

		$site_option = self::getSiteOptionByType($type);

		foreach ($site_ids as $site_id)
		{
			switch_to_network($site_id);
			delete_site_option($site_option);
			/* Explicitly delete site option from cache
			 *
			 * There can be a race condition where wpdb->get gets called before wpdb->delete
			 * finishes resulting in the alert getting resaved back into memcached.
			 *
			 * Below we explicitly delete the cache for each network id
			 * This race condition is most likely on www.bu.edu due to is high-usage
			 * but let's be safe and explcilty clear the cache for all IDs
			 *
			 * See slack conversation for additional background
			 * https://buweb.slack.com/archives/webteam/p1475767822000060
			 */
			$key = $site_id . ':' . $site_option;
			wp_cache_delete($key, 'site-options');
			restore_current_network();
		}

			try
			{
				global $bu_alert_campus_map;
				foreach ($bu_alert_campus_map[$campus] as $host)
				{
					$alert_file = new BU_AlertFile($host, $type);
					$alert_file->stopAlert();
				}
			}
			catch (Exception $e)
			{
				error_log('BU Alert: unable to write stop alert to file.');
				return false;
			}
		return true;
	}

	/**
	 * Fired by the WordPress 'wp' action.  This will begin output buffering.
	 */
	public static function openBuffer()
	{
		if (self::$buffering_started !== true) {
			ob_start(array(__CLASS__, 'bufferClosed'));
			self::$buffering_started = true;
		}
	}

	/**
	 * Fired when the output buffer is closed.  We use this, as opposed to WordPress hooks,
	 * to maintain the integrity of this plugin even when the theme does not appropriately
	 * fire the wp_footer action.
	 *
	 *	@param	string $buffer		The buffer, as provided by PHP.
	 *	@return	string				The buffer with the alert message injected.
	 */
	public static function bufferClosed($buffer)
	{

		$host = 'www.bu.edu';
		if (defined('BU_ENVIRONMENT_TYPE') && BU_ENVIRONMENT_TYPE == 'devl')
		{
			$host = 'www-devl.bu.edu';
		}

		// Inject emergency alert and output.
		$buffer = preg_replace('/(<body[^>]*>)/i', '\1' . self::$alert_msg, $buffer);
		$buffer = preg_replace('/<\/head>/i',
			sprintf('<link rel="stylesheet" type="text/css" media="screen" href="%s" />%s</head>', sprintf(self::CSS_URL, $host), "\n"),
			$buffer
		);

		return $buffer;
	}
}

add_action('init', array('BU_AlertsPlugin', 'init'));
