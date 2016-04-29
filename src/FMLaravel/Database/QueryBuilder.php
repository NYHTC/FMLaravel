<?php namespace FMLaravel\Database;

use Illuminate\Database\Query\Builder;
use \stdClass;
use FileMaker;

class QueryBuilder extends Builder {

	/**
	 * @var Model
	 */
	protected $model;

	protected $find;

	public $skip;

	public $limit;

	public $sorts = [];

	public $compoundWhere = 1;

	public function setModel($model){
		$this->model = $model;

		return $this;
	}

	//this should be the method to get the results
	public function get($columns = [])
	{
		if($this->containsOr()) {
			$this->find = $this->connection->getConnection('read')->newCompoundFindCommand($this->model->getLayoutName());
			$find_type = 'compound';
		} else {
			$this->find = $this->connection->getConnection('read')->newFindCommand($this->model->getLayoutName());
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

		$rows = [];

		if(!FileMaker::isError($result) && $result->getFetchCount() > 0) {

			foreach($result->getRecords() as $record) {

				$row = new stdClass();

				$fm = new stdClass();
				$fm->recordId = $record->getRecordId();
				$row->{$this->model->getFileMakerMetaKey()} = $fm;

				foreach($result->getFields() as $field) {
					if($field) {
						$row->$field = $record->getField($field);
					}
				}

				$rows[] = $row;
			}

		}

		return $rows;

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
				$request = $this->connection->getConnection('read')->newFindRequest($this->model->getLayoutName());
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
			throw new FileMakerException("delete mode not supported!");
		}

		$command = $this->connection->getConnection('write')->newDeleteCommand($this->model->getLayoutName(), $this->model->getRecordId());
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			throw FileMakerException::newFromError($result);
		}

		return true;
	}

	public function update(array $values)
	{
		$command = $this->connection->getConnection('write')->newEditCommand($this->model->getLayoutName(), $this->model->getRecordId(), $values);
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			dd($command);
			throw FileMakerException::newFromError($result);
		}

		return true;
	}


	public function insert(array $values)
	{
		$command = $this->connection->getConnection('write')->newAddCommand($this->model->getLayoutName(), $values);
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			throw FileMakerException::newFromError($result);
		}

		return true;
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
		$command = $this->connection->getConnection('write')->newAddCommand($this->model->getLayoutName(), $values);
		$result = $command->execute();

		if (\FileMaker::isError($result)){
			throw FileMakerException::newFromError($result);
		}

		$record = reset($result->getRecords());

		return $record->getRecordId();;
	}


}
