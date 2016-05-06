<?php namespace FMLaravel\Database;

use FMLaravel\Database\Model;
use FileMaker;

class RecordExtractor
{

    protected $metaKey;

    public function __construct($metaKey)
    {
        $this->metaKey = $metaKey;
    }

    public static function forModel($model){
        if (is_string($model)){
            $model = new $model();
        }
        if (!($model instanceof Model)) {
            throw new \Exception("Model is not a FMLaravel\\Data\\Model class!");
        }
        return new RecordExtractor($model->getFileMakerMetaKey());
    }

    /**
     * @param $result Result as returned from filemaker command
     * @return array
     */
    public function processResult($result){
        $rows = [];

        if(!FileMaker::isError($result) && $result->getFetchCount() > 0) {

            foreach($result->getRecords() as $record) {

                $row = $this->extractRecordFields($record);

                $row[$this->metaKey] = (object)[
                    Model::FILEMAKER_RECORD_ID 			=> $record->getRecordId(),
                    Model::FILEMAKER_MODIFICATION_ID	=> $record->getModificationId()
                ];

                $rows[] = (object)$row;
            }

        }

        return $rows;
    }

    public function extractRecordFields($record)
    {
        $attributes = [];
        foreach($record->getFields() as $field){
            if ($field){
                $attributes[$field] = $record->getField($field);
            }
        }
        return $attributes;
    }
}