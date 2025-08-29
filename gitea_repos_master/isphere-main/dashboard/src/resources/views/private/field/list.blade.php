@extends('layouts.app-private')

@section('content')

    <div class="row">
        <div class="col-3">
            <a href="#" class="grid-view-excel-download grid-view-mass-check btn btn-secondary btn-sm" data-uri="{{ route('fields.download.excel') }}"><i class="bi bi-download"></i> Excel</a>
        </div>
    </div>

    <br/>

    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            'tableHover' => false,
            'strictFilters' => ['status'],
            'useFilters' => false,
            'tableBordered' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\CheckboxColumn::class,
                    'attribute'=> 'source_name',
                ],
                [
                    'attribute' => 'source_name',
                    'sort' => 'source_name',
                    'label' => 'Код',
                    'filter' => [
                        'class' => Itstructure\GridView\Filters\TextFilter::class,
                        'name' => 'source_name'
                    ],
                ],
                [
                    'label' => '',
                    'class' => Itstructure\GridView\Columns\ActionColumn::class,
                    'actionTypes' => [
                        'edit' => function ($row) {
                            return route('fields.edit', ['source' => $row->source_name]);
                        },
                    ],
                    'htmlAttributes' => [
                        'width' => '60px',
                    ],
                ],

            ]
        ];
    @endphp

    @gridView($gridData)

    <script type="text/javascript">
        $('.grid-view-excel-download').on('click', function () {
            let url = $(this).data('uri') + window.location.search;
            window.open(url,"_blank");
        });
    </script>

@endsection
