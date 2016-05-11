<?php namespace FMLaravel\Database;

use FMLaravel\Database\ContainerField\ContainerField;
use FMLaravel\Database\Model;
use Illuminate\Database\Query\Builder;
use \stdClass;
use FileMaker;
use Exception;

class QueryBuilder extends Builder {

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var RecordExtractor
	 */
	protected $recordExtractor;

	protected $find;

	public $skip;

	public $limit;

	public $sorts = [];

	public $compoundWhere = 1;

	public function setModel(Model $model){
		$this->model = $model;

		$this->recordExtractor = RecordExtractor::forModel($model);

		return $this;
	}

	//this should be the method to get the results
	public function get($columns = [])
	{
		if($this->containsOr()) {
			$this->find = $this->connection->filemaker('read')->newCompoundFindCommand($this->model->getLayoutName());
			$find_type = 'compound';
		} else {
			$this->find = $this->connection->filemaker('read')->newFindCommand($this->model->getLayoutName());
			$find_type = 'basic';
		}

		$this->parseWheres($this->wheres, $this->find, $find_type);
		$this->addSortRules();
		$this->setRange();

		$result = $this->find->execute();

		/* check if error occurred.
		 * This wonderful FileMaker API considers no found entries as an error with code 401 which is why we have
		 * to make this ridiculous exception. Shame on them, really.
		 */
		if (FileMaker::isError($result) && !in_array($result->getCode(),['401'])){
			throw FileMakerException::newFromError($result);
		}

		return $this->recordExtractor->processResult($result);
	}

	public function skip($skip)
	{
		$this->skip = $skip;

		return $this;
	}

	public function limit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	private function parseWheres($wheres, $find, $find_type)
	{
		if(!$wheres) return;

		foreach($wheres as $where) {
			if($find_type == 'compound') {
				$request = $this->connection->filemaker('read')->newFindRequest($this->model->getLayoutName());
				$this->parseWheres([$where], $request, 'basic');
				$find->add($this->compoundWhere, $request);
				$this->compoundWhere++;
			} else {
				if($where['type'] == 'Nested') {
					$this->parseWheres($where['query']->wheres, $find, $find_type);
				} else {
			    	$find->AddFindCriterion(
			    		$where['column'],
			    		$where['operator'] . $where['value']
			    	);
				}
			}
		}
	}

	public function setRange()
	{
		$this->find->setRange($this->skip, $this->limit);

		return $this;
	}

	public function sortBy($fields, $order = 'asc')
	{
		if(!is_array($fields)) {
			$this->sorts[$fields] = $order;
		} else {
			foreach($fields as $field) {
				$this->sorts[$field] = 'asc';
			}
		}

		return $this;
	}

	private function addSortRules()
	{
		$i = 1;
		foreach($this->sorts as $field => $order) {
			$order = $order == 'desc' ? FILEMAKER_SORT_DESCEND : FILEMAKER_SORT_ASCEND;
			$this->find->addSortRule($field, $i, $order);
			$i++;
		}
	}

	/**
	 * Check to see if the wheres array contains any "or" type wheres
	 * @return boolean
	 */
	private function containsOr()
	{
		if(!$this->wheres) return false;

		return in_array('or', array_pluck($this->wheres, 'boolean'));
	}


	public function delete($id = null)
	{
		if (! is_null($id)) {
			throw new FileMakerException("this delete mode is not supported!");
		}

		$command = $this->connection->filemaker('write')->newDeleteCommand(
			$this->model->getLayoutName(),
			$this->model->getFileMakerMetaData(Model::FILEMAKER_RECORD_ID)
		);
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			throw FileMakerException::newFromError($result);
		}

		return true;
	}

	public function update(array $values)
	{
		/**
		 * separate container fields from other fields
		 * Container fields that are set to an empty value will delete the current data
		 */
		$cfValues = array_filter($values,function($v){
			return $v instanceof ContainerField;
		});
		$values = array_diff_key($values, $cfValues);

		// first update any non-ContainerFields
		if (!empty($values)){
			$command = $this->connection->filemaker('write')->newEditCommand(
				$this->model->getLayoutName(),
				$this->model->getFileMakerMetaData(Model::FILEMAKER_RECORD_ID),
				$values
			);
			$result = $command->execute();

			if (\FileMaker::isError($result)){
				throw FileMakerException::newFromError($result);
			}

			$record = reset($result->getRecords());

			// because setRawAttributes overwrites the whole array, we have to save the meta data before.
			$meta = (array)$this->model->getFileMakerMetaData();

			$this->model->setRawAttributes($this->recordExtractor->extractRecordFields($record));

			$meta[Model::FILEMAKER_MODIFICATION_ID] = $record->getModificationId();
			$this->model->setFileMakerMetaDataArray($meta);
		}

		// now also save container fields
		if (!empty($cfValues)){
			$this->model->updateContainerFields($cfValues);
		}


		return true;
	}


	public function insert(array $values)
	{
		return !empty($this->insertGetId($values));
	}


	/**
	 * Insert a new record and get the value of the primary key.
	 *
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function insertGetId(array $values, $sequence = null)
	{
		/**
		 * separate container fields from other fields
		 * Container fields that are set to an empty value will delete the current data
		 */
		$cfValues = array_filter($values,function($v){
			return $v instanceof ContainerField;
		});
		$values = array_diff_key($values, $cfValues);

		// first update any non-ContainerFields (even if no attributes set!)
		$command = $this->connection->filemaker('write')->newAddCommand(
			$this->model->getLayoutName(),
			$values
		);
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			throw FileMakerException::newFromError($result);
		}

		$record = reset($result->getRecords());

		$this->model->setRawAttributes($this->recordExtractor->extractRecordFields($record));

		$meta = [
			Model::FILEMAKER_RECORD_ID			=> $record->getRecordId(),
			Model::FILEMAKER_MODIFICATION_ID	=> $record->getModificationId()
		];
		$this->model->setFileMakerMetaDataArray($meta);

		// now also save container fields
		if (!empty($cfValues)){
			$this->model->updateContainerFields($cfValues);
		}


		return $record->getField($this->model->getKeyName());
	}


}
