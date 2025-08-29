@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($message, array('route' => array('messages.update', $message->id), 'method' => 'PUT')) }}

    <div class="row">
        <div class="col-12 col-lg-12">
        {{ Form::label('Text', 'Текст') }}
        {{ Form::textarea('Text', null, ['class' => 'form-control']) }}
        </div>
    </div>


    <div class="row">
        <div class="col-2 d-grid pt-3">
        {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

    <br/>
    <hr/>

    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link @if (request()->input('list', 'users') == 'users')
                    active
                    @endif
" href="{{ request()->fullUrlWithQuery(['list' => 'users']) }}">Пользователи</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if (request()->input('list') == 'clients')
                    active
                    @endif
" href="{{ request()->fullUrlWithQuery(['list' => 'clients']) }}">Клиенты</a>
        </li>
    </ul>

    <br/>
    <br/>

    @if (request()->input('list', 'users') == 'users')
        @php
            $gridData = [
                'dataProvider' => $dataProvider,
                'rowsPerPage' => 30,
                //'title' => 'Клиенты',
                'tableBordered' => false,
                'useFilters' => false,
                'useResetButton' => false,
                'columnFields' => [
                    [
                        'sort' => 'Login',
                        'label' => 'Логин',
                        'value' => function ($row) {
                            return $row->Login;
                        },
                    ],
                    [
                        'class' => Itstructure\GridView\Columns\ActionColumn::class,
                        'label' => '',
                        'actionTypes' => [
                            'edit' => function ($row) {
                                return route('users.edit', ['user' => $row->Id]);
                            },
                            'delete' => function ($row) use ($message) {
                                return route('messages.user.remove', ['message'=>$message->id, 'user'=>$row->Id]);
                            },
                        ],
                        'htmlAttributes' => [
                            'width' => '130px',
                        ],
                    ]
                ]
            ];
        @endphp
    @else
        @php
            $gridData = [
                'dataProvider' => $dataProvider,
                'rowsPerPage' => 30,
                //'title' => 'Клиенты',
                'useFilters' => false,
                'tableBordered' => false,
                'useResetButton' => false,
                'columnFields' => [
                    [
                        'sort' => 'OfficialName',
                        'label' => 'Название',
                        'value' => function ($row) {
                            return empty($row->OfficialName) ? $row->Name : $row->OfficialName;
                        },

                    ],
                    [
                        'class' => Itstructure\GridView\Columns\ActionColumn::class,
                        'label' => '',
                        'actionTypes' => [
                            'edit' => function ($row) {
                                return route('clients.edit', ['client' => $row->id]);
                            },
                            'delete' => function ($row) use ($message) {
                                return route('messages.client.remove', ['message'=>$message->id, 'client'=>$row->id]);
                            },
                        ],
                        'htmlAttributes' => [
                            'width' => '130px',
                        ],
                    ]
                ]
            ];
        @endphp
    @endif

    @gridView($gridData)

@endsection
