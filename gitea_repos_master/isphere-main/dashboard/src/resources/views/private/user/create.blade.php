@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($user, array('route' => array('users.store'), 'method' => 'POST')) }}

    <div class="row">
        <div class="col-12 col-lg-6">
        {{ Form::label('Login', 'Логин') }}
        {{ Form::text('Login', null, array('class' => 'form-control')) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Password', 'Пароль') }}
            {{ Form::password('Password', array('class' => 'form-control')) }}
        </div>
    </div>

    @if ($client)
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('Client', 'Клиент') }}

                {{ Form::text('Login', $client->OfficialName.' ('.$client->Code.')', array('class' => 'form-control', 'disabled')) }}

                <input type="hidden" name="ClientId" value="{{$client->id}}">
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
