@extends('layouts.app-private')

@section('css-includes')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js-includes')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
@endsection

@section('content')

    <div class="row">
        <div class="col-3">
            <a href="{{ route('users.journal', $user->Id) }}">Журнал изменений</a>
        </div>

        <div class="col-3">
            <a href="{{ route('users.limits', $user->Id) }}">Лимиты</a>
        </div>
    </div>

    <br/>

    {{ Html::ul($errors->all()) }}

    {{ Form::model($user, array('route' => array('users.update', $user->Id), 'method' => 'PUT')) }}

    <div class="row">
        <div class="col-12 col-lg-6">
        {{ Form::label('Login', 'Логин') }}
        {{ Form::text('Login', null, ['class' => 'form-control']) }}
        </div>

    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Name', 'Имя') }}
            {{ Form::text('Name', null, ['class' => 'form-control']) }}
        </div>
    </div>

    @php
        $phones = [];
        for($count = 0; $count<4; $count++)
            $phones[] = $user->phones[$count] ?? new \App\Models\Phone();
    @endphp

    @foreach ($phones as $ind => $phone)
        <div class="row phone-row">
            <div class="col-24">
                {{ Form::label(null, 'Телефон') }}
            </div>

            <div class="col-12 col-lg-6">
                <div class="input-group">
                    <input class="form-control phone-number" name="Phones[{{$ind}}][Number]" type="text" placeholder="Номер" value="{{$phone->Number}}">
                    <span class="input-group-btn" style="width:0px;"></span>
                    <input class="form-control" name="Phones[{{$ind}}][InnerCode]" type="text" placeholder="Добавочный" value="{{$phone->InnerCode}}">

                    <input type="hidden" name="Phones[{{$ind}}][Id]" value="{{$phone->Id}}">
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <input class="form-control" name="Phones[{{$ind}}][Notice]" type="text" placeholder="Примечание"  value="{{$phone->Notice}}">
            </div>
        </div>
    @endforeach

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Email', 'Почта') }}
            {{ Form::Email('Email', null, ['class' => 'form-control']) }}
        </div>
    </div>

    @can('use-function','users_clients')
    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('AllowedIP', 'Разрешённый IP') }}
            {{ Form::text('AllowedIP', null, ['class' => 'form-control']) }}
        </div>
    </div>
    @endcan

    @can('use-function','users_clients')
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('DefaultPrice', 'Стоимость запроса (вместо тарифной)') }}
                {{ Form::text('DefaultPrice', null, ['class' => 'form-control']) }}
            </div>
        </div>
    @endcan

    @can('use-function','users_clients')
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('DefaultRequestTimeout', 'Таймаут запроса (по умолчанию)') }}
                {{ Form::text('DefaultRequestTimeout', null, ['class' => 'form-control']) }}
            </div>
        </div>
    @endcan

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('Locked',0) }}
            {{ Form::checkbox('Locked', 1, null, ['class' => 'form-check-input user-status', 'data-user-id'=>$user->Id]) }}
            {{ Form::label('Locked', 'Заблокирован', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('OrgName', 'Организация') }}
            {{ Form::text('OrgName', null, ['class' => 'form-control']) }}
        </div>
    </div>



    @can('use-function','access_levels_all')
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('ReportsArea', 'Видимость статистики ') }}
                {{ Form::select('ReportsArea', array_filter(\App\Models\User::$reportsAreaMap, function ($key) {
                    return $key <= is_null(Auth::user()->ReportsArea) ? Auth::user()->AccessArea : Auth::user()->ReportsArea;
                }, ARRAY_FILTER_USE_KEY), is_null($user->ReportsArea) ? $user->AccessArea : $user->ReportsArea, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan

    @can('use-function','access_levels_all')
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('ResultsArea', 'Просмотр отчетов по ссылке') }}
                {{ Form::select('ResultsArea', array_filter(\App\Models\User::$resultsAreaMap, function ($key) {
                    return $key <= is_null(Auth::user()->ResultsArea) ? Auth::user()->AccessArea : Auth::user()->ResultsArea;
                }, ARRAY_FILTER_USE_KEY), is_null($user->ResultsArea) ? $user->AccessArea : $user->ResultsArea, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan


    @can('use-function','access_levels_all')
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('AccessArea', 'История запросов') }}
                {{ Form::select('AccessArea', array_filter(\App\Models\User::$accessAreaMap, function ($key) {
                    return $key <= Auth::user()->AccessArea;
                }, ARRAY_FILTER_USE_KEY), $user->AccessArea, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan

    @if (count($accessLevels))
        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('AccessLevel', 'Тип доступа') }}
                {{ Form::select('AccessLevel', $accessLevels, $user->AccessLevel, ['class' => 'form-select']) }}
            </div>
        </div>
    @endif

    @can('use-function','clients_own')
        <div class="row mt-2">
            <div class="col-12 col-lg-6">
                <hr/>
                {{ Form::label('ClientId', 'Клиент') }}
                {{ Form::select('ClientId', ($user->client ? [$user->client->id => $user->client->OfficialName] : []), ($user->client ? $user->client->id : null), ['class' => 'form-select', 'id'=>'clientSearch']) }}
                @if ($user->client)
                <small><a href="{{ route('clients.edit', ['client'=>$user->client->id]) }}">{{ $user->client->OfficialName }} [{{ $user->client->Code }}]</a></small>
                @endif
            </div>
        </div>
    @endcan



    @can('use-function','users_clients')
        <div class="row">
            <div class="col-12 col-lg-6">

                <br/>
                {{ Form::label('MasterUserId', 'Основной пользователь') }}
                {{ Form::select('MasterUserId', $users, $user->MasterUserId, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan

    @can('use-function','messages')
        <div class="row mt-2">
            <div class="col-12 col-lg-6">
                <hr/>
                {{ Form::label('MessageId', 'Объявление') }}
                {{ Form::select('MessageId', $messages, $user->MessageId, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan

    @can('use-function','clients_own')
        <div class="row">
            <div class="col-12 col-lg-6">
                <hr/>
                {{ Form::label('StartTime', 'Дата/время начала доступа') }}
                {{ Form::datetimeLocal('StartTime', null, ['class' => 'form-control']) }}
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-6">
                {{ Form::label('EndTime', 'Дата/время окончания доступа') }}
                {{ Form::datetimeLocal('EndTime', null, ['class' => 'form-control']) }}
            </div>
        </div>
    @endcan

    <div class="row mb-4">
        <div class="col-2 offset-9 offset-lg-4 d-grid pt-3">
        {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

    {{ Form::model($user, array('route' => array('users.password', $user->Id), 'method' => 'POST')) }}
        <div class="row mt-4">
            <div class="col-12 col-lg-12"><br/><br/><hr/></div>
        </div>
        <div class="row">

            <div class="col-4">

                {{ Form::label('Password', 'Новый пароль') }}
                {{ Form::password('Password', array('class' => 'form-control')) }}
            </div>
            <div class="col-2 d-grid">
                {{ Form::submit('Задать', array('class' => 'btn btn-secondary mt-4')) }}
            </div>
        </div>
    {{ Form::close() }}

    <div class="row mt-4">
        <div class="col-6 d-grid">
            <hr/>
            {{-- <a href="{{ route('users.password.generate', ['user'=>$user->Id]) }}" class="btn btn-primary">Сгенерировать новый пароль</a> --}}
            <a href="{{ route('users.mass.password.generate', ['mass'=>['mode'=>'include', 'ids'=>$user->Id], 'return'=>'edit']) }}" class="btn btn-secondary">Сгенерировать новый пароль</a>
        </div>
    </div>

    @can('use-function','clients_own')
        <script type="text/javascript">

            function hideEmptyPhones() {
                $('.phone-row').each(function( index ) {
                    if($(this).find('.phone-number').val() == '')
                        $(this).hide();
                });

                $('.phone-row:hidden').first().show();
            }

            hideEmptyPhones();

            $('.phone-number').change(hideEmptyPhones);

            $('.user-status').change(function() {

                let userId = $(this).data('user-id');
                if(!userId)
                    return;

                if ($(this).is(':checked')) {
                    $('.user-status').attr('disabled', 'disabled');

                    $.ajax({
                        url: "/private/users/" + userId + "/last-day-requests-count",
                        dataType: 'json',
                        success : function( data ) {
                            $('.user-status').removeAttr('disabled');
                            if (data.requestsCount && !confirm('От пользователя поступило ' + data.requestsCount + ' запросов за вчера и сегодня, после изменения статуса новые запросы будут невозможны. Изменить?')) {
                                $('.user-status').prop('checked', false);
                                return false;
                            }
                        }
                    });
                }

                $.data(this, 'current', $(this).val());
            });

            $('#clientSearch').select2({
                placeholder: 'Укажите клиента',
                language: "ru",
                allowClear: true,
                language: {
                    noResults: function(){
                        return "Нет результатов";
                    },
                    searching: function () {
                        return 'Поиск';
                    },
                },
                ajax: {
                    url: '/private/clients/select-search',
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.OfficialName,
                                    id: item.Id
                                }
                            })
                        };
                    },
                    cache: true
                }
            });

            $('.select2-container').addClass('form-select');
            $('b[role="presentation"]').hide();

        </script>
    @endcan

@endsection
