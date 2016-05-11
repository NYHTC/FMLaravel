<?php namespace FMLaravel\Script;

use FMHash\FMHash;
use FMLaravel\Connection;
use FMLaravel\Database\FileMakerException;
use FMLaravel\Database\RecordExtractor;
use Illuminate\Support\Facades\DB;
use \Exception;

class Script
{

    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var callable
     */
    protected $paramPreprocessor;

    /**
     * @var RecordExtractor
     */
    protected $recordExtractor;

    public function __construct(RecordExtractor $extractor = null, callable $paramPreprocessor = null)
    {
        $this->recordExtractor = $extractor;
        $this->setParamPreprocessor($paramPreprocessor);
    }

    /**
     * @return Script
     */
    public static function create(RecordExtractor $extractor = null, callable $paramPreprocessor = null)
    {
        return new Script($extractor, $paramPreprocessor);
    }

    /**
     * @param String|Connection $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        if (is_string($connection)) {
            $connection = DB::connection($connection);
        }
        if ($connection instanceof Connection) {
            $this->connection = $connection;
        }
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param callable $paramPreprocessor
     * @return $this
     */
    public function setParamPreprocessor(callable $paramPreprocessor = null)
    {
        if ($paramPreprocessor == null) {
            $this->paramPreprocessor = $this->getDefaultParamPreprocessor();
        } else {
            $this->paramPreprocessor = $paramPreprocessor;
        }
        return $this;
    }

    /**
     * @return \Closure
     */
    protected function getDefaultParamPreprocessor()
    {
        return function ($params) {
            if (is_array($params)) {
                return implode("\n", $params);
            }
            return $params;
        };
    }

    /**
     * @return callable
     */
    public function getParamPreprocessor()
    {
        return $this->paramPreprocessor;
    }

    /**
     * @param RecordExtractor $extractor
     * @return $this
     */
    public function setRecordExtractor(RecordExtractor $extractor)
    {
        $this->recordExtractor = $extractor;
        return $this;
    }

    /**
     * @return RecordExtractor
     */
    public function getRecordExtractor()
    {
        return $this->recordExtractor;
    }

    /**
     * @param $layout
     * @param $script
     * @param null $params
     * @return array|\FileMaker_Result
     * @throws FileMakerException
     */
    public function execute($layout, $script, $params = null)
    {
        if ($this->connection instanceof Connection) {
            $connection = $this->connection;
        } else {
            $connection = DB::connection();
        }

        $fm = $connection->filemaker('script');

        //if $params is an array, assume it needs to be hashed
        if (is_callable($this->paramPreprocessor)) {
            $params = call_user_func($this->paramPreprocessor, $params);
        }
//        dd($params);

        $command = $fm->newPerformScriptCommand($layout, $script, $params);

        $result = $command->execute();

        if ($fm->isError($result) && !in_array($result->getCode(), ['401'])) {
            throw FileMakerException::newFromError($result, "layout = {$layout}, script = {$script}");
        }

        //if not set, return the raw FileMaker result
        if (!$this->recordExtractor instanceof RecordExtractor) {
            return $result;
        }

        return $this->recordExtractor->processResult($result);
    }
}
