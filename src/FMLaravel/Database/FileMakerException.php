<?php namespace FMLaravel\Database;

use FileMaker_Error;

class FileMakerException extends \Exception {

    public static function newFromError(FileMaker_Error $error)
    {
        return new FileMakerException($error->getMessage(),$error->getCode());
    }
}