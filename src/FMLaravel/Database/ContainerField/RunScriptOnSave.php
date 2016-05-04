<?php namespace FMLaravel\Database\ContainerField;

use FMLaravel\Database\Model;
use FMLaravel\Support\Script;

trait RunScriptOnUpload
{
    protected function getContainerFieldUploaderScriptLayout(){
        return $this->getLayoutName();
    }

    protected function getContainerFieldUploaderScriptName(){
        return $this->containerFieldUploaderScriptName;
    }

    public function updateContainerFields(array $values){
        $primaryKeyValue = $this->getAttribute($this->getKeyName());

        $fields = array_keys($values);

        $params = array_unshift($fields,$primaryKeyValue);

        $result = Script::run(
            $this->getContainerFieldUploaderScriptLayout(),
            $this->getContainerFieldUploaderScriptName(),
            $params
        );

        
    }
}