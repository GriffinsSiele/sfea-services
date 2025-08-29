@extends('layouts.app-private')

@section('css-includes')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js-includes')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('content')
    @if (count($users) > 0)
    <form method="POST">
        @csrf

        {{--
        @if ($login)
            <div class="row">
                <div class="col-12 col-lg-6">
                    {{ Form::label('login', 'Префикс логина: ') }}
                    {{ $login }}
                </div>
            </div>
        @endif
        --}}

        @if ($accessLevel)
            <div class="row">
                <div class="col-12 col-lg-6">
                    {{ Form::label('login', 'Тип доступа: ') }}
                    {{ $accessLevel->Name }}
                    <input type="hidden" name="accessLevel" value="{{ $accessLevel->Level }}">
                </div>
            </div>
        @endif

        @if ($client)
            <div class="row">
                <div class="col-12 col-lg-6">
                    {{ Form::label('login', 'Клиент: ') }}
                    {{ $client->OfficialName }}
                    <input type="hidden" name="clientId" value="{{ $client->id }}">
                </div>
            </div>
        @endif

        @if ($masterUsers || 1)
            @can('use-function','users_clients')
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <br/>
                        {{ Form::label('masterUserId', 'Основной пользователь') }}
                        {{ Form::select('masterUserId', $masterUsers, null, ['class' => 'form-select']) }}
                    </div>
                </div>
            @endcan
        @endif

        <div class="row">
            <div class="col-12">
                <br/>

                <table class="table table-striped table-hover table-sm">
                    <thead>
                    <tr>
                        <th>Логин</th>
                        <th>Пароль</th>
                        <th>ФИО</th>
                        <th>Почта</th>
                    </tr>
                    </thead>

                @foreach($users as $ind => $user)
                    <tr>
                        <td><input type="hidden" name="users[{{ $ind }}][Login]" value="{{ $user->Login }}">{{$user->Login}}</td>
                        <td>
                            {{ ($pass = $user::generatePassword()) }}
                            <input type="hidden" name="users[{{ $ind }}][Password]" value="{{ $pass }}">
                        </td>
                        <td><input type="hidden" name="users[{{ $ind }}][Name]" value="{{ $user->Name }}">{{ $user->Name }}</td>
                        <td><input type="hidden" name="users[{{ $ind }}][Email]" value="{{ $user->Email }}">{{ $user->Email }}</td>
                    </tr>
                @endforeach

                </table>
            </div>
        </div>

        <input type="checkbox" class="form-check-input disabled" disabled="1" name="emailNotifications" id="email-notifications"> <label class="form-check-label" for="email-notifications">Разослать пароли по почте</label>

        <br/>
        <br/>

        <button type="submit" class="btn btn-primary">Импортировать</button>
        &nbsp;

        <a href="{{url()->current()}}" class="btn btn-default">Отменить</a>


    </form>

    @else
        <form method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-12 col-lg-6">
                    {{ Form::label('login', 'Префикс логина') }}
                    {{ Form::text('login', null, ['class' => 'form-control']) }}
                </div>
            </div>

            @if (count($accessLevels))
                <div class="row">
                    <div class="col-12 col-lg-6">
                        {{ Form::label('accessLevel', 'Тип доступа') }}
                        {{ Form::select('accessLevel', $accessLevels, env('USER_DEFAULT_ACCESS_LEVEL_ID'), ['class' => 'form-select']) }}
                    </div>
                </div>
            @endif

            @can('use-function','clients_own')
                <div class="row">
                    <div class="col-12 col-lg-6">
                        {{ Form::label('clientId', 'Клиент') }}
                        {{ Form::select('clientId', [], null, ['class' => 'form-select', 'id'=>'clientSearch']) }}
                    </div>
                </div>
            @endcan

            <div class="row">

                <div class="col-12 col-lg-6">
                    <br/>
                    В файле <b>обязательно</b> должна быть колонка <b>"ФИО"</b>, опционально колонка "Почта"
                    <br/>
                    <br/>
                    <input class="form-control" type="file" name="import">
                    <br/>
                    <button type="submit" class="btn btn-primary">Загрузить</button>
                </div>
            </div>
        </form>

    @endif

    @can('use-function','clients_own')
        <script type="text/javascript">

            $('#clientSearch').select2({
                placeholder: 'Укажите клиента',
                language: "ru",
                allowClear: true,
                language: {
                    noResults: function(){
                        return "Нет результатов";
                    },
                    searching: function () {
                        return 'Поиск';
                    },
                },
                ajax: {
                    url: '/private/clients/select-search',
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.OfficialName,
                                    id: item.Id
                                }
                            })
                        };
                    },
                    cache: true
                }
            });

            $('.select2-container').addClass('form-select');
            $('b[role="presentation"]').hide();

        </script>
    @endcan

@endsection
