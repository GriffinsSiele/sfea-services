@extends('layouts.app-private')

@section('content')

    <a href="{{ route('sources.create') }}">Добавить источник</a>
    <br/>
    <br/>


    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            'tableBordered' => false,
            'useFilters' => true,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'label' => 'Название',
                    'value' => function ($row) {
                        return $row->name;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'name'
                    ],
                ],
                [
                    'label' => 'Код',
                    'value' => function ($row) {
                        return $row->code;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'code'
                    ],
                ],
                [
                    'format' => 'html',
                    'filter' => false,
                    'label' => 'Параметры',
                    'value' => function ($row) {
                        return
                            'status: '.$row->status.'<br/>'
                            .'enabled: '.$row->enabled.'<br/>';
                    },
                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('sources.edit', ['source' => $row->id]);
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
