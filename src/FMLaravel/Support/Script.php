<?php namespace FMLaravel\Support;

use FMHash\FMHash;
use FMLaravel\Connection;
use Illuminate\Foundation\Application;

class Script {

	protected $hasher;
	protected $app;

	public function __construct(FMHash $hasher, Application $app)
	{
		$this->hasher = $hasher;
		$this->app = $app;
	}

	public function execute($script, $layout, $params = null)
	{
		$connection = $this->app->db->connection()->getConnection('script');

		//if $params is an array, assume it needs to be hashed
		if(is_array($params)) {
			$params = $this->hasher->make($params);
		}

		$script = $connection
			->newPerformScriptCommand($layout, $script, $params);

		$result = $script->execute();
	}

}