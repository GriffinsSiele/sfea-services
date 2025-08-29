<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Proxy;

use App\DataGrid\GridDataProvider;

class ProxyController extends Controller
{

    protected $commonRules = [
        'server' => 'required',
        'port' => 'Numeric|required',
        'proxygroup' => 'Numeric|Nullable',
        'rotation' => 'Numeric|Nullable',
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $proxies = Proxy::query();

        if($request->has('sort'))
            $checkTypes = Proxy::query();
        //else
        //    $checkTypes = Proxy::orderBy('code', 'asc');

        $dataProvider = new GridDataProvider($proxies);

        return view('private.proxy.list')
            ->with('pageTitle', 'Управление прокси')
            ->with('dataProvider', $dataProvider);
    }

    
    public function create()
    {
        $item = new Proxy();

        return view('private.proxy.create')
            ->with('pageTitle', 'Добавление прокси')
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


        $proxy = new Proxy($fields);
        
        $proxy->save();

        return redirect()
            ->route('proxies.edit', ['proxy'=>$proxy->id]);
    }

    public function edit(Proxy $proxy)
    {
        return view('private.proxy.edit')
            ->with('pageTitle', 'Редактирование прокси')
            ->with('item', $proxy);
    }

    public function update(Request $request, Proxy $proxy)
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


        $proxy->fill($fields);
        $proxy->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('proxies.edit', ['proxy'=>$proxy->id]);
    }

}
