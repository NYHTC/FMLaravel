<?php namespace FMLaravel\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class User extends Model implements Authenticatable
{

    use AuthenticatableTrait;
}
