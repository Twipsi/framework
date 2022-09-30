<?php

namespace Twipsi\Components\Database\Builder;

use Exception;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;

trait BuildsPagination
{
    /**
     * Set offset and limit for a page automatically.
     *
     * @param int $page
     * @param int $rows
     * @return QueryBuilder|BuildsPagination
     */
    public function page(int $page, int $rows = 15): self
    {
        return $this->offset(($page - 1) * $rows)
            ->limit($rows);
    }

    /**
     * Get the next page rows.
     *
     * @param int $rows
     * @param int $id
     * @param string $column
     * @return QueryBuilder|BuildsPagination
     * @throws NotSupportedException
     * @throws Exception
     */
    public function nextPage(int $rows = 15, int $id = 0, string $column = self::IDCOLUMN): self
    {
        unset($this->orders[$column]);
        $this->where($column, '<', $id);

        return $this->order($column, 'desc')
            ->limit($rows);
    }

    /**
     * Get the prev page rows.
     *
     * @param int $rows
     * @param int $id
     * @param string $column
     * @return QueryBuilder|BuildsPagination
     * @throws Exception
     */
    public function prevPage(int $rows = 15, int $id = 0, string $column = self::IDCOLUMN): self
    {
        unset($this->orders[$column]);
        $this->where($column, '>', $id);

        return $this->order($column, 'asc')
            ->limit($rows);
    }

    public function paginate(int $rows = 15, string $name = 'page', int $page = null)
    {
        // Add the counter cross join to the query.
        $total = $this->countTotalResultsOfQuery();

        return $total ? $this->page($page, $rows)->get() : [];
    }

    /**
     * Count all the results without limiting.
     *
     * @return int
     * @throws NotSupportedException
     * @throws ApplicationManagerException
     */
    protected function countTotalResultsOfQuery(): int
    {
        $count = $this->cloneWithout('columns')
            ->aggregate('count', 'total')
            ->column('total');

        return (int) $count;
    }


}