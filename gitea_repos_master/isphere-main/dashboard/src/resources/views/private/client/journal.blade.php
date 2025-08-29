@extends('layouts.app-private')

@section('content')

    @php
        use App\Models\Client;

        $gridData = [
            'dataProvider' => $dataProvider,
            'rowsPerPage' => 30,
            //'title' => 'Клиенты',
            'tableBordered' => false,
            'useFilters' => false,
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
                    'label' => 'Дата события',
                    'attribute'=> 'performed_at',
                ],
                [
                    'value' => function ($row) {
                    //var_dump(get_class($row));
                    //var_dump(get_object_vars ($row));
                    //var_dump($row->user);

                        return $row->user_id ? $row->user()->Name.' ('.$row->user()->Login.')' : '-';
                        //return $row->message;
                    },
                ],
                [
                    'format' => 'html',
                    'value' => function ($row) use ($valuesMap) {

                        $ret = '';
                        foreach ($row->meta as $hRecord) {

                            if(isset(Client::$fieldsLabels[$hRecord['key']])) {

                                //if($hRecord['key'] == 'TariffId')

                                $ret .= '<b>'.Client::$fieldsLabels[$hRecord['key']].'</b>: ';

                                if(isset($hRecord['old']))
                                    $ret .= ($valuesMap[$hRecord['key']][(string)$hRecord['old']] ?? $hRecord['old'] ?? '<i>пусто</i>').' -> ';

                                $ret .= $valuesMap[$hRecord['key']][(string)$hRecord['new']] ?? $hRecord['new'] ?? '<i>пусто</i>';

                                $ret .= '<br/>';
                            }
                        }



                        return $ret;
                    },
                ],
            ]
        ];
    @endphp

    @gridView($gridData)


@endsection
