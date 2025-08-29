<?php

namespace App\DataGrid;

use Itstructure\GridView\Columns\CheckboxColumn;

class CheckboxColumn extends CheckboxColumn
{
    protected $checked;

    /**
     * @var bool|callable
     */
    protected $display = true;

    /**
     * ActionColumn constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->loadConfig($config);

        $this->filter = new StubFilter();
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? trans('grid_view::grid.deletion');
    }

    /**
     * @param $row
     * @return mixed
     */
    public function getValue($row)
    {
        return $row->{$this->attribute} ?? '';
    }

    /**
     * @param $row
     * @return array|string
     */
    public function render($row)
    {
        if ($this->display === false) {
            return view('grid_view::columns.checkbox-stub')->render();
        }

        if (is_callable($this->display) && !call_user_func($this->display, $row)) {
            return view('grid_view::columns.checkbox-stub')->render();
        }

        return view('grid_view::columns.checkbox', [
            'field' => $this->field,
            'value' => $this->getValue($row),
        ])->render();
    }
}
