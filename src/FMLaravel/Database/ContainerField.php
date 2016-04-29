<?php namespace FMLaravel\Database;

use FileMaker;
use League\Flysystem\Util\MimeType;

class ContainerField
{
    /** A link to the containing model
     * @var Model
     */
    protected $model;

    /** Contains container data;
     * @var array
     */
    protected $container = [];


    protected function __construct($key, Model $model = null) {
        $this->key = $key;
        $this->model = $model;
    }

    /**
     * @param $key
     * @param $resource
     * @param Connection|null $connection
     * @return ContainerField
     */
    static public function fromResource($key, $url, Model $model = null) {
        if (empty($url)){
            return null;
        }

        $cf = new ContainerField($key, $model);

        $cf->container['key'] = $key;
        $cf->container['url'] = $url;
        $cf->container['file'] = basename(substr($url, 0, strpos($url, '?')));
        $cf->container['mimeType'] = MimeType::detectByFilename($cf->file);

        return $cf;
    }

    public function getModel(){
        return $this->model;
    }
    public function setModel(Model $model){
        $this->model = $model;
        return $this;
    }

    public function __get($name){
        if ($name == 'data' and !$this->hasLoadedData()){
            $this->loadData();
        }
        if (isset($this->container[$name])){
            return $this->container[$name];
        }
    }

    public function hasLoadedData(){
        return array_key_exists('data',$this->container);
    }

    public function loadData(){
        if (!$this->hasLoadedData()){
            $this->container['data'] = $this->model->getConnection('read')->getContainerData($this->container['url']);
        }
    }
}