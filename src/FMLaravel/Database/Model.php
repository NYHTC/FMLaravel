<?php namespace FMLaravel\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent {

	/**
	 * Get a new query builder instance for the connection.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newBaseQueryBuilder()
	{
		$conn = $this->getConnection();

		$grammar = $conn->getQueryGrammar();

		return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
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

}