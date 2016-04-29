<?php namespace FMLaravel\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;

abstract class Model extends Eloquent {

	// disable default timestamps
	public $timestamps = false;

	protected $fileMakerMetaKey = "__FileMaker__";
	protected $autoloadContainerFields = FALSE;
	protected $containerFields = [];

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

	public function getFileMakerMetaKey(){
		return $this->fileMakerMetaKey;
	}

	public function getFileMakerMetaData(){
		return $this->getAttributeFromArray($this->getFileMakerMetaKey());
	}

	public function getRecordId(){
		if (array_key_exists($this->fileMakerMetaKey,$this->attributes))
			return $this->getAttributeFromArray($this->fileMakerMetaKey)->recordId;
	}

	public function setRecordId($recordId){
		if (!array_key_exists($this->fileMakerMetaKey,$this->attributes)) {
			$this->setAttribute($this->getFileMakerMetaKey(), new \stdClass());
		}
		$this->getFileMakerMetaData()->recordId = $recordId;
		return $this;
	}



	/**
	 * Insert the given attributes and set the ID on the model.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  array  $attributes
	 * @return void
	 */
	protected function insertAndSetId(Builder $query, $attributes)
	{
		$id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

		$this->setRecordId($id);
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

		if ($this->isContainerField($key)){
			$value = $this->asContainerField($key, $value, $this->autoloadContainerFields);
		}

		return $value;
	}

	public function isContainerField($key){
		return in_array($key, $this->containerFields);
	}

	public function getContainerField($key, $loadFromServer = FALSE){
		return $this->asContainerField($key, $this->getAttributeFromArray($key), $loadFromServer);
	}

	public function asContainerField($key, $url, $loadFromServer = FALSE){
		$cf = ContainerField::fromResource($key, $url, $this);
		if ($loadFromServer && !empty($url)) {
			$cf->loadData();
		}
		return $cf;
	}


}