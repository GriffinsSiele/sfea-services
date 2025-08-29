@extends('layouts.app-private')

@section('content')

    <a href="{{ route('accesses.forms', ['access' => $access->Level]) }}">Управление полями форм/запросов</a>
    <br/>
    <br/>

    {{ Html::ul($errors->all()) }}

    {{ Form::model($access, array('route' => array('accesses.update', $access->Level), 'method' => 'PUT')) }}

    <div class="row">
        <div class="col-12 col-lg-6">
        {{ Form::label('Name', 'Название') }}
        {{ Form::text('Name', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('users',0) }}
            {{ Form::checkbox('users', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('users', 'Пользователи (users)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('history',0) }}
            {{ Form::checkbox('history', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('history', 'История (history)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('bulk',0) }}
            {{ Form::checkbox('bulk', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('bulk', 'Обработка реестра (bulk)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('stats',0) }}
            {{ Form::checkbox('stats', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('stats', 'Статистика (stats)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('sources',0) }}
            {{ Form::checkbox('sources', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('sources', 'Список источников (sources)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            <hr/>
            {{ Form::hidden('check',0) }}
            {{ Form::checkbox('check', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('check', 'Проверка физ лица (check)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkorg',0) }}
            {{ Form::checkbox('checkorg', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkorg', 'Проверка организации', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone',0) }}
            {{ Form::checkbox('checkphone', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone', 'Проверка телефона', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_by',0) }}
            {{ Form::checkbox('checkphone_by', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_by', 'Проверка телефона (Белоруссия)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_kz',0) }}
            {{ Form::checkbox('checkphone_kz', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_kz', 'Проверка телефона (Казахстан)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_uz',0) }}
            {{ Form::checkbox('checkphone_uz', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_uz', 'Проверка телефона (Узбекистан)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_bg',0) }}
            {{ Form::checkbox('checkphone_bg', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_bg', 'Проверка телефона (Болгария)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_ro',0) }}
            {{ Form::checkbox('checkphone_ro', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_ro', 'Проверка телефона (Румыния)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_pl',0) }}
            {{ Form::checkbox('checkphone_pl', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_pl', 'Проверка телефона (Польша)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkphone_pt',0) }}
            {{ Form::checkbox('checkphone_pt', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkphone_pt', 'Проверка телефона (Португалия)', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkemail',0) }}
            {{ Form::checkbox('checkemail', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkemail', 'Проверка e-mail', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkurl',0) }}
            {{ Form::checkbox('checkurl', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkurl', 'Проверка профиля соцсети', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkskype',0) }}
            {{ Form::checkbox('checkskype', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkskype', 'Проверка skype', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkauto',0) }}
            {{ Form::checkbox('checkauto', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkauto', 'Проверка автомобиля', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
            {{ Form::hidden('checkip',0) }}
            {{ Form::checkbox('checkip', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('checkip', 'Проверка ip-адреса', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <hr/>
            {{ Form::label(null, 'Источники', ['class'=>'form-check-label']) }}
        </div>
    </div>

    @foreach($sources as $code => $checkTypes)
        <div class="row pt-2 checks-row">

            <div class="col-lg-2 col-sm-4 checks-basic">
                {{ Form::checkbox('accessSources['.$code.']', 1, in_array($code, $activeSources), ['class' => 'form-check-input checks-basic-item']) }} {{$code}} {{-- {{ $title }} () --}}
            </div>

            @if(count($checkTypes)> 1)
                <div class="col-lg-4 col-sm-6 checks-detailed">
                    <div class="checks-details btn btn-sm btn-light checks-btn">задать проверки</div>
                    <div class="checks-list" style="display: none">
                    @foreach($checkTypes as $checkType)
                        {{ Form::checkbox('accessSources['.$checkType->code.']', 1, in_array($checkType->code, $activeSources), ['class' => 'form-check-input checks-list-item']) }} {{$checkType->code}}<br/>
                    @endforeach
                    </div>
                </div>
            @endif


        </div>
    @endforeach

    <div class="row">
        <div class="col-2 d-grid pt-3">
            {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}


    <script type="text/javascript">

        $('.checks-basic-item').on('click', function () {
            $(this).parents('.checks-row').find('.checks-list-item').prop( "checked", false );
        });

        $('.checks-list-item').on('click', function () {
            $(this).parents('.checks-row').find('.checks-basic-item').prop( "checked", false );
        });

        $('.checks-btn').on('click', function () {
            if($(this).parent().find('.checks-list').is(":hidden"))
                $(this).parent().find('.checks-list').show();
            else
                $(this).parent().find('.checks-list').hide();
        });

        renderChButtons();

        function renderChButtons() {
            $('.checks-list').each(function () {
                if($(this).find('input:checked').length != 0)
                    $(this).show();
            })
        }
    </script>

    {{--
    {{ Form::model($access, array('route' => array('accesses.sources.add', $access->Level), 'method' => 'POST')) }}
        <div class="row pt-2">
            <div class="col-2">
                {{ Form::select('source_name', $sourcesNames, null, ['class' => 'form-select']) }}
            </div>
            <div class="col-2 d-grid">
                {{ Form::submit('добавить', array('class' => 'btn btn-sm btn-primary')) }}
            </div>
        </div>

    {{ Form::close() }}


    @foreach($accessSources as $source)
        <div class="row pt-2">
            <div class="col-2">{{ $source->source_name }}</div>
            <div class="col-2 d-grid">
                <a href="{{ route('accesses.sources.remove', ['access'=>$access->Level, 'source'=>$source->id]) }}" class="btn btn-sm btn-danger">удалить</a>
            </div>
        </div>
    @endforeach
    --}}

@endsection
