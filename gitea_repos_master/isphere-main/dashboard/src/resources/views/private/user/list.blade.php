@extends('layouts.app-private')

@section('content')

    <div class="row">
    @can('use-function','users_own')
        <div class="col-6">
        <a href="{{ route('users.create') }}">Создать пользователя</a>
            |
        <a href="{{ route('users.import.excel') }}">Импортировать</a>
        </div>
    @endcan

    </div>

    <br/>

    <div class="row">
        <div class="col-12">
            <button class="grid-view-excel-download grid-view-mass-check btn btn-secondary btn-sm" data-uri="{{ route('users.download.excel') }}"><i class="bi bi-download"></i> Excel</button>

            <button class="grid-view-mass-check btn btn-secondary btn-sm" data-uri="{{ route('users.mass.password.generate') }}">Задать пароли</button>
        </div>
    </div>

    <br/>

    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            //'title' => 'Клиенты',
            'tableBordered' => false,
            'useFilters' => true,
            'additionalFiltersTpl' => 'private.user.filter',
            'strictFilters' => ['AccessLevel', 'AccessArea', 'Locked'],
            'useResetButton' => false,
            'columnFields' => [
                /*[
                    'sort' => 'Id',
                    'label' => 'ИД',
                    'filter' => false,
                    'htmlAttributes' => [
                        'width' => '30px'
                    ],
                    'value' => function ($row) {
                        return $row->Id;
                    }

                ],*/
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\CheckboxColumn::class,
                    'attribute'=> 'Id',
                ],
                [
                    'sort' => 'Login',
                    'label' => 'Логин',
                    'htmlAttributes' => [
                        'width' => '100px'
                    ],
                    'value' => function ($row) {
                        return $row->Login;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'Login'
                    ],

                ],
                [
                    'attribute' => 'Client_OfficialName',
                    'sort' => false,
                    'label' => 'Клиент *',
                    'value' => function ($row) {
                        return $row->client ? (empty($row->client->OfficialName) ? $row->client->Name : $row->client->OfficialName) : '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'Client_OfficialName'
                    ],
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
                    'attribute' => 'AccessLevel',
                    'sort' => 'AccessLevel',
                    'label' => 'Тип доступа',
                    'value' => function ($row) use ($accessLevels) {
                        return $accessLevels[$row->AccessLevel] ?? '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => $accessLevels
                    ],
                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('users.edit', ['user' => $row->Id]);
                        },
                    ],
                    'htmlAttributes' => [
                        'width' => '60px',
                    ],
                ]
            ]
        ];
    @endphp

    @gridViewExt($gridData)

    <br/>
    <i>*Клиент: "-" - Без договора</i>

    <script type="text/javascript">
        $('.grid-view-excel-download').on('click', function () {
            let url = $(this).data('uri') + window.location.search;
            window.open(url,"_blank");
        });
    </script>


@endsection
