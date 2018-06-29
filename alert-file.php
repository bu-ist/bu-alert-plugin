<?php

/**
 * Represents a local file containing details about active alerts.
 *
 * NB: This class will not have access to most WP resources.  It should rely only on data set in
 * wp-config.php prior to wp-settings.php being included.
 */
class BU_AlertFile
{
	protected $file_data;
	protected $file_path;
	protected $host;

	public function __construct($host, $type)
	{
		$bu_init = BU_Config::get_instance();
		$bu_wp_config = $bu_init->get_config_for_server(BU_INSTALL_TYPE);

		$installs_path = $bu_wp_config->installs_path;
		if (!$installs_path)
		{
			throw new Exception('No installs_path configured, BU Alerts cannot function.');
		}

		$this->host = $host;
		$this->file_path = sprintf('%s/%s/bu-alert.%s.%s.txt', rtrim($installs_path, '/'), DB_NAME, $host, $type);
		$this->load();
	}

	protected function load()
	{
		$this->file_data = false;

		if ($this->file_path && file_exists($this->file_path) && $data = file_get_contents($this->file_path))
		{
			$this->file_data = $data;
		}
	}

	protected function save()
	{
		$written = file_put_contents($this->file_path, $this->file_data);

		if ($written === false)
		{
			throw new Exception('Unable to write alert file at ' . $this->file_path);
		}

		return true;
	}

	public function startAlert($msg)
	{
		$this->file_data = $msg;
		$this->save();
	}

	public function stopAlert()
	{
		unlink($this->file_path);
	}

	public function getActiveAlert($host)
	{
		return $this->file_data;
	}

	public function getTimestamp()
	{
		return filemtime($this->file_path);
	}
}
