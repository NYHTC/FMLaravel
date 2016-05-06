<?php namespace FMLaravel\Database;

use Illuminate\Database\Connection as BaseConnection;
use FileMaker;
use Illuminate\Support\Str;
use \Session;
use FMLaravel\Database\LogFacade;

class Connection extends BaseConnection {

	/** List of instantiated connections
	 * @var array
	 */
	protected $connections = [];

	public function __construct(array $config)
	{
		$this->useDefaultQueryGrammar();

		$this->useDefaultPostProcessor();

		$this->config = $config;
	}

	public function filemaker($type = 'default', $configMutator = null)
	{
		$config = $this->config;

		// if neither a particular configuration nor a configuration mutator exists, just take default connection
		if ((!array_key_exists($type, $config) || !is_array($config[$type])) && !is_callable($configMutator)){
			$type = 'default';
		}

		// has it already been created?
		if (isset($this->connections[$type])) {
			return $this->connections[$type];
		}


		// if any particular configuration is wanted and defined load it
		if (array_key_exists($type, $config) && is_array($config[$type])){
			$config = array_merge($config, $config[$type]);
		}

		// if there is any particular configurator passed, run it first
		if (is_callable($configMutator)) {
			$config = $configMutator($config);
		}

		$con = $this->createFileMakerConnection($config);

		if (!array_key_exists('cache', $config) || $config['cache']) {
			$this->connections[$type] = $con;
		}

		return $con;
	}

	protected function createFileMakerConnection($config)
	{
		$fm = new FileMaker(
			$config['database'],
			$config['host'],
			$config['username'],
			$config['password']
		);

		if (array_key_exists('logger',$config) && $config['logger'] instanceof LogFacade){
			$config['logger']->attachTo($fm);
		}
		else if (array_key_exists('logLevel', $config)){
			switch ($config['logLevel']) {
				case 'error':
					LogFacade::with(FILEMAKER_LOG_ERR)->attachTo($fm);
					break;
				case 'info':
					LogFacade::with(FILEMAKER_LOG_INFO)->attachTo($fm);
					break;
				case 'debug':
					LogFacade::with(FILEMAKER_LOG_DEBUG)->attachTo($fm);
					break;
			}
		}

		return $fm;
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
		return $this->filemaker('read')->listDatabases();
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
		return $this->filemaker('read')->listScripts();
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
		return $this->filemaker('read')->listLayouts();
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
//	public function getContainerData($url)
//	{
//		return $this->filemaker('read')->getContainerData($url);
//	}


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
//	function getContainerDataURL($url)
//	{
//		return $this->filemaker('read')->getContainerDataURL($url);
//	}

}