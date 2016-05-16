<?php namespace FMLaravel\Database\ContainerField;

use FMLaravel\Database\Model;
use FMLaravel\Database\RecordExtractor;
use FMLaravel\Script\Script;

trait RunBase64UploaderScriptOnSave
{
    protected function getContainerFieldUploaderScriptLayout()
    {
        if (property_exists($this, 'containerFieldUploaderScriptLayout')) {
            return $this->containerFieldUploaderScriptLayout;
        }
        return $this->getLayoutName();
    }

    protected function getContainerFieldUploaderScriptName()
    {
        if (property_exists($this, 'containerFieldUploaderScriptName')) {
            return $this->containerFieldUploaderScriptName;
        }
        return 'PHPAPI_' . class_basename($this) . '_RunBase64UploaderScriptOnSave';
    }

    public function updateContainerFields(array $values)
    {
        $primaryKeyValue = $this->getAttribute($this->getKeyName());

        $paramsStart = [
            class_basename($this),
            $primaryKeyValue,
            count($values),
        ];

        $paramsList = array_map(function ($k, $cf) {
            return $k . "\n" . $cf->filename . "\n" . base64_encode($cf->data);
        }, array_keys($values), $values);

        $params = array_merge($paramsStart, $paramsList);

        $script = new Script(RecordExtractor::forModel($this), function ($params) {
            if (is_array($params)) {
                return implode("\n", $params);
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
        array_walk($values, function (ContainerField $cf, $k) use ($record) {
            $cf->didSaveToServer($record->$k);
        });

        $meta = (array)$this->getFileMakerMetaData();
        $meta = array_merge($meta, (array)$record->{$this->getFileMakerMetaKey()});
        $this->setFileMakerMetaDataArray($meta);
    }
}
