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
		if(isset($this->config[$type]) && $type == 'read') {
			$this->config = $this->getReadConfig($this->config);
		}

		if(isset($this->config[$type]) && $type == 'write') {
			$this->config = $this->getWriteConfig($this->config);
		}

		if(isset($this->config[$type]) && $type == 'script') {
			$this->config = $this->getScriptConfig($this->config);
		}

		//If the type passed in is auth, set the config
		//type to auth so the credentials will be 
		//overridden with the auth credentials
		if($type == 'auth') {
			$this->config['credentials'] = 'session';
		}

		return $this->createConnection($this->config);
	}

	private function createConnection($config)
	{
		//If the config type requires FileMaker authentication, override
		//the config with the auth credentials from the session.
		if(isset($config['credentials']) && $config['credentials'] == 'session') {
			$config['username'] = Session::get('auth.username');
			$config['password'] = Session::get('auth.password');
		}

		return new FileMaker(
			$config['database'],
			$config['host'],
			$config['username'],
			$config['password']
		);
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