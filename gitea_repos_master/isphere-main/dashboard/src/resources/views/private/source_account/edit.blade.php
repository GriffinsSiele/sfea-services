@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($sa, array('route' => array('source-accounts.update', $sa->sourceaccessid), 'method' => 'PUT')) }}

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            <b>Источник:</b> @if ($sa->source){{ $sa->source->name }}@else - @endif<br/>
            <b>Клиент:</b> @if ($sa->client){{ $sa->client->Name }}@else - @endif<br/>
            <b>Время последней попытки входа:</b> {{ $sa->lasttime }}<br/>
            <b>Текущая сессия:</b> @if ($sa->lastSession){{ $sa->lastSession->id }} ({{ $sa->lastSession->lasttime }})@else - @endif<br/>

            <hr/>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('sourceid', 'Источник') }}
            {{ Form::select('sourceid', $sources, $sa->sourceid, ['class' => 'form-select']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('login', 'Логин') }}
            {{ Form::text('login', $sa->login, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('password', 'Пароль') }}
            {{ Form::text('password', $sa->password, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('note', 'Примечание') }}
            {{ Form::text('note', $sa->note, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6">
            {{ Form::label('status', 'Статус') }}
            {{ Form::select('status', \App\Models\SourceAccount::$statusMap, $sa->status, ['class' => 'form-select']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6">
            {{ Form::label('unlocktime', 'Время следующей попытки входа') }}
            {{ Form::datetimeLocal('unlocktime', $sa->unlocktime, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
