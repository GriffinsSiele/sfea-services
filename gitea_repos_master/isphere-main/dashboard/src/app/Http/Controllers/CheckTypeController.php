<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\CheckType;

use App\DataGrid\GridDataProvider;

class CheckTypeController extends Controller
{
    protected $commonRules = [
        'code' => 'required',
        'title' => 'required',
        'source_code' => 'required',
        'source_name' => 'required',
        'source_title' => 'required',
        //'rotation' => 'Numeric|Nullable',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $checkTypes = CheckType::query();

        if($request->has('sort'))
            $checkTypes = CheckType::query();
        else
            $checkTypes = CheckType::orderBy('code', 'asc');

        $dataProvider = new GridDataProvider($checkTypes);

        return view('private.check_type.list')
            ->with('pageTitle', 'Управление проверками')
            ->with('dataProvider', $dataProvider);
    }

   
    public function create()
    {
        $item = new CheckType();

        return view('private.check_type.create')
            ->with('pageTitle', 'Создание проверки')
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


        $checkType = new CheckType($fields);
        
        $checkType->save();

        return redirect()
            ->route('check-types.edit', ['check_type'=>$checkType->id]);
    }

    public function edit(CheckType $checkType)
    {
        return view('private.check_type.edit')
            ->with('pageTitle', 'Редактирование проверки')
            ->with('item', $checkType);
    }

    public function update(Request $request, CheckType $checkType)
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


        $checkType->fill($fields);
        $checkType->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('check-types.edit', ['check_type'=>$checkType->id]);
    }

    public function updateStatus(Request $request, CheckType $checkType)
    {
        Gate::authorize('update-access', $checkType); // todo: ...

        if(isset(CheckType::$statusMap[$request->input('status', false)])) {
            $checkType->status = $request->input('status', false);
            $checkType->save();
        }
        else
            return response()->json(['status' => 'error']);

        return response()->json(['status' => 'ok']);
    }

}
