<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

//use Itstructure\GridView\DataProviders\EloquentDataProvider;
use App\DataGrid\GridDataProvider;
use App\Exports\ClientsExport;
use App\Models\{ClientUsageLimits, Client, Message, Phone, RequestNew, User, Tariff};

class ClientController extends Controller
{
    protected $commonRulesMes = [
        'Phone.regex' => 'Формат номера телефона должен быть 79999999999',
    ];
    protected $commonRules = [
        'Name' => 'required|min:1|max:50',
        'Code' => 'required|max:50',
        'TariffId' => 'required',
        'BankAccount' => 'Numeric|Nullable',
        'KPP' => 'Numeric|Nullable',
        'Phone' => 'regex:/^7[0-9]{10}$/|Nullable',
    ];

    public function selectSearch(Request $request)
    {
        $clients = [];

        if($request->has('q')){

            $clients = Client::select("Id", "OfficialName")
                ->where('OfficialName', 'LIKE', "%".$request->q."%");

            if (!Gate::allows('use-function', 'clients_all')) {
                $user = Auth::user();

                $clients->where('MasterUserId', $user->Id)
                    ->orWhere('id', $user->ClientId);
            }

            $clients = $clients->get();
        }

        return response()->json($clients);
    }

    public function lastDayRequestsCount(Client $client)
    {
        Gate::authorize('update-client', $client);

        $rCount = RequestNew::where('client_id', '=', $client->id)
            ->where('created_date','>=', date('Y-m-d', time()-24*60*60))
            ->count();

        return response()->json(['requestsCount'=>$rCount]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //$clients = Client::orderBy('Id', 'desc');
        $clients = Client::query();

        if (!Gate::allows('use-function', 'clients_all')) {
            $user = Auth::user();

            $clients->where('MasterUserId', $user->Id)
                ->orWhere('id', $user->ClientId);
        }

        $dataProvider = new GridDataProvider($clients);

        $dataProvider->setComplexFilter('clientExpired', [$this, 'clientExpiredFilterFunction'] );

        /*if($request->input('OfficialName', false)) {
            $clients->where('OfficialName', 'like', '%'.$request->input('OfficialName').'%');
        }*/

        return view('private.client.list')
            ->with('pageTitle', 'Управление клиентами')
            ->with('request', $request)
            ->with('dataProvider', $dataProvider);

            //->with('clients', $clients->paginate(30));
    }

    public function clientExpiredFilterFunction(\Illuminate\Database\Eloquent\Builder &$query, $value) {

        if(!empty($value))
            $query->where('EndTime', '<', date('Y-m-d H:i:s'));

        return;
    }

    public function addLimit(Request $request, Client $client)
    {
        $fields = $request->except(['_token', '_method']);

        $fields = array_filter($request->all(), function ($filed) { return !is_null($filed) && (!is_string($filed) || strlen($filed)); });

        $cul = new ClientUsageLimits($fields);

        $cul->ClientId = $client->id;

        $cul->save();

        return redirect()
            ->route('clients.limits', ['client'=>$client->id]);
    }

    public function removeLimit(Client $client, ClientUsageLimits $limit)
    {
        if($limit->ClientId == $client->id) {
            $limit->delete();
        }

        return redirect()
            ->route('clients.limits', ['client'=>$client->id]);
    }

    public function limits(Client $client)
    {
        $limits = ClientUsageLimits::where('ClientId', $client->id)->get();

        $limitsTypeMap = [];
        $availableLimits = [];

        foreach ($limits as $limit)
            $limitsTypeMap[$limit->PeriodType] = 1;

        foreach (ClientUsageLimits::$periodTypes as $pType => $ptLabel)
            if(!isset($limitsTypeMap[$pType]))
                $availableLimits[$pType] = $ptLabel;

        return view('private.client.limits')
            ->with('pageTitle', 'Лимиты')
            ->with('limits', $limits)
            ->with('availableLimits', $availableLimits)
            ->with('client', $client);
    }

    public function journal(Client $client)
    {
        $tariffs = Tariff::orderBy('Name', 'asc');
        $valuesMap['TariffId'] = $tariffs->pluck('Name', 'id');
        $valuesMap['Status'] = Client::$statusMap;

        $dataProvider = new GridDataProvider($client->histories()->orderBy('performed_at', 'desc')->getQuery());

        return view('private.client.journal')
            ->with('pageTitle', 'Журнал изменений клиента')
            ->with('valuesMap', $valuesMap)
            ->with('dataProvider', $dataProvider);
    }

    public function downloadExcel(Request $request) {

        $clients = Client::query();

        if (!Gate::allows('use-function', 'clients_all')) {
            $user = Auth::user();

            $clients->where('MasterUserId', $user->Id)
                ->orWhere('id', $user->ClientId);
        }

        $dataProvider = new GridDataProvider($clients);
        $dataProvider->setComplexFilter('clientExpired', [$this, 'clientExpiredFilterFunction'] );
        $dataProvider->selectionConditions($request, ['Status']);

        $query = $dataProvider->getQuery();

        if(($mass = $request->input('mass')) && isset($mass['ids']) && preg_match_all('/([\d]+)/', $mass['ids'], $match) && count($match[1])) { // Дофильтрация по галкам

            if(isset($mass['mode']) && $mass['mode'] == 'include')
                $query->whereIn('Id', $match[1]);
            else
                $query->whereNotIn('Id', $match[1]);
        }

        return (new ClientsExport($query))->download('Клиенты.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $client = new Client();

        $tariffs = Tariff::orderBy('Name', 'asc');
        $tariffsMap = $tariffs->pluck('Name', 'id');

        $users = User::orderBy('Login', 'asc')->where('AccessArea', 3);
        $usersMap = [''=>'Не задано'] + $users->pluck('Login', 'id')->toArray();

        return view('private.client.create')
            ->with('pageTitle', 'Создание клиента')
            ->with('client', $client)
            ->with('users', $usersMap)
            ->with('tariffs', $tariffsMap);
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

        $validator = Validator::make($fields, $this->commonRules, $this->commonRulesMes);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $client = new Client($fields);

        if(Gate::allows('use-function', 'clients_all')) {
            $client->MessageId = empty($fields['MessageId']) ? null : $fields['MessageId'];
            $client->MasterUserId = empty($fields['MasterUserId']) ? null : $fields['MasterUserId'];
            $client->StartTime = empty($fields['StartTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['StartTime']));
            $client->EndTime = empty($fields['EndTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['EndTime']));
        }

        $cu = Auth::user();
        //$client->MasterUserId = $cu->Id;

        $client->save();

        if(isset($fields['Phones'])) {
            $savedPhonesObjects = [];
            $valid = Phone::fillAndSaveList($fields['Phones'], $client->id, 'client', $savedPhonesObjects);

            if($valid!==true)
                return back()->withErrors($valid)->withInput();

            if(count($savedPhonesObjects)) {
                $ch = $client->addCustomHistory(['key' => 'phones', 'new' => implode('/', Phone::compileListToStringArray($savedPhonesObjects))], true);

                if($ch) {
                    event('eloquent.updating: App\Models\Client', $client);
                    $client->clearCustomHistory();
                }
            }

        }

        return redirect()
            ->route('clients.edit', ['client'=>$client->id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        Gate::authorize('update-client', $client);

        $tariffs = Tariff::orderBy('Name', 'asc');
        $tariffsMap = $tariffs->pluck('Name', 'id');

        $users = User::orderBy('Login', 'asc')->where('AccessArea', 3);
        $usersList = [0=>'Не задано'] + $users->pluck('Login', 'id')->toArray();

        $messages = Message::orderBy('id', 'desc');
        $messagesList = [0=>'Не задано'] + $messages->pluck('Text', 'id')->toArray();

        foreach ($messagesList as $mid => $text)
            $messagesList[$mid] = mb_substr($text, 0, 100).(mb_strlen($text) > 100 ? ' ...' : '');

        $query = User::where('ClientId', '=', $client->id);

        $dataProvider = new \App\DataGrid\GridDataProvider($query);

        return view('private.client.edit')
            ->with('pageTitle', 'Редактирование клиента')
            ->with('client', $client)
            ->with('dataProvider', $dataProvider)
            ->with('users', $usersList)
            ->with('messages', $messagesList)
            ->with('tariffs', $tariffsMap);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Client $client)
    {
        Gate::authorize('update-client', $client);

        $fields = array_filter($request->all(), function ($filed) { return !is_null($filed) && (!is_string($filed) || strlen($filed)); });

        $validator = Validator::make($fields, $this->commonRules, $this->commonRulesMes);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();

        if(Gate::allows('use-function', 'clients_all')) {
            $client->MessageId = empty($fields['MessageId']) ? null : $fields['MessageId'];
            $client->MasterUserId = empty($fields['MasterUserId']) ? null : $fields['MasterUserId'];
            $client->StartTime = empty($fields['StartTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['StartTime']));
            $client->EndTime = empty($fields['EndTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['EndTime']));

            $fields['TariffStartDate'] = empty($fields['TariffStartDate']) ? null : $fields['TariffStartDate'];
        }

        $client->fill($fields);

        $client->save();

        if(isset($fields['Phones'])) {
            $originalPhonesObjects = $client->phones;
            $savedPhonesObjects = [];

            $valid = Phone::fillAndSaveList($fields['Phones'], $client->id, 'client', $savedPhonesObjects);

            if($valid!==true)
                return back()->withErrors($valid)->withInput();

            $ch = $client->addCustomHistory(['key' => 'phones', 'new' => implode('/', Phone::compileListToStringArray($savedPhonesObjects)), 'old'=>implode('/', Phone::compileListToStringArray($originalPhonesObjects))], true);

            if($ch) {
                event('eloquent.updating: App\Models\Client', $client);
                $client->clearCustomHistory();
            }
        }

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('clients.edit', ['client'=>$client->id]);
    }
}
