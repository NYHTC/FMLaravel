<?php namespace FMLaravel\Auth;

use Session;

class Auth {

	public static function check()
	{
		return Session::get('auth.active');
	}

	public function connect($username = null, $password = null)
	{
		Session::put('auth.username', $username);
		Session::put('auth.password', $password);

		$filemaker = DB::connection('filemaker')->filemaker('auth', function(){

			$config['username'] = Session::get('auth.username');
			$config['password'] = Session::get('auth.password');
			$config['cache'] = false;

			return $config;
		});

		$layouts = $filemaker->listLayouts();
		if($filemaker->isError($layouts)) {
			return false;
		}

		return true;
	}

}