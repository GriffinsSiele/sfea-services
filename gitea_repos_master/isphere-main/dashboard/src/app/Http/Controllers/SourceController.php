<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Source;

use App\DataGrid\GridDataProvider;

class SourceController extends Controller
{

    protected $commonRules = [
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sourcess = Source::query();

        if($request->has('sort'))
            $checkTypes = Source::query();
        //else
        //    $checkTypes = Source::orderBy('code', 'asc');

        $dataProvider = new GridDataProvider($sourcess);

        return view('private.source.list')
            ->with('pageTitle', 'Управление источниками')
            ->with('dataProvider', $dataProvider);
    }

    
    public function create()
    {
        $item = new Source();

        return view('private.source.create')
            ->with('pageTitle', 'Создание источника')
            ->with('item', $item);
    }

    public function store(Request $request)
    {
        $fields = array_map(function ($filed) {
            if(is_string($filed) || is_null($filed)) {
                $filed = trim($filed);
                $filed = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $filed);
            }

            return $filed;
        }, $request->all());

        $validator = Validator::make($fields, $this->commonRules);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $source = new Source($fields);
        
        $source->save();

        return redirect()
            ->route('sources.edit', ['source'=>$source->id]);
    }

    public function edit(Source $source)
    {
        return view('private.source.edit')
            ->with('pageTitle', 'Редактирование источника')
            ->with('item', $source);
    }

    public function update(Request $request, Source $source)
    {
        $fields = array_map(function ($filed) {
            if(is_string($filed) || is_null($filed)) {
                $filed = trim($filed);
                $filed = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $filed);
            }

            return $filed;
        }, $request->all());

        $validator = Validator::make($fields, $this->commonRules);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $source->fill($fields);
        $source->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('sources.edit', ['source'=>$source->id]);
    }

}
