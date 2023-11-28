<?php
/**
 * Builder.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Class Builder
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class Builder extends EloquentBuilder
{

    public function all($columns = ['*'])
    {
        return $this->paginate($this->getModel()->maxMatches(),$columns,'page',1);
    }

    public function paginate($perPage = 20, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = $this->forPage($page, $perPage)->get($columns);

        $total = $this->getCountForPagination($columns);

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        $metas = $this->getQuery()->getConnection()->select('SHOW META');
        // mysql> SHOW META;
        // +---------------+-------+
        // | Variable_name | Value |
        // +---------------+-------+
        // | total         | 1000  |
        // | total_found   | 1014  |
        // | time          | 0.000 |
        // +---------------+-------+

        $total = 0;
        $totalFound = 0;

        foreach ($metas as $meta) {
            $meta = array_change_key_case((array)$meta);
            if ($meta['variable_name'] === 'total') {
                $total = $meta['value'];
            } else if ($meta['variable_name'] === 'total_found') {
                $totalFound = $meta['value'];
            }
        }

        return min($total, $totalFound);
    }
}
