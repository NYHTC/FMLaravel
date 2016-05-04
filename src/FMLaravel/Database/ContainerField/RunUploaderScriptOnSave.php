<?php namespace FMLaravel\Database\ContainerField;

use FMLaravel\Database\Model;
use FMLaravel\Support\Script;

trait RunUploaderScript
{
    protected function getContainerFieldUploaderScriptLayout(){
        return $this->getLayoutName();
    }

    protected function getContainerFieldUploaderScriptName(){
        return $this->containerFieldUploaderScriptName;
    }

    public function updateContainerFields(array $values){
        $primaryKeyValue = $this->getAttribute($this->getKeyName());

        foreach($values as $cf){
            $result = Script::run(
                $this->getContainerFieldUploaderScriptLayout(),
                $this->getContainerFieldUploaderScriptName(),
                [$primaryKeyValue,base64_encode($cf->data)]
            );
        }
    }
}