@extends('layouts.app-private')

@section('content')

    <a href="{{ route('proxies.create') }}">Добавить прокси</a>
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
                    'label' => 'Адрес',
                    'value' => function ($row) {
                        return $row->server.':'.$row->port;
                    },
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'server'
                    ],
                ],
                [
                    'format' => 'html',
                    'filter' => false,
                    'label' => 'Параметры',
                    'value' => function ($row) {
                        return
                            'status: '.$row->status.'<br/>'
                            .'starttime: '.$row->starttime.'<br/>'
                            .'successtime: '.$row->successtime.'<br/>'
                            .'endtime: '.$row->endtime.'<br/>'
                            .'used: '.$row->used.'<br/>'
                            .'success: '.$row->success;
                    },
                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('proxies.edit', ['proxy' => $row->id]);
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
