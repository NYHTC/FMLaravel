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

		if(isset($type) && $type == 'auth') {
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





	/**
	 * Returns an array of databases that are available with the current
	 * server settings and the current user name and password
	 * credentials.
	 *
	 * @return array|FileMaker_Error List of database names or an Error object.
	 * @see FileMaker
	 */
	function listDatabases()
	{
		return $this->getConnection('read')->listDatabases();
	}

	/**
	 * Returns an array of ScriptMaker scripts from the current database that
	 * are available with the current server settings and the current user
	 * name and password credentials.
	 *
	 * @return array|FileMaker_Error List of script names or an Error object.
	 * @see FileMaker
	 */
	function listScripts()
	{
		return $this->getConnection('read')->listScripts();
	}

	/**
	 * Returns an array of layouts from the current database that are
	 * available with the current server settings and the current
	 * user name and password credentials.
	 *
	 * @return array|FileMaker_Error List of layout names or an Error object.
	 * @see FileMaker
	 */
	function listLayouts()
	{
		return $this->getConnection('read')->listLayouts();
	}


	/**
	 * Returns the data for the specified container field.
	 * Pass in a URL string that represents the file path for the container
	 * field contents. For example, get the image data from a container field
	 * named 'Cover Image'. For a FileMaker_Record object named $record,
	 * URL-encode the path returned by the getField() method.  For example:
	 *
	 * <samp>
	 * <IMG src="img.php?-url=<?php echo urlencode($record->getField('Cover Image')); ?>">
	 * </samp>
	 *
	 * Then as shown below in a line from img.php, pass the URL into
	 * getContainerData() for the FileMaker object named $fm:
	 *
	 * <samp>
	 * echo $fm->getContainerData($_GET['-url']);
	 * </samp>
	 *
	 * @param string $url URL of the container field contents to get.
	 *
	 * @return string Raw field data|FileMaker_Error if remote container field.
	 * @see FileMaker
	 */
	public function getContainerData($url)
	{
		return $this->getConnection('read')->getContainerData($url);
	}


	/**
	 * Returns the fully qualified URL for the specified container field.
	 * Pass in a URL string that represents the file path for the container
	 * field contents. For example, get the URL for a container field
	 * named 'Cover Image'.  For example:
	 *
	 * <samp>
	 * <IMG src="<?php echo $fm->getContainerDataURL($record->getField('Cover Image')); ?>">
	 * </samp>
	 *
	 * @param string $url URL of the container field contents to get.
	 *
	 * @return string Fully qualified URL to container field contents
	 * @see FileMaker
	 */
	function getContainerDataURL($url)
	{
		return $this->getConnection('read')->getContainerDataURL($url);
	}

}