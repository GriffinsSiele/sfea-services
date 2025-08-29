@extends('layouts.app-private')

@section('content')

    <div class="row">
        <div class="col-3">
            <a href="{{ route('clients.journal', $client->id) }}">Журнал изменений</a>
        </div>

        <div class="col-3">
            <a href="{{ route('clients.limits', $client->id) }}">Лимиты</a>
        </div>
    </div>

    <br/>


    {{ Html::ul($errors->all()) }}

    {{ Form::model($client, array('route' => array('clients.update', $client->id), 'method' => 'PUT')) }}

    @include('private/client/form_common', ['client'=>$client, 'users'=>$users])

    @can('use-function','messages')
        <div class="row mt-2">
            <div class="col-12 col-lg-6">
                <hr/>
                {{ Form::label('MessageId', 'Объявление') }}
                {{ Form::select('MessageId', $messages, $client->MessageId, ['class' => 'form-select']) }}
            </div>
        </div>
    @endcan

    <div class="row">
        <div class="col-2 offset-9 offset-lg-4 d-grid pt-3">
        {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            //'title' => 'Клиенты',
            'tableBordered' => false,
            'useFilters' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'sort' => 'Login',
                    'label' => 'Логин',
                    'value' => function ($row) {
                        return $row->Login;
                    },
                ],
                [
                    'attribute' => 'Locked',
                    'sort' => 'Locked',
                    'label' => 'Заблокирован',
                    'value' => function ($row) {
                        return \App\Models\User::$lockedMap[$row->Locked] ?? '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => \App\Models\User::$lockedMap
                    ],
                ],
                [
                    'attribute' => 'AccessArea',
                    'sort' => 'AccessArea',
                    'label' => 'Зона видимости',
                    'value' => function ($row) {
                        return \App\Models\User::$accessAreaMap[$row->AccessArea] ?? '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => \App\Models\User::$accessAreaMap
                    ],
                ],
                [
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'label' => '',
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('users.edit', ['user' => $row->Id]);
                        },
                    ],
                    'htmlAttributes' => [
                        'width' => '130px',
                    ],
                ]
            ]
        ];
    @endphp

    <div class="row">
        <div class="col"><br/><hr/></div>
    </div>


    @can('use-function','users_own')
    <div class="row mb-3">

        <div class="col-4">
            <a href="{{ route('users.create', ['ClientId'=>$client->id]) }}" class="btn btn-secondary btn-sm">Создать пользователя</a>

            <a href="{{ route('users.download.excel', ['filters'=>['ClientId'=>$client->id]]) }}" class="btn btn-secondary btn-sm"><i class="bi bi-download"></i> Excel</a>
        </div>

    </div>

    @endcan

    @gridView($gridData)

@endsection
