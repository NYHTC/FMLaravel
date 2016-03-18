<?php

namespace FMLaravel\Database\Finds;

use FMLaravel\Database\Finds\Find;

class CompoundFind extends Find
{
	protected $requestNumber = 1;

	public function __construct($connection, $layout)
	{
		parent::__construct($connection, $layout);
		$this->command = $this->getReadConnection()->newCompoundFindCommand($layout);
	}

	public function addFindCriterion($field, $operator, $value)
	{
		$request = $this->newRequest($this->layout);

		$request->AddFindCriterion($field, $operator . $value);

		$this->addToCommand($request);
	}

	protected function newRequest($layout)
	{
		return $this->getReadConnection()->newFindRequest($layout);
	}

	protected function addToCommand($request)
	{
		$this->command->add($this->requestNumber, $request);
		$this->requestNumber++;
	}
}
