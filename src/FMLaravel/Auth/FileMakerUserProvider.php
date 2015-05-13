<?php namespace FMLaravel\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Auth\UserInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;
use \Session;

class FileMakerUserProvider implements UserProvider {

	protected $auth;

	public function __construct($auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $identifier
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveById($identifier)
	{
		$user = new User;
		$user->id = 1;
		$user->username = Session::get('auth.username');
		$user->password = Session::get('auth.password');

		return $user;
	}

	/**
	 * Retrieve a user by by their unique identifier and "remember me" token.
	 *
	 * @param  mixed   $identifier
	 * @param  string  $token
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveByToken($identifier, $token)
	{

	}

	/**
	 * Update the "remember me" token for the given user in storage.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  string  $token
	 * @return void
	 */
	public function updateRememberToken(Authenticatable $user, $token)
	{

	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveByCredentials(array $credentials)
	{
		$user = new User;
		$user->id = 1;
		$user->username = $credentials['username'];
		$user->password = $credentials['password'];

		return $user;
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validateCredentials(Authenticatable $user, array $credentials)
	{
		return $this->auth->connect(
			$credentials['username'],
			$credentials['password']
		);
	}

}


class User extends Model implements Authenticatable {

	use AuthenticatableTrait;

}
