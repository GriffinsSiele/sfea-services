@extends('layouts.app-private')

@section('content')
    <div class="row">
    @can('use-function','clients_own')
        <div class="col-3">
            <a href="{{ route('clients.create') }}">Создать клиента</a>
        </div>
    @endcan

    </div>

    <br/>

    <div class="row">
        <div class="col-3">
            <a href="#" class="grid-view-excel-download grid-view-mass-check btn btn-secondary btn-sm" data-uri="{{ route('clients.download.excel') }}"><i class="bi bi-download"></i> Excel</a>
        </div>
    </div>

    <br/>

    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            //'title' => 'Клиенты',
            'tableBordered' => false,
            'strictFilters' => ['Status'],
            'useFilters' => true,
            'useResetButton' => false,
            'additionalFiltersTpl' => 'private.client.filter',
            'columnFields' => [
                /*[
                    'sort' => 'id',
                    'label' => 'ИД',
                    'filter' => false,
                    'htmlAttributes' => [
                        'width' => '30px'
                    ],
                    'value' => function ($row) {
                        return $row->id;
                    }

                ],*/
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\CheckboxColumn::class,
                    'attribute'=> 'id',
                ],
                [
                    'sort' => 'Code',
                    'label' => 'Код',
                    'filter' => false,
                    'htmlAttributes' => [
                        'width' => '120px'
                    ],
                    'value' => function ($row) {
                        return $row->Code;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'Code'
                    ],

                ],
                [
                    'attribute' => 'OfficialName',
                    'sort' => 'OfficialName',
                    'label' => 'Юридическое название',
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'OfficialName'
                    ],
                ],
                [
                    'attribute' => 'Status',
                    'sort' => 'Status',
                    'label' => 'Статус',
                    'value' => function ($row) {
                        return \App\Models\Client::$statusMap[$row->Status] ?? '-';
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => \App\Models\Client::$statusMap
                    ],
                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('clients.edit', ['client' => $row->id]);
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


@endsection
