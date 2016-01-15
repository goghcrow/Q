<?php
namespace Xiaofeng;

// TODO Generator Version
// TODO cached generator
// TODO 集成Plinq各种功能

use Traversable;
use Iterator;
use ArrayIterator;
use OuterIterator;
use InvalidArgumentException;
use LogicException;

/**
 * Query 2D Traversable Datasource
 * Class Q
 * @author xiaofeng
 * @package Xiaofeng
 */
class Q implements OuterIterator
{
    /**
     * 内部迭代器
     * @var ArrayIterator
     */
    private $innerIterator;

    /**
     * as 表别名
     * @var string
     */
    private $alias = "";

    /**
     * 当前表作用列
     * @var string
     */
    private $keyColumn = "";

    /**
     * 重名key分隔符
     * @var string
     */
    // private $keySep = "…"; /* U+2026 */
    private $keySep = ".";

    /**
     * 生成一个表查询对象
     * @param array|Traversable $twoDimDataSource 二维可遍历数据
     * @param string $keyColumn 表作用列
     * @param string $alias 表别名
     * @return static
     */
    public static function from($twoDimDataSource, $keyColumn = "", $alias = "")
    {
        return new static($twoDimDataSource, $keyColumn, $alias);
    }

    /**
     * 生成一个表查询对象
     * Q constructor.
     * @param array|Traversable $twoDimDataSource 二维可遍历数据
     * @param string $keyColumn 表作用列
     * @param string $alias 表别名
     */
    public function __construct($twoDimDataSource, $keyColumn = "", $alias = "")
	{
        // 必须先设置内部迭代器，内部迭代器更新将清除别名与作用列
        // 表作用列设置要以来迭代器做合法性检测

        if(is_object($twoDimDataSource) && $twoDimDataSource instanceof static) {
            $this->innerIterator = $twoDimDataSource->getInnerIterator();

            $this->alias = $alias ?: $twoDimDataSource->getAlias();
            if($keyColumn !== "") {
                $this->setKeyColumn($keyColumn);
            } else {
                $this->keyColumn = $twoDimDataSource->getKeyColumn();
            }
            return;
        }

        $this->setInnerIterator($twoDimDataSource);
        $this->alias = $alias ?: spl_object_hash($this);
        if($keyColumn !== "") {
            $this->setKeyColumn($keyColumn);
        }
	}

    /**
     * 获取表作用列
     * @return string
     */
    public function getKeyColumn()
    {
        return $this->keyColumn;
    }

    /**
     * 设置表作用列
     * @param string $keyColumn
     * @param callable|null $aliasKey
     * @return $this
     */
    public function setKeyColumn($keyColumn, callable $aliasKey = null)
    {
        if(!is_string($keyColumn) || !$keyColumn || !$this->isColumnValid($keyColumn, $aliasKey)) {
            throw new InvalidArgumentException("column \"{$keyColumn}\" not found or invalid");
        }
        $this->keyColumn = $keyColumn;
        return $this;
    }

    /**
     * 获取表别名
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 设置表别名
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 获取内部迭代器
     * @return Iterator
     */
    public function getInnerIterator()
    {
        $it = $this->innerIterator;
        $it->rewind();
        return $it;
    }

    /**
     * 设置内部迭代器
     * 会清除当前表作用列与别名
     * @param array|Traversable $twoDimDataSource
     */
    public function setInnerIterator($twoDimDataSource)
    {
        if($this->canBeTravel($twoDimDataSource)) {
            $this->innerIterator = $this->traversable2D2Iterator($twoDimDataSource);
            $this->innerIterator->rewind();
            $this->alias = "";
            $this->keyColumn = "";
        } else {
            throw new InvalidArgumentException("dataSource should be array or traversable");
        }
    }

    /**
     * Inner Join 支持一对一，一对多，多对多关系
     * @param Q $toJoin
     * @return Q
     */
    public function join(Q $toJoin)
	{
        $join = new QJoin($this, $toJoin, $this->keySep);
        $this->qjoin = $join;
        return $join->join();
	}

    /**
     * Left Join 支持一对一，一对多，多对多关系
     * @param Q $toJoin
     * @return Q
     */
	public function leftJoin(Q $toJoin)
	{
        $join = new QJoin($this, $toJoin, ".");
        return $join->join(true);
	}

    /**
     * Right Join 支持一对一，一对多，多对多关系
     * @param Q $toJoin
     * @return Q
     */
	public function rightJoin(Q $toJoin)
	{
        $join = new QJoin($toJoin, $this, ".");
        return $join->join(true);
	}

    /**
     *
     * @param callable $filterFunc
     * @return Q
     */
	public function where(callable $filterFunc)
	{
        $result = [];
        foreach($this as $row) {
            if($filterFunc($row)) {
                $result[] = $row;
            }
        }

        $keyColumn = $result ? $this->keyColumn : "";
        return static::from($result, $keyColumn, $this->alias);
	}

	public function groupBy($column)
	{
        throw new LogicException("not still impl");
        /*
        if(!$this->isColumnValid($column)) {
            throw new InvalidArgumentException("column \"{$column}\" not found or invalid");
        }

        $result = [];
        foreach($this as $row) {

        }
        */
	}

    public function limit($n = 1)
    {
        $n = min($n, count($this->innerIterator));
        $result =[];
        $this->innerIterator->rewind();
        while($n--) {
            $result[] = $this->innerIterator->current();
            $this->innerIterator->next();
        }
        return static::from($result, $this->keyColumn, $this->alias);
    }

    /**
     * @param callable $cmpFunc
     * @return $this
     */
	public function orderBy(callable $cmpFunc)
	{
        $this->innerIterator->uasort($cmpFunc);
        return $this;
	}

    /**
     * @param callable $satisfyFunc
     * @return bool
     */
	public function any(callable $satisfyFunc)
	{
        foreach($this as $row) {
            if($satisfyFunc($row)) {
                return true;
            }
        }
        return false;
	}

    /**
     * @param callable $satisfyFunc
     * @return bool
     */
	public function all(callable $satisfyFunc)
	{
        foreach($this as $row) {
            if(!$satisfyFunc($row)) {
                return false;
            }
        }
        return true;
	}

    /**
     * 产生新的Q对象
     * @param array $map [key=>newKey, ...] 两个表有同名字段，尽量采用别名方式select
     * @return Q
     */
    public function select(array $map)
    {
        if(!$map) {
            return self::from([]);
        }

        $columns = array_keys($map);
        $mapTo = array_values($map);

        $result = [];
        $newKeyColum = "";
        foreach($this as $row) {
            $newRow = [];
            foreach($columns as $i => $column) {
                // 不设置column别名情况，column为value非key
                if(is_int($column)) {
                    $column = $mapTo[$i];
                }

                // 直接查找
                if(array_key_exists($column, $row)) {
                    $newColumn = $mapTo[$i];
                    $newRow[$newColumn] = $row[$column];
                    // 设置新的keyColumn
                    if($column === $this->keyColumn) {
                        $newKeyColum = $newColumn;
                    }

                } else {
                    // 脱去别名查找
                    if($tmp = explode($this->keySep, $column)) {
                        $originColumn = array_pop($tmp);
                        if(array_key_exists($originColumn, $row)) {
                            $newColumn = $mapTo[$i];
                            $newRow[$newColumn] = $row[$originColumn];
                            // 设置新的keyColumn
                            if($originColumn === $this->keyColumn) {
                                $newKeyColum = $newColumn;
                            }
                        } else {
                            throw new InvalidArgumentException("can not found column \"{$column}\" in table");
                        }
                    }
                }
            }
            $result[] = $newRow;
        }

        return static::from($result, $newKeyColum);
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function toArray()
    {
        // FIXME
        return iterator_to_array($this->innerIterator);
    }

    public function current()
    {
        return $this->innerIterator->current();
    }

    public function next()
    {
        $this->innerIterator->next();
    }

    public function key()
    {
        return $this->innerIterator->key();
    }

    public function valid()
    {
        return $this->innerIterator->valid();
    }

    public function rewind()
    {
        $this->innerIterator->rewind();
    }

    public function __toString()
    {
        /* @var string $str */
        $str = var_export($this->toArray(), true);
        return $str;
    }

    public function __invoke()
    {
        return $this->getInnerIterator();
    }

    /**
     * traversable -> iterator
     * 检测2D traversable 结构，修改key值
     * @param $traver2D
     * @param callable|null $aliasKey
     * @return ArrayIterator
     */
    private function traversable2D2Iterator($traver2D, callable $aliasKey = null)
    {
        $result = [];
        //  = null;
        $columns = [];
        foreach($traver2D as $row) {
            if(!$this->canBeTravel($row)) {
                throw new InvalidArgumentException("2D-table-data is invalid: should be 2 dim traversable");
            }

            $newRow = [];
            $newColumns = [];
            foreach ($row as $k => $v) {
                $k = $aliasKey ? $aliasKey($k) : $k;
                $newRow[$k] = $v;
                $newColumns[] = $k;
            }

            // if($columnCount !== null && $columnCount !== count($newRow)) {
            //     throw new InvalidArgumentException("2D-table-data is invalid: count of columns is not all equal");
            // }
            if($columns !== [] && $newColumns !== $columns) {
                throw new InvalidArgumentException("2D-table-data is invalid: columns is not all equal");
            }
            // $columnCount = count($newRow);
            $columns = $newColumns;

            $result[] = $newRow;
        }
        return new ArrayIterator($result);
    }

    /**
     * 检测列是否存在且合法
     * @param string $keyColumn
     * @param callable|null $aliasKey
     * @return bool
     */
    private function isColumnValid($keyColumn, callable $aliasKey = null)
    {
        $keyColumn = trim($keyColumn);
        $interArr = iterator_to_array($this->getInnerIterator()); // iterator_to_array($this)
        $arrCount = count($interArr); // count($this)
        $keyColumn = $aliasKey === null ? $keyColumn : $aliasKey($keyColumn);
        $colCount = count(array_column($interArr, $keyColumn));
        return ($arrCount !== 0 && $arrCount === $colCount);
    }

    /**
     * 检测是否可以foreach遍历
     * @param array|Traversable $dataSource
     * @return bool
     */
    private function canBeTravel($dataSource)
    {
        if(is_array($dataSource)) {
            return true;
        }
        if(is_object($dataSource) && $dataSource instanceof Iterator) {
            return true;
        }
        return false;
    }
}

/**
 * Class QJoin
 * @author xiaofeng
 * @package Xiaofeng
 */
class QJoin
{
    /**
     * 左表
     * @var Q
     */
    protected $leftTable;

    /**
     * 右表
     * @var Q
     */
    protected $rightTable;

    /**
     * 左表别名
     * @var string
     */
    protected $leftAlias;

    /**
     * 右表别名
     * @var string
     */
    protected $rightAlias;

    /**
     * 左表joinkey
     * @var string
     */
    protected $leftKey;

    /**
     * 右表joinkey
     * @var string
     */
    protected $rightKey;

    /**
     * 左表列
     * @var array
     */
    protected $leftColumns = [];

    /**
     * 右表列
     * @var array
     */
    protected $rightColumns = [];

    /**
     * 重名key分隔符
     * @var string
     */
    protected $keySep;

    /**
     * QJoin constructor.
     * @param Q $leftTable
     * @param Q $rightTable
     * @param string $keySep
     */
    public function __construct(Q $leftTable, Q $rightTable, $keySep = ".")
    {
        $this->keySep = trim(strval($keySep));

        $this->leftAlias = $leftTable->getAlias();
        $this->rightAlias = $rightTable->getAlias();

        if($this->leftAlias === $this->rightAlias) {
            throw new LogicException("the alias name of two table should not be the same '{$this->leftAlias}'");
        }

        $this->leftKey = $leftTable->getKeyColumn();
        $this->rightKey = $rightTable->getKeyColumn();

        if($this->leftKey === "" || $this->rightKey === "") {
            throw new LogicException("keycolumn shoule be set before join");
        }

        $this->leftTable = $leftTable;
        $this->rightTable = $rightTable;
    }

    /**
     * 简易的nested loop join
     * 注意：join之后生成新的Q对象
     * 使用leftTable的keyColumn做新的keyColumn
     * @param bool $isLeft 默认inner join，参数true执行left join
     * @return Q
     */
    public function join($isLeft = false) {
        $result = [];
        foreach($this->leftTable->getInnerIterator() as $leftRow) {
            if($this->leftColumns === []) {
                $this->leftColumns = array_keys($leftRow);
            }
            $inRightTable = false;
            foreach($this->rightTable->getInnerIterator() as $rightRow) {
                if($this->rightColumns === []) {
                    $this->rightColumns = array_keys($rightRow);
                }
                if(array_key_exists($this->leftKey, $leftRow) && array_key_exists($this->rightKey, $rightRow)) {
                    if($leftRow[$this->leftKey] === $rightRow[$this->rightKey]) {
                        $result[] = $this->mergeRow($leftRow, $rightRow);
                        $inRightTable = true;
                    }
                }
            }
            if($isLeft && !$inRightTable) {
                $rightRow = array_combine($this->rightColumns, array_fill(0, count($this->rightColumns), null));
                $result[] = $this->mergeRow($leftRow, $rightRow);
            }
        }

        // 新的keyColumn以左表为准
        $newKeyColumn = count(array_column($result, $this->leftKey)) === 0 ? $this->getAliasKey($this->leftAlias, $this->leftKey) : $this->leftKey;
        return Q::from($result, $newKeyColumn);
    }

    /**
     * 为重名key起别名
     * @param $alias
     * @param $key
     * @return string
     */
    public function getAliasKey($alias, $key)
    {
        return $alias . $this->keySep . $key;
    }

    /**
     * 合并两个表的关联行，部分重命名
     * @param array $leftRow
     * @param array $rightRow
     * @return array
     */
    protected function mergeRow(array $leftRow, array $rightRow, &$newColumnKey = "")
    {
        // 无重名key
        if(array_intersect_key($leftRow, $rightRow) === []) {
            return $leftRow + $rightRow;
        }

        $result = $leftRow;
        foreach($rightRow as $rightKey => $rightValue) {
            // 重命名同名key
            if(isset($result[$rightKey])) {
                // left
                $newLeftKey = $this->getAliasKey($this->leftAlias, $rightKey);
                $result[$newLeftKey] = $result[$rightKey];
                unset($result[$rightKey]);
                // right
                $newRightKey = $this->getAliasKey($this->rightAlias, $rightKey);
                $result[$newRightKey] = $rightValue;
            } else {
                $result[$rightKey] = $rightValue;
            }
        }
        return $result;
    }
}