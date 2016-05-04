<?php namespace FMLaravel\Script;

use FMHash\FMHash;
use FMLaravel\Connection;
use Illuminate\Support\Facades\DB;
use \Exception;

class Script {

	/**
	 * @var Connection
	 */
	protected $connection;

	protected function __construct(Connection $connection = null)
	{
		if ($connection == null){
			$this->connection = DB::connection('filemaker');
		} else {
			$this->connection = $connection;
		}
	}

	public static function connection($name)
	{
		return new Script(DB::connection($name));
	}

	public function execute($layout, $script, $params = null)
	{
		$fm = $this->connection->getConnection('script');

		//if $params is an array, assume it needs to be hashed
		if(is_array($params)) {
			$params = FMHash::make($params);
		}

		$script = $fm->newPerformScriptCommand($layout, $script, $params);

		$result = $script->execute();
	}

	public static function __callStatic($name, $arguments)
	{
		if ($name == 'execute'){
			return call_user_func_array([new Script(), 'execute'], $arguments);
		}
		throw new Exception("Invalid static call {$name} to Script class");
	}
}