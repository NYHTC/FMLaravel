<?php

namespace FMLaravel\Database\Finds;

use \stdClass;
use FileMaker;

abstract class Find
{
	public $type;

	protected $command;

	protected $connection;

	protected $layout;

	protected $compoundFind = 1;

	public function __construct($connection, $layout)
	{
		$this->connection = $connection;
		$this->layout = $layout;
	}

	public abstract function addFindCriterion($field, $operator, $value);

	protected function getReadConnection()
	{
		return $this->connection->getConnection('read');
	}

	public function setRange($skip, $limit)
	{
		$this->command->setRange($skip, $limit);
	}

	public function addSortRule($field, $i, $order)
	{
		$this->command->addSortRule($field, $i, $order);
	}

	public function execute()
	{
		$result = $this->command->execute();

		return $this->convertToArray($result);
	}

	private function convertToArray($result)
	{
		$rows = [];

		//if there is a FileMaker error return the empty rows array
		if(FileMaker::isError($result)) return $rows;

		if($result->getFetchCount() > 0) {

			//loop through the records and create a standard object for each one
			foreach($result->getRecords() as $record) {

				$row = new stdClass();

				//loop through the fields on the layout setting
				//each field as a property on the object
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
}
