<?php

namespace FMLaravel\Database;

use FileMaker_Error;

class FileMakerException extends \Exception {

    public static function newFromError(FileMaker_Error $error, $additionalMessage = null)
    {
        $additionalMessage = empty($additionalMessage) ? '' : ' / ' . $additionalMessage;
        return new FileMakerException($error->getMessage() . $additionalMessage, $error->getCode());
    }
}