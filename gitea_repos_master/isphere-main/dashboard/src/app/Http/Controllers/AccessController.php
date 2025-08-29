<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Access;
use App\Models\AccessSource;
use App\Models\CheckType;

use Itstructure\GridView\DataProviders\EloquentDataProvider;

class AccessController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->has('sort'))
            $accesses = Access::query();
        else
            $accesses = Access::orderBy('Name', 'desc');

        $dataProvider = new EloquentDataProvider($accesses);

        return view('private.access.list')
            ->with('pageTitle', 'Управление доступами')
            ->with('dataProvider', $dataProvider);
            //->with('accesses', $accesses->paginate(30));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $access = new Access();

        return view('private.access.create')
            ->with('pageTitle', 'Создание доступа')
            ->with('access', $access);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->all();

        $validator = Validator::make($fields, [
            //'Level' => 'required|unique:Access|integer',
            'Name' => 'required|max:50',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $access = new Access($fields);
        //$access->Level = $fields['Level'];

        $access->save();

        return redirect()
            ->route('accesses.edit', ['access'=>$access->Level]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Access  $access
     * @return \Illuminate\Http\Response
     */
    public function edit(Access $access)
    {
        Gate::authorize('update-access', $access);

        $sources = [];

        $checkTypes = CheckType::query()
            //->groupby('source_code','source_title')
            ->orderBy('source_code', 'asc')
            ->get();
            //->pluck('source_title','source_code')
            //->toArray();

        foreach ($checkTypes as $checkType)
            $sources[$checkType->source_code][] = $checkType;

        $activeSources = $access->accessSources()->where('allowed', '1')->pluck('source_name')->toArray();

        return view('private.access.edit')
            ->with('pageTitle', 'Редактирование доступа')
            ->with('access', $access)
            ->with('activeSources', $activeSources)
            ->with('sources', $sources);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Access  $access
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Access $access)
    {
        Gate::authorize('update-access', $access);

        //$fields = array_filter($request->all(), 'strlen');
        $fields = array_filter($request->all(), function ($filed) { return is_array($filed) || !is_null($filed) && (!is_string($filed) || strlen($filed)); });

        $rules = [
            'Name' => 'string|max:50',
        ];

        $validator = Validator::make($fields, $rules);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $access->fill($fields);
        $access->save();

        foreach ($fields['accessSources'] as $source => $val) {
            $accessSource = $access->accessSources()->where('source_name', $source)->first();

            if(!$accessSource) {
                $accessSource = new AccessSource();
                $accessSource->source_name = $source;
                $accessSource->Level = $access->Level;
            }

            $accessSource->allowed = 1;

            $accessSource->save();
        }

        AccessSource::where('Level', $access->Level)
            ->whereNotIn('source_name', array_keys($fields['accessSources']))
            ->delete();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('accesses.edit', ['access'=>$access->Level]);
    }

    public function sourceAdd(Request $request, Access $access)
    {
        $fields = $request->all();

        $validator = Validator::make($fields, [
            'source_name' => 'required|max:50',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();

        $accessSource = $access->accessSources()->where('source_name', $fields['source_name'])->first();

        if(!$accessSource) {
            $accessSource = new AccessSource($fields);
            $accessSource->Level = $access->Level;
        }

        $accessSource->allowed = 1;

        $accessSource->save();

        return redirect()
            ->route('accesses.edit', ['access'=>$access->Level]);
    }

    public function sourceRemove(Request $request, Access $access, AccessSource $source)
    {
        if ($access->Level == $source->Level)
            $source->delete();

        return back();
    }
}
