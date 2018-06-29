<?php

// NB: This file will not have access to most WP resources when it is loaded.

require_once 'alert-file.php';

function bu_alert_batcache_plugin()
{
	$host = $_SERVER['HTTP_HOST'];
	$emergency_alert_file = new BU_AlertFile($host, 'emergency');
	$announcement_alert_file = new BU_AlertFile($host, 'announcement');

	$emergency = $emergency_alert_file->getActiveAlert($host);
	$announcement = $announcement_alert_file->getActiveAlert($host);

	if ($emergency || $announcement)
	{
		global $batcache;

		// We need to set the unique key using both timestamps
		// so that if one type of alert is active and the other is then later
		// also activated this unique key will change. Likewise, if both alerts
		// are currently active and one type of alert is de-activated
		// this unique key will change causing a purge cache.
		$batcache->unique['active'] = $emergency_alert_file->getTimestamp() . $announcement_alert_file->getTimestamp();
	}
}
bu_alert_batcache_plugin();
