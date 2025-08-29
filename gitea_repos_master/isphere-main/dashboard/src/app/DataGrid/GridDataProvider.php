<?php

namespace App\DataGrid;

use Illuminate\Http\Request;
use Itstructure\GridView\Helpers\SortHelper;

use Itstructure\GridView\DataProviders\EloquentDataProvider;

class GridDataProvider extends EloquentDataProvider
{
    protected $complexFilters = [];

    public function setComplexFilter($name, $filterFunction) {
        $this->complexFilters[$name] = $filterFunction;

        return $this;
    }

    public function selectionConditions(Request $request, $strictFilters = false): void
    {
        if ($request->get('sort', null)) {
            $this->query->orderBy(SortHelper::getSortColumn($request), SortHelper::getDirection($request));
        }

        if (!is_null($request->filters)) {
            foreach ($request->filters as $column => $value) {

                if(isset($this->complexFilters[$column])) {
                    $this->complexFilters[$column]($this->query, $value);
                    continue;
                }

                if (is_null($value)) {
                    continue;
                }

                if ((is_bool($strictFilters) && $strictFilters) || (is_array($strictFilters) && in_array($column, $strictFilters))) {
                    $this->query->where($column, '=', $value);
                } else {
                    $this->query->where($column, 'like', '%' . $value . '%');
                }
            }
        }
    }

    public function getQuery() {
        return $this->query;
    }
}
