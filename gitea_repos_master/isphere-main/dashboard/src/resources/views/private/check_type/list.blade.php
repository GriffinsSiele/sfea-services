@extends('layouts.app-private')

@section('content')

    <div class="row">
    @can('use-function','clients_own')
        <div class="col-3">
            <a href="{{ route('check-types.create') }}">Создать проверку</a>
        </div>
    @endcan

    </div>

    <br/>


    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            'tableHover' => false,
            'strictFilters' => ['status'],
            'useFilters' => true,
            'tableBordered' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'sort' => false,
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'code'
                    ],
                    'label' => 'Код (Название)',
                    'format' => 'html',

                    'value' => function ($row) {
                        return '<b>'.$row->code.'</b> (<small>'.$row->title.'</small>)';
                    },
                ],
                [
                    'label' => 'Статус',
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\DropdownFilter::class,
                        'data' => \App\Models\CheckType::$statusMap,
                    ],
                    'attribute' => 'status',
                    'format' => 'html',
                    'value' => function ($row) {

                        $form = Form::select('status', \App\Models\CheckType::$statusMap, $row->status, ['class' => 'form-select ct-status-select', 'data-id'=>$row->id]);

                        return $form;

                        //return \App\Models\CheckType::$statusMap[$row->status] ?? '-';
                    },
                    'htmlAttributes' => [
                        'width' => '150px',
                    ],
                ],

                [
                    'label' => '',
                    'filter' => false,
                    'format' => 'html',
                    'value' => function ($row) {
                        $form = ' <button class="btn bnt-sm btn-primary ct-status-bnt" type="button" disabled data-id="'.$row->id.'">Сохранить</button>';
                        return $form;

                        //return \App\Models\CheckType::$statusMap[$row->status] ?? '-';
                    },
                    'htmlAttributes' => [
                        'width' => '70px',
                    ],
                ],

                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('check-types.edit', ['check_type' => $row->id]);
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

    <script type="text/javascript">

        $('.ct-status-select').on('change', function(  ) {
            $('.ct-status-bnt[data-id=' + $(this).data('id') + ']').removeAttr('disabled');
        });

        $('.ct-status-bnt').on('click', function(  ) {
            var btn = $(this);

            $.ajax({
                url: "/private/check-types/"+ $(this).data('id') + '/update-status',
                type: 'PUT',
                data: {
                    'status' : $('.ct-status-select[data-id=' + $(this).data('id') + ']').val(),
                },
                error : function( data ) {
                    alert('Ошибка');
                },
                success : function( data ) {
                    btn.attr('disabled', '1');
                }
            });
        });


    </script>


@endsection
