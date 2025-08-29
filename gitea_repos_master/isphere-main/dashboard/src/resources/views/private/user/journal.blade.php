@extends('layouts.app-private')

@section('content')

    @php
        use App\Models\User;

        function specifyValueIfNeed($key, $value) {

            $valuesMap['AccessArea'] = User::$accessAreaMap;
            $valuesMap['ResultsArea'] = User::$resultsAreaMap;
            $valuesMap['ReportsArea'] = User::$reportsAreaMap;
            $valuesMap['Locked'] = [
                        ''=>'нет',
                        '0'=>'нет',
                        '1'=>'да',
                    ];

            switch($key) {
                case 'MasterUserId':
                    $usr = User::where("Id", "=", $value)->first();
                    $value = $usr ? $usr->Login : $value;
                    break;

                case 'AccessLevel':
                    $al = \App\Models\Access::where("Level", "=", $value)->first();
                    $value = $al ? $al->Name : $value;
                    break;
                case 'ClientId':
                    $cl = \App\Models\Client::where("id", "=", $value)->first();
                    $value = $cl ? $cl->Name : $value;
                    break;
            }

            return $valuesMap[$key][(string)$value] ?? $value ?? '<i>пусто<i>';
        };

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
                    'value' => function ($row) {



                        $ret = '';
                        foreach ($row->meta as $hRecord) {

                            if(isset(User::$fieldsLabels[$hRecord['key']])) {

                                $ret .= '<b>'.User::$fieldsLabels[$hRecord['key']].'</b>: ';

                                if(isset($hRecord['old'])) {
                                    $ret .= specifyValueIfNeed($hRecord['key'], $hRecord['old']).' -> ';
                                }

                                $ret .= specifyValueIfNeed($hRecord['key'], $hRecord['new']);

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
