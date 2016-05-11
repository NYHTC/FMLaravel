<?php namespace FMLaravel\Database\ContainerField;

use FileMaker;
use FMLaravel\Database\Model;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Util\MimeType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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



    protected function __construct($origin)
    {
        $this->origin = $origin;
    }

    /**
     * @param $key
     * @param $resource
     * @param Connection|null $connection
     * @return ContainerField
     */
    public static function fromServer($key, $url, Model $model)
    {
        $cf = new ContainerField('server');

        $cf->key = $key;
        $cf->model = $model;

        $filename = basename(substr($url, 0, strpos($url, '?')));

        $cf->container = [
            'filename'    => $filename,
            'url'     => $url
        ];

        return $cf;
    }

    public static function fromStorage($filename, $disk = null)
    {
        $cf = new ContainerField('storage');

        $cf->setFromStorage($filename, $disk);

        return $cf;
    }

    public function setFromStorage($filename, $disk = null)
    {
        $this->origin = 'storage';
        $this->container = [
            'filename'  => $filename,
            'disk'      => $disk
        ];
    }

    public static function fromRealpath($realpath, $filename = null)
    {
        $cf = new ContainerField('realpath');

        $cf->setFromRealpath($realpath, $filename);

        return $cf;
    }

    public function setFromRealpath($realpath, $filename = null)
    {
        if ($filename === null) {
            $filename = basename($realpath);
        }

        $this->origin = 'realpath';
        $this->container = [
            'filename'  => $filename,
            'realpath'  => $realpath
        ];
    }

    public static function withData($filename, $rawData)
    {
        $cf = new ContainerField('data');

        $cf->setWithData($filename, $rawData);

        return $cf;
    }

    public function setWithData($filename, $rawData)
    {
        $this->origin = 'data';
        $this->container = [
            'filename'  => $filename,
            'data'      => $rawData
        ];
    }

    public function getOrigin()
    {
        return $this->origin;
    }


    public function getModel()
    {
        return $this->model;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function getMimeType()
    {
        return MimeType::detectByFilename($this->container['filename']);
    }


    public function __get($name)
    {
        // container field data is treated specially
        if ($name == 'data') {
            switch ($this->origin) {
                case 'server':
                    // return null if no url/container data exists
                    if (empty($this->container['url'])) {
                        return null;
                    }
                    if (!$this->hasLoadedServerData()) {
                        // if cache is enabled, check it first, and possibly retrieve server
                        if ($this->isCachable()) {
                            $key = $this->getCacheKey();
                            $store = $this->model->getContainerFieldCacheStore();

                            if ($store->has($key)) {
                                $this->container['data'] = $store->get($key);
                            } else {
                                $this->loadServerData();
                                $this->saveToCache();
                            }
                        } else { // no cache used.
                            $this->loadServerData();
                        }
                    }

                    return $this->container['data'];

                case 'realpath':
                    return file_get_contents($this->container['realpath']);

                case 'storage':
                    return Storage::disk($this->container['disk'])->get($this->container['filename']);

                case 'data':
                    return $this->container['data'];
            }
        } elseif (method_exists($this, 'get' . Str::studly($name))) {
            return $this->{'get' . Str::studly($name)}();
        } elseif (isset($this->container[$name])) {
            return $this->container[$name];
        }
    }

    /** Is content set?
     * NOTE: only meaningful for fields fetched from the server
     * @return bool
     */
    public function isEmpty()
    {
        switch ($this->origin) {
            case 'server':
                return empty($this->container['url']);

            // in case
            default:
                return false;
        }
    }

    public function hasLoadedServerData()
    {
        return $this->origin == 'server' && array_key_exists('data', $this->container);
    }

    public function loadServerData()
    {
        if (!$this->hasLoadedServerData()) {
            $this->container['data'] = $this->fetchServerData();
        }
    }
    protected function fetchServerData()
    {
        if ($this->origin != 'server') {
            throw new Exception("Container data is not stored on server");
        }
        if (empty($this->container['url'])) {
            return null;
        }
        return $this->model->getConnection()->filemaker('read')->getContainerData($this->container['url']);
    }

    public function didSaveToServer($url)
    {

        $this->container['url'] = $url;

        if ($this->isCachable()) {
            $this->saveToCache();
        }
    }


    /**
     * ONLY to be use
     * @return string|null
     */
    public function getCacheKey()
    {
        if (array_key_exists('url', $this->container)) {
            $cacheKey = $this->model->getContainerFieldCacheKeyFormat($this);
            $cacheKey = str_replace(':field', $this->key, $cacheKey);
            $cacheKey = str_replace(':filename', $this->container['filename'], $cacheKey);
            $cacheKey = str_replace(':url', $this->container['url'], $cacheKey);
            $cacheKey = str_replace(
                ':recordId',
                $this->model->getFileMakerMetaData(Model::FILEMAKER_RECORD_ID),
                $cacheKey
            );
            $cacheKey = str_replace(
                ':modificationId',
                $this->model->getFileMakerMetaData(Model::FILEMAKER_MODIFICATION_ID),
                $cacheKey
            );
            return $cacheKey;
        }
        return null;
    }

    /** ContainerFields are cachable if the data origin is the server and the necessary fields
     * are set (ie, a cache time)
     * @return bool
     */
    public function isCachable()
    {
        return $this->origin == 'server'
        && 0 < $this->model->getContainerFieldCacheTime()
        && !empty($this->getCacheKey());
    }

    protected function saveToCache()
    {
        switch ($this->origin) {
            case 'server':
            case 'data':
                $data = $this->container['data'];
                break;

            case 'realpath':
                $data = file_get_contents($this->container['realpath']);
                break;

            case 'storage':
                $data = Storage::disk($this->container['disk'])->get($this->container['filename']);
                break;

            default:
                throw new Exception("origin not supported {$this->origin}");
        }
        $this->model->getContainerFieldCacheStore()->put(
            $this->getCacheKey(),
            $data,
            $this->model->getContainerFieldCacheTime()
        );
    }
}
