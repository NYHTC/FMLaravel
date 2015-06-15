<?php namespace FMLaravel\Database;

use Illuminate\Database\Connection as BaseConnection;
use FileMaker;
use \Session;

class Connection extends BaseConnection {

	public $connection;

	public function __construct(array $config)
	{
		$this->useDefaultQueryGrammar();

		$this->useDefaultPostProcessor();

		$this->config = $config;
	}

	public function getConnection($type)
	{
		$config = $this->config;

		if(isset($this->config[$type]) && $type == 'read') {
			$config = $this->getReadConfig($this->config);
		}

		if(isset($this->config[$type]) && $type == 'write') {
			$config = $this->getWriteConfig($this->config);
		}

		if(isset($this->config[$type]) && $type == 'script') {
			$config = $this->getScriptConfig($this->config);
		}

		if($type == 'auth') {
			$config = $this->getAuthConfig($this->config);
		}

		return $this->createConnection($config);
	}

	private function createConnection($config)
	{
		return new FileMaker(
			$config['database'],
			$config['host'],
			$config['username'],
			$config['password']
		);
	}

	//override the session username and password with session veriables
	private function getAuthConfig($config)
	{
		$config['username'] = Session::get('auth.username');
		$config['password'] = Session::get('auth.password');

		return $config;
	}

	private function getReadConfig(array $config)
	{
		$readConfig = $this->getReadWriteConfig($config, 'read');

		return $this->mergeReadWriteConfig($config, $readConfig);
	}

	/**
	 * Get the read configuration for a read / write connection.
	 *
	 * @param  array  $config
	 * @return array
	 */
	private function getWriteConfig(array $config)
	{
		$writeConfig = $this->getReadWriteConfig($config, 'write');

		return $this->mergeReadWriteConfig($config, $writeConfig);
	}

	/**
	 * Get the read configuration for a read / write connection.
	 *
	 * @param  array  $config
	 * @return array
	 */
	private function getScriptConfig(array $config)
	{
		$scriptConfig = $this->getReadWriteConfig($config, 'script');

		return $this->mergeReadWriteConfig($config, $scriptConfig);
	}

	/**
	 * Get a read / write level configuration.
	 *
	 * @param  array   $config
	 * @param  string  $type
	 * @return array
	 */
	private function getReadWriteConfig(array $config, $type)
	{
		if (isset($config[$type][0]))
		{
			return $config[$type][array_rand($config[$type])];
		}

		return $config[$type];
	}

	/**
	 * Merge a configuration for a read / write connection.
	 *
	 * @param  array  $config
	 * @param  array  $merge
	 * @return array
	 */
	private function mergeReadWriteConfig(array $config, array $merge)
	{
		return array_except(array_merge($config, $merge), array('read', 'write', 'script'));
	}

}