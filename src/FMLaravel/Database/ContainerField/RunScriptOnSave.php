<?php namespace FMLaravel\Database\ContainerField;

use FMLaravel\Database\Model;
use FMLaravel\Support\Script;

trait RunScriptOnSave
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

        $script = new Script(RecordExtractor::forModel($this), function($params){
            if (is_array($params)){
                return implode("\n",$params);
            }
            return $params;
        });

        $script->setConnection($this->getConnection());

        $result = $script->execute(
            $this->getContainerFieldUploaderScriptLayout(),
            $this->getContainerFieldUploaderScriptName(),
            $params
        );

        $record = reset($result);

        // for each of the passed container fields
        array_walk($values,function(ContainerField $cf,$k)use($record){
            // well, it depends on what the script actually does :)
            // this is more of a sample implementation, also see RunBase64UploaderScriptOnSave
        });

        $meta = (array)$this->getFileMakerMetaData();
        $meta = array_merge($meta, (array)$record->{$this->getFileMakerMetaKey()} );
        $this->setFileMakerMetaDataArray($meta);


    }
}