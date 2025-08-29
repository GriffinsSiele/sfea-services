@extends('layouts.app-private')

@section('css-includes')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js-includes')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
@endsection

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($sa, array('route' => array('source-accounts.store'), 'method' => 'POST')) }}


    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('sourceid', 'Источник') }}
            {{ Form::select('sourceid', $sources, $sa->sourceid, ['class' => 'form-select']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6">
            {{ Form::label('clientid', 'Клиент') }}
            {{ Form::select('clientid', [], $sa->clientid, ['class' => 'form-select', 'id'=>'clientSearch']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('login', 'Логин') }}
            {{ Form::text('login', $sa->login, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('password', 'Пароль') }}
            {{ Form::text('password', $sa->password, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-6 col-lg-6">
            {{ Form::label('note', 'Примечание') }}
            {{ Form::text('note', $sa->note, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6">
            {{ Form::label('status', 'Статус') }}
            {{ Form::select('status', \App\Models\SourceAccount::$statusMap, $sa->status, ['class' => 'form-select']) }}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6">
            {{ Form::label('unlocktime', 'Время следующей попытки входа') }}
            {{ Form::datetimeLocal('unlocktime', $sa->unlocktime, ['class' => 'form-control']) }}
        </div>
    </div>



    <div class="row mt-2">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

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

@endsection
