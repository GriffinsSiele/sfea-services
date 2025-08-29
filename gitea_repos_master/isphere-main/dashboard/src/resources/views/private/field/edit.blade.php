@extends('layouts.app-private')

@section('content')
    @php
        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 50,
            'tableHover' => false,
            'strictFilters' => ['status'],
            'useFilters' => false,
            'tableBordered' => false,
            'useResetButton' => false,
            'columnFields' => [
                [
                    'attribute' => 'name',
                    'sort' => 'name',
                    'label' => 'Код / Тип',
                    'format' => 'html',
                    'value' => function ($row) {
                         $ret = '<b>'.$row->name.'</b><br/><select name="type" class="form-select" value="'.$row->type.'"  data-id="'.$row->id.'">';

                         foreach (\App\Models\Field::$typesList as $type)
                            $ret .= '<option '.($type == $row->type ? 'selected' : '').'>'.$type.'</option>';


                         return $ret;
                    },
                ],
                [
                    'attribute' => 'title',
                    'sort' => 'title',
                    'label' => 'Название',
                    'format' => 'html',
                    'value' => function ($row) {
                        return '<br/><input type="text" name="title" class="form-control" value="'.$row->title.'" data-id="'.$row->id.'">';
                    },
                ],

                [
                    'sort' => 'description',
                    'label' => 'Описание',
                    'format' => 'html',
                    'value' => function ($row) {
                        return '<br/><input type="text" name="description" class="form-control" value="'.$row->description.'" data-id="'.$row->id.'">';
                    },
                ],
                [
                    'label' => '',
                    'filter' => false,
                    'format' => 'html',
                    'value' => function ($row) {
                        $form = '<br/><button class="btn bnt-sm btn-primary save-bnt" type="button" data-id="'.$row->id.'">Сохранить</button>';
                        return $form;

                        //return \App\Models\CheckType::$statusMap[$row->status] ?? '-';
                    },
                    'htmlAttributes' => [
                        'width' => '70px',
                    ],
                ],

            ]
        ];
    @endphp

    @gridView($gridData)

    <script type="text/javascript">

        $('.save-bnt').on('click', function(  ) {
            let data = {};

            $(this).closest("tr").find("input[name], select[name]").each(function() {
                data[this.name] = $(this).val();
            });

            console.log(data);

            $.ajax({
                url: "/private/fields/"+ $(this).data('id'),
                type: 'PUT',
                data: data,
                error : function( data ) {
                    alert('Ошибка');
                },
                success : function( data ) {
                    alert('Сохранено');
                }
            });
        });


    </script>


@endsection
