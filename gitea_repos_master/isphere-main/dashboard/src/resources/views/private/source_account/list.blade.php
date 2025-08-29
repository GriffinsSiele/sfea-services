@extends('layouts.app-private')

@section('content')

    <a href="{{ route('source-accounts.create') }}">Создать аккаунт</a>
    <br/>
    <br/>


    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            'useFilters' => true,
            'strictFilters' => ['status', 'sourceid'],
            'tableBordered' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'attribute' => 'sourceid',
                    'sort' => false,
                    'label' => 'Источник',
                    'value' => function ($row) {
                        return $row->source->name.' ('.$row->source->code.')';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => $sources
                    ],
                ],
                [
                    'attribute' => 'status',
                    'sort' => 'status',
                    'label' => 'Статус',
                    'value' => function ($row) {
                        return \App\Models\SourceAccount::$statusMap[$row->status] ?? '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => \App\Models\SourceAccount::$statusMap
                    ],
                ],
                [
                    'sort' => false,
                    'label' => 'Логин',
                    'filter' => false,
                    'value' => function ($row) {
                        return $row->login;
                    }

                ],
                /*
                [
                    'sort' => false,
                    'label' => 'Пароль',
                    'filter' => false,

                    'value' => function ($row) {
                        return $row->password;
                    }

                ],
                */

                [
                    'label' => 'Статус сессии',
                    'filter' => false,
                    'value' => function ($row) {
                        $ls = $row->lastSession;
                        return  $ls ? (\App\Models\Session::$statusMap[$ls->sessionstatusid] ?? $ls->sessionstatusid) : '-';
                    },

                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('source-accounts.edit', ['source_account' => $row->sourceaccessid]);
                        },

                        [
                            'class' => Itstructure\GridView\Actions\Delete::class,
                            'url' => function ($row) {
                                return $row->status != 0 ? NULL : route('source-accounts.remove', ['source_account' => $row->sourceaccessid]);
                            },
                            'htmlAttributes' => [
                                'onclick' => 'return window.confirm("Вы уверены, что хотите удалить запись?");'
                            ]
                        ],
                   ],
                    'htmlAttributes' => [
                        'width' => '130px'
                    ],
                ],

            ]
        ];
    @endphp

    @gridView($gridData)


@endsection
