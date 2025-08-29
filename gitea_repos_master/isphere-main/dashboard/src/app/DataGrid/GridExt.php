<?php
namespace App\DataGrid;

use Illuminate\Pagination\LengthAwarePaginator;

class GridExt extends \Itstructure\GridView\Grid
{
    public $additionalFiltersTpl;

    /**
     * @return string
     */
    public function render(): string
    {
        $this->applyColumnsConfig();

        $this->dataProvider->selectionConditions($this->request, $this->strictFilters);

        $totalCount = $this->dataProvider->getCount();
        $pageNumber = $this->request->get($this->paginatorOptions['pageName'] ?? 'page', $this->page);

        $this->paginator = new LengthAwarePaginator(
            $this->dataProvider->get($this->rowsPerPage, $pageNumber),
            $totalCount,
            $this->rowsPerPage,
            $pageNumber,
            $this->paginatorOptions
        );

        return view('grid_view::grid', [
            'columnObjects' => $this->columnObjects,
            'useFilters' => $this->useFilters,
            'paginator' => $this->paginator,
            'title' => $this->title,
            'rowsFormAction' => $this->rowsFormAction,
            'filtersFormAction' => $this->filtersFormAction,
            'additionalFiltersTpl' => $this->additionalFiltersTpl,
            'useSendButtonAnyway' => $this->useSendButtonAnyway,
            'searchButtonLabel' => $this->getSearchButtonLabel(),
            'resetButtonLabel' => $this->getResetButtonLabel(),
            'sendButtonLabel' => $this->getSendButtonLabel(),
            'tableBordered' => $this->tableBordered,
            'tableStriped' => $this->tableStriped,
            'tableHover' => $this->tableHover,
            'tableSmall' => $this->tableSmall
        ])->render();
    }
}