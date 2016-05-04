<?php namespace FMLaravel\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;
use FMLaravel\Database\ContainerField\ContainerField;
use \Exception;

abstract class Model extends Eloquent {

	// disable default timestamps
	public $timestamps = false;

	const FILEMAKER_RECORD_ID = "recordId";
	const FILEMAKER_MODIFICATION_ID = "modificationId";

	protected $fileMakerMetaKey = "__FileMaker__";

	protected $autoloadContainerFields = false;
	protected $containerFields = [];
	protected $containerFieldCacheStore = 'file';
	protected $containerFieldCacheTime = 1;


	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);

	}


	/**
	 * Get a new query builder instance for the connection.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newBaseQueryBuilder()
	{
		$conn = $this->getConnection();

		$grammar = $conn->getQueryGrammar();

		if (method_exists($this,'initContainerUploader')){
			$this->initContainerUploader();
		}
		// if model uses a ContainerFieldUploader, initialize it
//		$containerUploaderTraits = array_filter(class_uses(get_class($this)), function($v){
//			return false !== strpos('FMLaravel\Database\ContainerField\ContainerUploader',$v);
//		});
//		if (count($containerUploaderTraits)){
//			$this->
//		}



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



	public function getFileMakerMetaKey(){
		return $this->fileMakerMetaKey;
	}

	public function getFileMakerMetaData($key = null){
		if (!array_key_exists($this->fileMakerMetaKey,$this->attributes)) {
			$this->setFileMakerMetaDataArray([]);
		}
		$meta = $this->getAttributeFromArray($this->getFileMakerMetaKey());
		if ($key === null){
			return $meta;
		}
		return $meta->$key;
	}

	public function setFileMakerMetaDataArray(array $values){
		$this->setAttribute($this->getFileMakerMetaKey(), (object)$values);
	}

	public function setFileMakerMetaData($key, $value){
		$this->getFileMakerMetaData()->$key = $value;
	}


//	/**
//	 * Get the attributes that have been changed since last sync.
//	 *
//	 * @return array
//	 */
//	public function getDirty()
//	{
//		$dirty = parent::getDirty();
//
//
//		return $dirty;
//	}



	/**
	 * Get a plain attribute (not a relationship).
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttributeValue($key)
	{
		$value = parent::getAttributeValue($key);

		if ($this->isContainerField($key) && !($value instanceof ContainerField)){
			$value = $this->asContainerField($key, $value, $this->autoloadContainerFields);
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
		if ($this->isContainerField($key)){
			// require a container field to be either of the following:
			if (empty($value)){
				$this->attributes[$key] = null;
			}
			else if ($value instanceof ContainerField){

				// make sure the container field knows to which field it belongs
				$value->setKey($key);

				if (method_exists($this,'containerFieldSetMutator')){
					return $this->containerFieldSetMutator($key, $value);
				} else {
					$this->attributes[$key] = $value;
				}
			}
			else {
				throw new Exception("Settings a container field to a type of ". gettype($value) . "is currently not supported.");
			}
		} else {
			parent::setAttribute($key, $value);
		}

		return $this;
	}

	public function isContainerField($key){
		return in_array($key, $this->containerFields);
	}

	public function getContainerField($key, $loadFromServer = FALSE){
		return $this->asContainerField($key, $this->getAttributeFromArray($key), $loadFromServer);
	}

	public function asContainerField($key, $url, $loadFromServer = FALSE){
		$cf = ContainerField::fromServer($key, $url, $this);
		if ($loadFromServer && !empty($url)) {
			$cf->loadData();
		}
		return $cf;
	}

	public function getContainerFieldCacheStore(){
		return $this->containerFieldCacheStore;
	}

	public function getContainerFieldCacheTime(){
		return $this->containerFieldCacheTime;
	}

	public function updateContainerFields(array $values){
		throw new Exception("updateContainerFields has not yet been implemented for this model");
	}

}