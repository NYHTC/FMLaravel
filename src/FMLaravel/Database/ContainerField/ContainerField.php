<?php namespace FMLaravel\Database\ContainerField;

use FileMaker;
use FMLaravel\Database\Model;
use League\Flysystem\Util\MimeType;
use Illuminate\Support\Facades\Cache;

class ContainerField
{
    /** A link to the containing model
     * @var Model
     */
    protected $model;

    /** Denotes the origin where the current data originates from
     * @var string
     */
    protected $origin;

    /**
     * @var string
     */
    protected $key;

    /** Contains container data;
     * @var array
     */
    protected $container = [];



    protected function __construct($origin, $key = null, Model $model = null) {
        $this->origin = $origin;
        $this->key = $key;
        $this->model = $model;
    }

    /**
     * @param $key
     * @param $resource
     * @param Connection|null $connection
     * @return ContainerField
     */
    static public function fromServer($key, $url, Model $model = null) {
        if (empty($url)){
            return null;
        }

        $cf = new ContainerField('server', $key, $model);

        $filename = basename(substr($url, 0, strpos($url, '?')));

        $cf->container['url'] = $url;
        $cf->container['file'] = $filename;
        $cf->container['mimeType'] = MimeType::detectByFilename($filename);

        return $cf;
    }

    static public function fromFile($realpath){

        $cf = new ContainerField('file');

        $filename = basename($realpath);

        $cf->container['realpath'] = $realpath;
        $cf->container['file'] = $filename;
        $cf->container['mimeType'] = MimeType::detectByFilename($filename);

    }

    static public function withData($filename, $rawData){

        $cf = new ContainerField('data');

        $cf->container['file'] = $filename;
        $cf->container['mimeType'] = MimeType::detectByFilename($filename);
        $cf->container['data'] = $rawData;

        return $cf;
    }



    public function getModel(){
        return $this->model;
    }
    public function setModel(Model $model){
        $this->model = $model;
        return $this;
    }
    public function getKey(){
        return $this->key;
    }
    public function setKey($key){
        $this->key = $key;
        return $this;
    }


    public function __get($name){
        // container field data is treated specially
        if ($name == 'data'){
            switch($this->origin){
                case 'server':

                    if (!$this->hasLoadedServerData()) {

                        // if cache is enabled, check it first, and possibly retrieve server
                        if ($this->isCachable()){
                            $key = $this->getCacheKey();
                            $store = $this->cacheStore();

                            if ($store->has($key)) {
                                $this->container['data'] = $store->get($key);
                            } else {
                                $this->loadServerData();
                                $store->put($key, $this->container['data'], $this->model->getContainerFieldCacheTime());
                            }
                        } else { // no cache used.
                            $this->loadServerData();
                        }
                    }

                    return $this->container['data'];

                case 'file':
                    return file_get_contents($this->container['realpath']);

                case 'data':
                    return $this->container['data'];

            }
        }
        else if (isset($this->container[$name])){
            return $this->container[$name];
        }
    }


    public function hasLoadedServerData(){
        return $this->origin == 'server' && array_key_exists('data',$this->container);
    }

    public function loadServerData(){
        if (!$this->hasLoadedServerData()){
            $this->container['data'] = $this->fetchServerData();
        }
    }
    protected function fetchServerData(){
        if ($this->origin != 'server'){
            throw new Exception("Container data is not stored on server");
        }
        if (empty($this->container['url'])){
            return NULL;
        }
        return $this->model->getConnection('read')->getContainerData($this->container['url']);
    }


    /**
     * ONLY to be use
     * @return string|null
     */
    public function getCacheKey(){
        if (array_key_exists('url',$this->container)){
            return $this->container['url'];
        }
        return null;
    }

    /**
     * to use the cache, it must be enabled, and a record it (as retrieved from the server) must be set
     * @return bool
     */
    public function isCachable(){
        return is_string($this->model->getContainerFieldCacheStore()) && !empty($this->getCacheKey());
    }

    /** Gets the cache store to be used
     * @return bool|Cache
     */
    public function cacheStore(){
        $store = $this->model->getContainerFieldCacheStore();
        if (empty($store)){
            return false;
        }
        return Cache::store($store);
    }

}