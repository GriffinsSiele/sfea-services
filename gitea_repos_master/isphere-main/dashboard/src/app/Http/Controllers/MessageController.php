<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Message;
use App\Models\Client;
use App\Models\User;

use Itstructure\GridView\DataProviders\EloquentDataProvider;

class MessageController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $messages = Message::orderBy('id', 'desc');

        $dataProvider = new EloquentDataProvider($messages);

        return view('private.message.list')
            ->with('pageTitle', 'Управление объявлениями')
            ->with('dataProvider', $dataProvider);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $message = new Message();

        return view('private.message.create')
            ->with('pageTitle', 'Создание объявления')
            ->with('message', $message);
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
            'Text' => 'required|min:1',
        ]);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $message = new Message($fields);
        //$message->Level = $fields['Level'];

        $message->save();

        return redirect()
            ->route('messages.edit', ['message'=>$message->id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Message $message)
    {
        Gate::authorize('update-message', $message);

        $query = null;

        if($request->input('list', 'users') == 'users')
            $query = User::where('MessageId', '=', $message->id);
        else
            $query = Client::where('MessageId', '=', $message->id);

        $dataProvider = new \App\DataGrid\GridDataProvider($query);

        return view('private.message.edit')
            ->with('pageTitle', 'Редактирование доступа')
            ->with('dataProvider', $dataProvider)
            ->with('message', $message);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Message $message)
    {
        Gate::authorize('update-message', $message);

        $fields = array_filter($request->all(), 'strlen');

        $rules = [
            'Text' => 'required|min:1',
        ];

        $validator = Validator::make($fields, $rules);

        if ($validator->fails())
            return
                back()
                    ->withErrors($validator)
                    ->withInput();


        $message->fill($fields);
        $message->save();

        $request->session()->flash('status', 'Успешно обновлено');

        return redirect()->route('messages.edit', ['message'=>$message->id]);
    }
    
    public function removeUser(Request $request, Message $message, User $user) {
        if(!Gate::authorize('update-message', $message))
            return back();

        if($user->MessageId == $message->id) {
            $user->MessageId = NULL;
            $user->save();
        }

        return back();
    }

    public function removeClient(Request $request, Message $message, Client $client) {
        if(!Gate::authorize('update-message', $message))
            return back();

        if($client->MessageId == $message->id) {
            $client->MessageId = NULL;
            $client->save();
        }

        return back();
    }

    public function remove(Request $request, Message $message)
    {
        if(Gate::authorize('update-message', $message))
            $message->delete();

        return back();
    }
}
