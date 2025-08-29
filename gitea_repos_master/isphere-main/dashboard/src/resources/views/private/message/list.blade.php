@extends('layouts.app-private')

@section('content')

    <a href="{{ route('messages.create') }}">Создать объявление</a>
    <br/>
    <br/>


    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            'useFilters' => false,
            'tableBordered' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'sort' => false,
                    'label' => 'Текст',
                    'filter' => false,

                    'value' => function ($row) {
                        return $row->Text;
                    }

                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [

                        'edit' => function ($row) {
                            return route('messages.edit', ['message' => $row->id]);
                        },

                        [
                            'class' => Itstructure\GridView\Actions\Delete::class,
                            'url' => function ($row) {
                                return route('messages.remove', ['message' => $row->id]);
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
