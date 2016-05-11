<?php namespace FMLaravel\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Cache;
use FMLaravel\Database\ContainerField\ContainerField;
use \Exception;
use Symfony\Component\HttpFoundation\File\File;

abstract class Model extends Eloquent
{
    const FILEMAKER_RECORD_ID = "recordId";
    const FILEMAKER_MODIFICATION_ID = "modificationId";

    // disable default timestamps, because likely the filemaker table will not have these
    public $timestamps = false;

//    protected $fileMakerMetaKey = "__FileMaker__"; // override property

//    protected $containerFields = [];
//    protected $containerFieldsAutoload = false; // override property
//    protected $containerFieldsCacheKeyFormat = ':modificationId'; // override property
//    protected $containerFieldsCacheStore = 'file'; // override property
//    protected $containerFieldsCacheTime = 1;          // override property


    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $query = new QueryBuilder($conn, $grammar, $conn->getPostProcessor());

        return $query->setModel($this);
    }

    /**
     * Get the table qualified key name.
     * return plain key name without the table
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    public function getTable()
    {
        return $this->getLayoutName();
    }

    public function getLayoutName()
    {
        return $this->layoutName;
    }

    public function setLayoutName($layout)
    {
        $this->layoutName = $layout;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }


    /**
     * @return string
     */
    public function getFileMakerMetaKey()
    {
        if (property_exists($this, 'fileMakerMetaKey')) {
            return $this->fileMakerMetaKey;
        }
        return '__FileMaker__';
    }

    /** Get filemaker meta data
     * if no key is provided, returns the meta data object
     * @param string|null $key
     * @return mixed
     */
    public function getFileMakerMetaData($key = null)
    {
        if (!array_key_exists($this->fileMakerMetaKey, $this->attributes)) {
            $this->setFileMakerMetaDataArray([]);
        }
        $meta = $this->getAttributeFromArray($this->getFileMakerMetaKey());
        if ($key === null) {
            return $meta;
        }
        return $meta->$key;
    }

    /** Sets the whole filemaker meta data object using the given key-value pairs
     * NOTE: overwrites existing meta data
     * @param array $values
     * @throws Exception
     */
    public function setFileMakerMetaDataArray(array $values)
    {
        $this->setAttribute($this->getFileMakerMetaKey(), (object)$values);
    }

    /** Sets a specific filemaker meta data value
     * @param $key
     * @param $value
     */
    public function setFileMakerMetaData($key, $value)
    {
        $this->getFileMakerMetaData()->$key = $value;
    }


    /** Retrieves list of container fields.
     * Can be overriden by settings the property 'containerFields' in the extending model
     * (MUST be an array of strings)
     * @return array
     */
    public function getContainerFields()
    {
        if (property_exists($this, 'containerFields')) {
            return $this->containerFields;
        }
        return [];
    }

    /** Returns container field autoload setting
     * Can be overriden by setting the property 'containerFieldsAutoload' in the extending model
     * @return bool
     */
    public function getContainerFieldsAutoload()
    {
        if (property_exists($this, 'containerFieldsAutoload')) {
            return (bool)$this->containerFieldsAutoload;
        }
        return false;
    }

    /** Returns container field cache key format to use
     * Can be overriden either by setting the property 'containerFieldsCacheKeyFormat' or by overriding the method
     * itself, whereof the latter would allow using ContainerField instance specific key to set.
     * By default the following strings will be replaced:
     *      :field          field/attribute key
     *      :filename       insert contained filename
     *      :url            server provided container resource url
     *      :recordId       server provided record id
     *      :modificationId server provided modification id
     * @param ContainerField $cf
     * @return mixed|string
     */
    public function getContainerFieldCacheKeyFormat(ContainerField $cf)
    {
        // if override key format set use it
        if (property_exists($this, 'containerFieldsCacheKeyFormat')) {
            return $this->containerFieldsCacheKeyFormat;
        }
        // otherwise use the default format
        return ':url';
    }

    /** Returns cache store to use for container field.
     * By default returns default cache store.
     * Can be overriden by settings the property 'containerFieldsCacheStore' to the cache store key to use.
     * To implement field specific cache stores, override method.
     * @param ContainerField $cf
     * @return mixed
     */
    public function getContainerFieldCacheStore(ContainerField $cf)
    {
        // first try field overrider
        if (property_exists($this, 'containerFieldsCacheStore')) {
            return Cache::store($this->containerFieldsCacheStore);
        }

        // second try connection configuration
        $store = $this->getConnection()->getConfig('cacheStore');
        if (empty($store)) {
            return Cache::store($store);
        }

        // last just return default store
        return Cache::store();
    }

    /** Returns cache time.
     * Can be overriden by settings the model property 'containerFieldsCacheTime' or by setting the database setting
     * 'cacheTime' in database.php
     * @return int
     */
    public function getContainerFieldCacheTime()
    {
        if (property_exists($this, 'containerFieldsCacheTime')) {
            return $this->containerFieldsCacheTime;
        }
        return intval($this->getConnection()->getConfig('cacheTime'));
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        // mutate container fields on request
        if ($this->isContainerField($key) && !($value instanceof ContainerField)) {
            $value = $this->asContainerField($key, $value, $this->getContainerFieldsAutoload());

            // overwrite the original value with the created container field
            $this->attributes[$key] = $value;
            $this->original[$key] = $value;
        }

        return $value;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($this->isContainerField($key)) {
            // require a container field to be either of the following:
            if (empty($value)) {
                $this->attributes[$key] = null;
            } elseif (is_string($value)) {
                // treat value as a realpath, as the most likely scenario entails developers wanting file uploads to
                // be stored to the FM server
                $value = ContainerField::fromRealpath($value);
            } elseif ($value instanceof File) {
                $value = ContainerField::fromRealpath($value->getRealPath());
            }
            if ($value instanceof ContainerField) {
                // associate container field with this model
                $value->setModel($this);

                // make sure the container field knows to which field it belongs
                $value->setKey($key);

                if (method_exists($this, 'containerFieldSetMutator')) {
                    return $this->containerFieldSetMutator($key, $value);
                } else {
                    $this->attributes[$key] = $value;
                }
            } else {
                throw new Exception(
                    "Settings a container field to a type of ". gettype($value) . "is currently not supported."
                );
            }
        } else {
            parent::setAttribute($key, $value);
        }

        return $this;
    }

    /** Is the field with given key a container field?
     * @param $key
     * @return bool
     */
    public function isContainerField($key)
    {
        return property_exists($this, 'containerFields') && in_array($key, $this->containerFields);
    }

    /** Get given field as container field
     * Likely only useful if container fields are not labelled as such
     * @param $key
     * @param bool $loadFromServer
     * @return ContainerField
     */
    public function getContainerField($key, $loadFromServer = false)
    {
        return $this->asContainerField($key, $this->getAttributeFromArray($key), $loadFromServer);
    }

    /** Create a ContainerField with given key/url and optionally load data from server
     * @param $key
     * @param $url
     * @param bool $loadFromServer
     * @return ContainerField
     */
    public function asContainerField($key, $url, $loadFromServer = false)
    {
        $cf = ContainerField::fromServer($key, $url, $this);
        if ($loadFromServer && !empty($url)) {
            $cf->loadData();
        }
        return $cf;
    }

    /** Method called by container field update mechanism
     * @param array $values
     * @throws Exception
     * @see FMLaravel\Database\QueryBuilder
     */
    public function updateContainerFields(array $values)
    {
        throw new Exception("updateContainerFields has not yet been implemented for this model");
    }
}
