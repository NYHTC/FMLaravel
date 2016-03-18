<?php namespace FMLaravel\Database;

use Illuminate\Database\Query\Builder;
use FMLaravel\Database\Finds\BasicFind;
use FMLaravel\Database\Finds\CompoundFind;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;

class QueryBuilder extends Builder {

	protected $find;

	public $skip;

	public $limit;

	public $sorts = [];

	//this should be the method to get the results
	public function get($columns = [])
	{
		if($this->containsOr()) {
			$this->find = new CompoundFind($this->connection, $this->from);
		} else {
			$this->find = new BasicFind($this->connection, $this->from);
		}

		$this->parseWheres($this->wheres)
			 ->addSortRules()
			 ->setRange();

		return $this->find->execute();
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

	private function parseWheres($wheres)
	{
		if(!$wheres) return $this;

		foreach($wheres as $where) {

			//if this is a nested find, run it through parseWheres again
			if($where['type'] == 'Nested') {
				$this->parseWheres($where['query']->wheres);
			}

	    	$this->find->addFindCriterion(
	    		$where['column'],
	    		$where['operator'],
	    		$where['value']
	    	);
		}

		return $this;
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

		return $this;
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
