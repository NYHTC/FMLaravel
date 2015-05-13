<?php namespace FMLaravel\Auth;

use Session;

class Auth {

	protected $app;

	public function __construct($app)
	{
		$this->app = $app;
	}

	public static function check()
	{
		return Session::get('auth.active');
	}

	public function connect($username = null, $password = null)
	{
		Session::put('auth.username', $username);
		Session::put('auth.password', $password);
		
		$connection = $this->app->db->connection()->getConnection('auth');

		$layouts = $connection->listLayouts();
		if($connection->isError($layouts)) {
			return false;
		}	

		return true;
	}

}