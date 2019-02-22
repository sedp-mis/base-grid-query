<?php

namespace SedpMis\BaseGridQuery\Search;

use DB;
use SedpMis\BaseGridQuery\BaseSearchQuery;

/**
 * A search query resembling the behaviour in sublime file search (ctrl+p).
 */
class SublimeSearch extends BaseSearchQuery
{
    /**
     * The query for the search.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Columns for sorting query.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * Construct.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $searchable
     * @param bool $sort
     * @param array $columns
     */
    public function __construct($query, $columns = [], $sort = true, $searchOperator = 'where')
    {
        $this->query          = $query;
        $this->columns        = $columns;
        $this->sort           = $sort;
        $this->searchOperator = $searchOperator;
    }

    /**
     * Get the actual searchable column of the given column key.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey)
    {
        $columns = $this->searchable();

        if (array_key_exists($columnKey, $columns)) {
            return $columns[$columnKey];
        }

        foreach ($columns as $column) {
            if ($column === $columnKey || ends_with($column, ".{$columnKey}")) {
                return $column;
            }
        }
    }

    /**
     * Getter for searchable column.
     *
     * @param  string $columnKey
     * @return string|mixed
     */
    public function __get($columnKey)
    {
        return $this->getColumn($columnKey);
    }

    /**
     * Return the searchable columns, actual columns for `where` operator and alias column names for `having` operator.
     *
     * @return array
     */
    public function searchable()
    {
        return $this->searchOperator === 'having' ? $this->columnKeys() : array_values($this->columns());
    }

    /**
     * Get the keys of columns to be used in the query result.
     *
     * @return array
     */
    public function columnKeys()
    {
        $columnKeys = [];

        foreach ($this->columns() as $key => $column) {
            if (is_string($key)) {
                $columnKeys[] = $key;
            } elseif (str_contains($column, '.')) {
                list($table, $columnKey) = explode('.', $column);
                $columnKeys[]            = $columnKey;
            } else {
                $columnKeys[] = $column;
            }
        }

        return $columnKeys;
    }

    /**
     * Apply search query.
     *
     * @param  string|mixed  $searchStr
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($searchStr)
    {
        $conditions = [];

        $parsedStr = $this->parseSearchStr($this->searchStr = $searchStr);

        foreach ($this->searchable() as $column) {
            $conditions[] = $column.' like "'.$parsedStr.'"';
        }

        $method = $this->searchOperator.'Raw';
        $query  = $this->query()->{$method}('('.join(' OR ', $conditions).')');

        return $query;
    }

    /**
     * Parse string to search.
     *
     * @param  string|mixed $searchStr
     * @return string
     */
    protected function parseSearchStr($searchStr)
    {
        $searchStr = preg_replace('/[^A-Za-z0-9]/', '', $searchStr);

        return '%'.join('%', str_split($searchStr)).'%';
    }

    /**
     * Return the columns for sorting query.
     *
     * @return array
     */
    protected function columns()
    {
        return $this->columns;
    }
}
