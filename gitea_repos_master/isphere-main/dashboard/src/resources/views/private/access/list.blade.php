@extends('layouts.app-private')

@section('content')

    @can('use-function','clients_own')
        <a href="{{ route('accesses.create') }}">Создать доступ</a>
        <br/>
        <br/>
    @endcan

    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            //'title' => 'Клиенты',
            'tableBordered' => false,
            'useFilters' => true,
            'useResetButton' => false,
            'columnFields' => [
                /*[
                    'sort' => 'Level',
                    'label' => 'ИД',
                    'filter' => false,
                    'htmlAttributes' => [
                        'width' => '30px'
                    ],
                    'value' => function ($row) {
                        return $row->Level;
                    }

                ],*/
                [
                    'sort' => 'Name',
                    'label' => 'Название',
                    'htmlAttributes' => [
                        'width' => '100px'
                    ],
                    'value' => function ($row) {
                        return $row->Name;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'Name'
                    ],

                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('accesses.edit', ['access' => $row->Level]);
                        },
                    ],
                    'htmlAttributes' => [
                        'width' => '60px',
                    ],
                ]
            ]
        ];
    @endphp

    @gridView($gridData)


@endsection
