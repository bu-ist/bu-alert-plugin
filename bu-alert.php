<?php

require_once('bu-alerts.php');

function bu_alert_start()
{
	global $bu_alert_campus_map;

	$response = new stdClass;

	if (isset($_REQUEST['message']) && $_REQUEST['message']
		&& isset($_REQUEST['campus']) && $_REQUEST['campus'])
	{
		$campus = strtolower($_REQUEST['campus']);
		if (isset($bu_alert_campus_map[$campus]))
		{
			$message = stripslashes($_REQUEST['message']);
			$type = strtolower($_REQUEST['type']);

			$result = BU_AlertsPlugin::startAlert($message, $campus, $type);

			if ($result)
			{
				$response->success = 1;
			}
			else
			{
				$response->success = 0;
				$response->error_txt = 'Unable to start alert, see error log for detail.';
			}
		}
		else
		{
			$response->success = 0;
			$response->error_txt = 'Unrecognized campus';
		}
	}
	else
	{
		$response->success = 0;
		$response->error_txt = 'One or more required arguments ("message", "campus") was missing or empty.';
	}

	header('Content-Type: application/json');
	echo json_encode($response);
	die;
}

function bu_alert_stop()
{
	global $bu_alert_campus_map;
	$response = new stdClass;

	if (isset($_REQUEST['campus']) && $_REQUEST['campus'])
	{
		$campus = strtolower($_REQUEST['campus']);
		$type = strtolower($_REQUEST['type']);

		if (isset($bu_alert_campus_map[$campus]))
		{
			$result = BU_AlertsPlugin::stopAlert($campus, $type);

			if ($result)
			{
				$response->success = 1;
			}
			else
			{
				$response->success = 0;
				$response->error_txt = 'Unable to stop alert, see error log for detail.';
			}
		}
		else
		{
			$response->success = 0;
			$response->error_txt = 'Unrecognized campus';
		}
	}
	else
	{
		$response->success = 0;
		$response->error_txt = 'Required argument ("campus") missing or empty.';
	}

	header('Content-Type: application/json');
    echo json_encode($response);
	die();
}

function bu_alert_init(){
	bu_api_keys_register_endpoint('bu-alert', 'start-bu-alert', 'bu_alert_start');
	bu_api_keys_register_endpoint('bu-alert', 'stop-bu-alert', 'bu_alert_stop');
}

add_action('admin_init', 'bu_alert_init');
