<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Field;
use App\Exports\FieldsExport;

use App\DataGrid\GridDataProvider;

class FieldController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fieldsSources = Field::query()
            ->select('source_name')
            ->groupBy('source_name')
            ->orderBy('source_name', 'asc');

        $dataProvider = new GridDataProvider($fieldsSources);

        return view('private.field.list')
            ->with('pageTitle', 'Управление полями источников')
            ->with('dataProvider', $dataProvider);
    }

    public function edit(Request $request, $source)
    {
        $fields = Field::where('source_name', '=', $source);

        $dataProvider = new GridDataProvider($fields);

        return view('private.field.edit')
            ->with('pageTitle', 'Управление полями: '.$source)
            ->with('dataProvider', $dataProvider);
    }

    public function downloadExcel(Request $request) {
        $query = Field::query()->orderBy('source_name', 'asc');

        if(($mass = $request->input('mass')) && isset($mass['ids']) && preg_match_all('/([^,]+)/', $mass['ids'], $match) && count($match[1])) { // Дофильтрация по галкам

            if(isset($mass['mode']) && $mass['mode'] == 'include')
                $query->whereIn('source_name', $match[1]);
            else
                $query->whereNotIn('source_name', $match[1]);
        }

        return (new FieldsExport($query))->download('Поля.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Access  $access
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Field $field)
    {
        //Gate::authorize('fields', $field); // todo: ...

        $field->fill($request->all());

        if($field->save())
            return response()->json(['status' => 'ok']);

        return response()->json(['status' => 'error']);
    }

}
