<?php

namespace FMLaravel\Database\Finds;

use FMLaravel\Database\Finds\Find;

class BasicFind extends Find
{
	public function __construct($connection, $layout)
	{
		parent::__construct($connection, $layout);
		$this->command = $this->getReadConnection()->newFindCommand($layout);

		return $this;
	}

	public function addFindCriterion($field, $operator, $value)
	{
		$this->command->addFindCriterion($field, $operator . $value);
	}
}
