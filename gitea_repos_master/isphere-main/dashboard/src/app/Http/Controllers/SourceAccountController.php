<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\SourceAccount;
use App\Models\Source;
use App\Models\Client;
use App\Models\User;

//use Itstructure\GridView\DataProviders\EloquentDataProvider;
use App\DataGrid\GridDataProvider;

class SourceAccountController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sourceAccounts = SourceAccount::with('source');//->orderBy('sourceid', 'desc');

        $sources = Source::orderBy('name', 'asc');//->where('enabled', 1);

        $dataProvider = new GridDataProvider($sourceAccounts);

        $dataProvider->selectionConditions($request);

        $dataProvider->setComplexFilter('source', [$this, 'sourceFilterFunction'] );

        return view('private.source_account.list')
            ->with('pageTitle', 'Управление аккаунтами источников')
            ->with('sources', $sources->pluck('name', 'id')->toArray())
            ->with('dataProvider', $dataProvider);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sourceAccount = new SourceAccount();

        $sources = Source::orderBy('name', 'asc')->where('enabled', 1);

        return view('private.source_account.create')
            ->with('pageTitle', 'Создание аккаунта')
            ->with('sources', $sources->pluck('name', 'id')->toArray())
            ->with('sa', $sourceAccount);
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
            'login' => 'required|min:1',
            'password' => 'required|min:1',
            'note' => 'string|min:0|max:50',
            //'clientid' => 'required',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $sourceAccount = new SourceAccount($fields);
        //$sourceAccount->Level = $fields['Level'];

        $sourceAccount->save();

        return redirect()
            ->route('source-accounts.edit', ['source_account'=>$sourceAccount->sourceaccessid]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SourceAccount  $sourceAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, SourceAccount $sourceAccount)
    {
        $sources = Source::orderBy('name', 'asc')->where('enabled', 1);

        return view('private.source_account.edit')
            ->with('pageTitle', 'Редактирование аккаунта')
            ->with('sources', $sources->pluck('name', 'id')->toArray())
            ->with('sa', $sourceAccount);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SourceAccount  $sourceAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SourceAccount $sourceAccount)
    {
        $fields = array_filter($request->all(), 'strlen');

        $rules = [
            'login' => 'required|min:1',
            'note' => 'string|min:0|max:50',
        ];

        $validator = Validator::make($fields, $rules);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $sourceAccount->fill($fields);
        $sourceAccount->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('source-accounts.edit', ['source_account'=>$sourceAccount->sourceaccessid]);
    }

    public function remove(Request $request, SourceAccount $sourceAccount)
    {
        if($sourceAccount->status != 0)
            return back();

        $sourceAccount->delete();

        return back();
    }
}
