<?php namespace FMLaravel\Database;

use Illuminate\Database\Query\Builder;
use \stdClass;
use FileMaker;

class QueryBuilder extends Builder {

	protected $find;

	public $skip;

	public $limit;

	public $sorts = [];

	public $compoundWhere = 1;

	//this should be the method to get the results
	public function get($columns = [])
	{
		if($this->containsOr()) {
			$this->find = $this->connection->getConnection('read')->newCompoundFindCommand($this->from);
			$find_type = 'compound';
		} else {
			$this->find = $this->connection->getConnection('read')->newFindCommand($this->from);
			$find_type = 'basic';
		}

		$this->parseWheres($this->wheres, $this->find, $find_type);
		$this->addSortRules();
		$this->setRange();

		$result = $this->find->execute();

		$rows = [];

		if(!FileMaker::isError($result) && $result->getFetchCount() > 0) {

			foreach($result->getRecords() as $record) {

				$row = new stdClass();

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
				$request = $this->connection->getConnection('read')->newFindRequest($this->from);
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

}
