<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;

use App\Models\{UserUsageLimits, Client, Access, Message, Phone, RequestNew, User};

//use http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;

//use Itstructure\GridView\DataProviders\EloquentDataProvider;
use App\DataGrid\GridDataProvider;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $users = User::getUsersQuery($user);

        //if($request->input('Login', false)) {
        //    $users->where('Login', 'like', '%'.$request->input('Login').'%');
        //}

        $dataProvider = new GridDataProvider($users);
        $dataProvider->setComplexFilter('Client_OfficialName', [$this, 'clientOfficialNameFilterFunction'] );
        $dataProvider->setComplexFilter('userExpired', [$this, 'userExpiredFilterFunction'] );

        $alArray = [];

        if (Gate::allows('use-function', 'access_levels_all')) {
            $accessLevels = Access::orderBy('Name', 'asc');
            $alArray = $accessLevels->pluck('Name', 'Level');
        }

        return view('private.user.list')
            ->with('pageTitle', 'Управление пользователями')
            ->with('request', $request)
            ->with('accessLevels', $alArray)
            ->with('dataProvider', $dataProvider);
            //->with('users', $users->paginate(30));
    }

    public function downloadExcel(Request $request) {
        $query = $this->getMassSelectQuery($request);

        return (new UsersExport($query))->download('Пользователи.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    protected function getMassSelectQuery(Request $request) {
        $user = Auth::user();

        $users = User::getUsersQuery($user);

        $dataProvider = new GridDataProvider($users);
        $dataProvider->setComplexFilter('Client_OfficialName', [$this, 'clientOfficialNameFilterFunction'] );
        $dataProvider->setComplexFilter('ClientId', [$this, 'clientIdFilterFunction'] );
        $dataProvider->setComplexFilter('userExpired', [$this, 'userExpiredFilterFunction'] );

        $dataProvider->selectionConditions($request, ['AccessLevel', 'AccessArea', 'Locked']);

        $query = $dataProvider->getQuery();

        if(($mass = $request->input('mass')) && isset($mass['ids']) && preg_match_all('/([\d]+)/', $mass['ids'], $match) && count($match[1])) { // Дофильтрация по галкам

            if(isset($mass['mode']) && $mass['mode'] == 'include')
                $query->whereIn('Id', $match[1]);
            else
                $query->whereNotIn('Id', $match[1]);
        }

        return $query;
    }

    public function userExpiredFilterFunction(\Illuminate\Database\Eloquent\Builder &$query, $value) {

        if(!empty($value))
            $query->where('EndTime', '<', date('Y-m-d H:i:s'));

        return;
    }

    public function clientIdFilterFunction(\Illuminate\Database\Eloquent\Builder &$query, $value) {
        if(is_null($value))
            return;

        $query->where('ClientId', '=', $value);
        return;
    }

    public function clientOfficialNameFilterFunction(\Illuminate\Database\Eloquent\Builder &$query, $value) {

        if(is_null($value))
            return;

        if($value == '-') {
            $query->whereNull('ClientId');
            return;
        }

        $query->whereRelation('client', 'OfficialName', 'like', '%' . $value . '%');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = new User();

        $client = $request->input('ClientId') ? Client::find($request->input('ClientId')) : null;

        return view('private.user.create')
            ->with('pageTitle', 'Создание пользователя')
            ->with('user', $user)
            ->with('client', $client);
    }

    public function importExcel(Request $request)
    {
        $users = [];
        $masterUsers = [];
        $client = null;

        if ($request->isMethod('post') && $request->hasFile('import')) {

            $usersData = (new \App\Imports\UsersImport())->toArray(request()->file('import'));

            $users = User::mapExcel($usersData, $request->input('login'));

            if($request->input('clientId')) {
                $client = Client::find($request->input('clientId'));

                $masterUsers = User::orderBy('AccessArea', 'desc')
                    ->orderBy('Login', 'asc')
                    ->where('ClientId', $request->input('clientId'))->pluck('Login', 'id')->toArray();

                $masterUsers = [0=>'Не задано'] + $masterUsers;
            }
        }

        if ($request->isMethod('post') && $request->input('users')) {

            $clientId = null;
            //$masterUserId = null;

            if($request->input('clientId')) {
                $cu = Auth::user();

                $clients = Client::select("Id");
                if (!Gate::allows('use-function', 'clients_all')) {
                    $clients->where('MasterUserId', $cu->Id)
                        ->orWhere('id', $cu->ClientId);
                }
                $clientIds = $clients->get()->pluck('Id')->toArray();
                $clientIds[] = $cu->ClientId;

                if(!in_array($request->input('clientId'), $clientIds)) {
                    $request->session()->flash('error', 'Невозможно загрузить пользователей для выбранного клиента');
                    return back();
                }

                $clientId = $request->input('clientId');
            }

            $masterUserId = $request->input('masterUserId', null);
            $accessLevel = env('USER_DEFAULT_ACCESS_LEVEL_ID');

            if (Gate::allows('use-function', 'access_levels_all') && $request->input('accessLevel'))
                $accessLevel = $request->input('accessLevel');

            DB::beginTransaction();

            $usersData = $request->input('users');

            foreach ($usersData as $userData) {

                $user = new User($userData);

                $user->Name = $user->Name ?? '';
                $user->Email = $user->Email ?? '';

                $user->Password = Hash::make($userData['Password']);
                $user->ClientId = $clientId;
                $user->MasterUserId = empty($masterUserId) ? null : $masterUserId;
                $user->AccessLevel = $accessLevel;
                $user->AccessArea = 0;
                $user->save();
            }

            DB::commit();

            $request->session()->flash('status', 'Данные импортированы');

            return redirect()->route('users.index');
        }

        $al = null;
        $alArray = [];

        if (Gate::allows('use-function', 'access_levels_all')) {
            $al = Access::find($request->input('accessLevel'));
            $accessLevels = Access::orderBy('Name', 'asc');
            $alArray = $accessLevels->pluck('Name', 'Level');
        }

        return view('private.user.import_excel')
            ->with('pageTitle', 'Импорт пользователей')
            ->with('masterUsers', $masterUsers)
            ->with('accessLevels', $alArray)
            ->with('accessLevel', $al)
            ->with('client', $client)
            ->with('login', $request->input('login'))
            ->with('users', $users);
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

        $fields = array_map(function ($filed) {
            if(is_string($filed) || is_null($filed)) {
                $filed = trim($filed);
                $filed = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $filed);
            }

            return $filed;
        }, $request->all());

        $validator = Validator::make($fields, [
            'Login' => 'required|unique:SystemUsers|max:255',
            'Password' => 'required|min:5|max:16',
            //'OrgName' => 'empty',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $user = new User($fields);
        $user->Password = Hash::make($fields['Password']);

        $cu = Auth::user();

        $user->AccessLevel = env('USER_DEFAULT_ACCESS_LEVEL_ID');//$cu->AccessLevel;
        $user->AccessArea = 0;//$cu->AccessArea;

        if(!empty($fields['ClientId']) && ($fields['ClientId'] == $cu->ClientId || Gate::allows('use-function', 'clients_all')))
            $user->ClientId = $fields['ClientId'];
        else
            $user->ClientId = $cu->ClientId;
        //$user->MasterUserId = $cu->Id;

        /*$masterUser = User::orderBy('AccessArea', 'desc')
            ->orderBy('Login', 'asc')
            ->where('ClientId', $user->ClientId)
            ->first();

        if($masterUser)
            $user->MasterUserId = $masterUser->Id;*/

        $user->save();

        return redirect()
            ->route('users.edit', ['user'=>$user->Id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function journal(User $user)
    {
        $dataProvider = new GridDataProvider($user->histories()->orderBy('performed_at', 'desc')->getQuery());

        return view('private.user.journal')
            ->with('pageTitle', 'Журнал изменений пользователя')
            ->with('dataProvider', $dataProvider);
    }

    public function lastDayRequestsCount(User $user)
    {
        Gate::authorize('update-user', $user);

        $rCount = RequestNew::where('user_id', '=', $user->Id)
            ->where('created_date','>=', date('Y-m-d', time()-24*60*60))
            ->count();

        return response()->json(['requestsCount'=>$rCount]);
    }

    public function limits(User $user)
    {
        $limits = UserUsageLimits::where('UserId', $user->Id)->get();

        $limitsTypeMap = [];
        $availableLimits = [];

        foreach ($limits as $limit)
            $limitsTypeMap[$limit->PeriodType] = 1;

        foreach (UserUsageLimits::$periodTypes as $pType => $ptLabel)
            if(!isset($limitsTypeMap[$pType]))
                $availableLimits[$pType] = $ptLabel;

        return view('private.user.limits')
            ->with('pageTitle', 'Лимиты')
            ->with('limits', $limits)
            ->with('availableLimits', $availableLimits)
            ->with('user', $user);
    }

    public function parallelQueriesLimit(Request $request, User $user)
    {
        $user->ParallelQueriesLimit = empty($request->input('ParallelQueriesLimit')) ? NULL : $request->input('ParallelQueriesLimit');
        $user->save();

        return redirect()
            ->route('users.limits', ['user'=>$user->Id]);
    }

    public function addLimit(Request $request, User $user)
    {
        $fields = $request->except(['_token', '_method']);

        $fields = array_filter($fields, function ($filed) { return !is_null($filed) && (!is_string($filed) || strlen($filed)); });

        $cul = new UserUsageLimits($fields);

        $cul->UserId = $user->Id;

        $cul->save();

        return redirect()
            ->route('users.limits', ['user'=>$user->Id]);
    }

    public function removeLimit(User $user, UserUsageLimits $limit)
    {
        if($limit->UserId == $user->Id) {
            $limit->delete();
        }

        return redirect()
            ->route('users.limits', ['user'=>$user->Id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        Gate::authorize('update-user', $user);

        $alArray = [];

        if (Gate::allows('use-function', 'access_levels_all')) {
            $accessLevels = Access::orderBy('Name', 'asc');
            $alArray = $accessLevels->pluck('Name', 'Level');
        }

        $messages = Message::orderBy('id', 'desc');
        $messagesList = [0=>'Не задано'];

        foreach ($messages->pluck('Text', 'id')->toArray() as $mid => $text)
            $messagesList[$mid] = mb_substr($text, 0, 100).(mb_strlen($text) > 100 ? ' ...' : '');

        $users = User::orderBy('AccessArea', 'desc')
            ->orderBy('Login', 'asc')
            ->where('AccessArea', '>', $user->AccessArea)
            ->where('ClientId', $user->ClientId);

        $usersList = [0=>'Не задано'] + $users->pluck('Login', 'id')->toArray();

        return view('private.user.edit')
            ->with('pageTitle', 'Редактирование пользователя')
            ->with('user', $user)
            ->with('users', $usersList)
            ->with('messages', $messagesList)
            ->with('accessLevels', $alArray);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('update-user', $user);

        //$fields = array_filter($request->all(), 'strlen');
        //$fields = array_filter($request->all(), 'strlen');
        $fields = array_map(function ($filed) {
            if(is_string($filed) || is_null($filed)) {
                $filed = trim($filed);
                $filed = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $filed);
            }

            return $filed;
        }, $request->all());




        $rules = [
            'Name' => 'string|max:32',
            'Email' => 'email',
            'ContactName' => 'string|nullable',
            'ContractNum' => 'string|nullable',
            'DefaultPrice' => 'between:0,1000|nullable',
            'DefaultRequestTimeout' => 'between:0,10000|nullable',
            //'Phone' => 'regex:/^7[0-9]{10}$/|Nullable',
            //'OrgName' => 'string|nullable|max:64',
        ];

        if($fields['Login']!=$user->Login)
            $rules['Login'] = 'unique:SystemUsers|max:255';

        $validator = Validator::make($fields, $rules);

        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        if (Gate::allows('use-function', 'access_levels_all')) {
            $user->AccessLevel = $fields['AccessLevel'] ?? $user->AccessLevel;
        }

        if (Gate::allows('use-function', 'access_levels_all')) {
            $user->ResultsArea = $fields['ResultsArea'] ?? $user->ResultsArea;
        }

        if (Gate::allows('use-function', 'access_levels_all')) {
            $user->ReportsArea = $fields['ReportsArea'] ?? $user->ReportsArea;
        }

        if (Gate::allows('use-function', 'clients_own')) {

            $cu = Auth::user();

            $clientIds = Client::where('MasterUserId', $user->Id)
                ->pluck('Id')
                ->toArray();

            $clientIds[] = $cu->ClientId;

            if(Gate::allows('use-function', 'clients_all')) // Можно менять и сбрасывать
                $user->ClientId = isset($fields['ClientId']) ? $fields['ClientId'] : null;
            elseif(in_array($fields['ClientId'], $clientIds) && in_array($user->ClientId, $clientIds)) // Менять можно только в рамках допустимых пользователю
                $user->ClientId = $fields['ClientId'];
        }

        if(Auth::id() != $user->Id)
            $user->AccessArea = min($fields['AccessArea'] ?? $user->AccessArea, Auth::user()->AccessArea);

        if(isset($fields['Phones'])) {

            $originalPhonesObjects = $user->phones;
            $savedPhonesObjects = [];
            $valid = Phone::fillAndSaveList($fields['Phones'], $user->Id, 'user', $savedPhonesObjects);

            if($valid!==true)
                return back()->withErrors($valid)->withInput();

            $ch = $user->addCustomHistory(['key' => 'phones', 'new' => implode('/', Phone::compileListToStringArray($savedPhonesObjects)), 'old'=>implode('/', Phone::compileListToStringArray($originalPhonesObjects))], true);

            if($ch) {
                event('eloquent.updating: App\Models\User', $user);
                $user->clearCustomHistory();
            }

        }

        $user->fill($fields);

        if(Gate::allows('use-function', 'users_clients')) {

            $user->MessageId = empty($fields['MessageId']) ? null : $fields['MessageId'];
            $user->MasterUserId = empty($fields['MasterUserId']) ? null : $fields['MasterUserId'];
            $user->AllowedIP = empty($fields['AllowedIP']) ? null : $fields['AllowedIP'];

            $user->StartTime = empty($fields['StartTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['StartTime']));
            $user->EndTime = empty($fields['EndTime']) ? null : date('Y-m-d H:i:s', strtotime($fields['EndTime']));

            $user->DefaultPrice = empty($fields['DefaultPrice']) && $fields['DefaultPrice']!=='0' ? null : $fields['DefaultPrice'];
            $user->DefaultRequestTimeout = empty($fields['DefaultRequestTimeout']) ? null : $fields['DefaultRequestTimeout'];
            //event('eloquent.updating: App\Models\User', $user);
        }

        $user->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('users.edit', ['user'=>$user->Id]);
    }



    /**
     * Set user password
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function password(Request $request, User $user)
    {
        Gate::authorize('update-user', $user);

        $fields = $request->all();

        $validator = Validator::make($fields, [
            'Password' => 'required|min:5|max:16',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();

        $user->Password = Hash::make($fields['Password']);
        $user->save();

        $request->session()->flash('status', 'Пароль обновлён');

        return redirect()->route('users.edit', ['user'=>$user->Id]);
    }

    /**
     * Set user password
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function passwordGenerate(Request $request, User $user)
    {
        Gate::authorize('update-user', $user);

        $password = User::generatePassword();

        if(strlen($password) >=5 && strlen($password) <=16) {
            $user->Password = Hash::make($password);
            $user->save();

            $request->session()->flash('status', 'Пароль обновлён. Новое значение: '.$password);
        }
        else
            $request->session()->flash('error', 'Ошибка обновления пароля');

        return redirect()->route('users.edit', ['user'=>$user->Id]);
    }

    /**
     * Set user password
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massPasswordGenerate(Request $request)
    {
        $query = $this->getMassSelectQuery($request);

        if(!$query->count() || $query->count() > 30) {
            $request->session()->flash('error', 'Для задания паролей выберите от 1 до 30 пользователей');
            return back();
        }

        $users = $query->get();

        if ($request->isMethod('post')) {


            $passwords = $request->input('passwords');

            DB::beginTransaction();

            foreach ($users as $user) {

                $password = $passwords[$user->Id] ?? null;

                if($password && strlen($password) >=5 && strlen($password) <=16) {
                    $user->Password = Hash::make($password);
                    $user->save();
                }
            }

            DB::commit();

            if($request->input('return') == 'edit') {
                $request->session()->flash('status', 'Пароль задан');

                return redirect()->route('users.edit', ['user'=>$users[0]->Id]);
            }

            $request->session()->flash('status', 'Новые пароли заданы');

            return redirect()->route('users.index');
        }


        return view('private.user.mass_password_generate')
            ->with('pageTitle', 'Задание паролей')
            ->with('users', $users);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        Gate::authorize('update-user', $user);
    }
}
