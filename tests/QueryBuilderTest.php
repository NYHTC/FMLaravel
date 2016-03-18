<?php

use Mockery as m;
use FMLaravel\Database\QueryBuilder;

class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
	public function testAssertion()
	{
		$this->assertTrue(true);
	}

	public function testFindMethod()
    {
    	$conn = m::mock('FMLaravel\Database\Connection');
    	$grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
    	$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
    	$findCommand = m::mock('FileMaker_Command_Find');
    	$filemaker = m::mock('alias:FileMaker');

    	$result = m::mock('FileMaker_Result');
    	$result->shouldReceive('getFetchCount')->andReturn(1);
    	$result->shouldReceive('getRecords')->andReturn([]);

    	$filemaker->shouldReceive('isError')->andReturn(false);

    	$findCommand->shouldReceive('setRange');
    	$findCommand->shouldReceive('execute')->andReturn($result);

    	$conn->shouldReceive('getConnection')->andReturn($conn);
    	$conn->shouldReceive('newFindCommand')->andReturn($findCommand);

        $builder = m::mock('FMLaravel\Database\QueryBuilder[]', [$conn, $grammar, $processor]);
        $builder->shouldReceive('setFileMaker');
        $builder->setFileMaker($filemaker);
        // $builder->setModel($this->getMockModel());
        // $builder->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        // var_dump($builder);
        // die();
        // $builder->shouldReceive('where')->once();
        // $builder->shouldReceive('first')->andReturn('baz');
        // $builder->shouldReceive('find')->with('bar');
        $builder->shouldReceive('get')->once()->andReturn($builder);

        $result = $builder->get('bar');
        // $this->assertEquals('baz', $result);

        // $this->assertTrue(true);
    }

    protected function getMockModel()
    {
        $model = m::mock('FMLaravel\Database\Model');
        $model->shouldReceive('getKeyName')->andReturn('foo');
        $model->shouldReceive('getTable')->andReturn('foo_table');
        $model->shouldReceive('getQualifiedKeyName')->andReturn('foo_table.foo');
        return $model;
    }
}
