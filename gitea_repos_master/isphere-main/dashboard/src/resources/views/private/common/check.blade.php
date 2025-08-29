@extends('layouts.app-private')

@section('content')

    <form method="POST">
        @csrf

    <div class="row">

        <div class="col-12 col-lg-3">
            {{ Form::label('last_name', 'Фамилия') }}
            {{ Form::text('last_name', $request->get('last_name'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('first_name', 'Имя') }}
            {{ Form::text('first_name', $request->get('first_name'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('patronymic', 'Отчество') }}
            {{ Form::text('patronymic', $request->get('patronymic'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('date', 'Дата рождения') }}
            {{ Form::date('date', $request->get('date'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('passport_series', 'Серия паспорта') }}
            {{ Form::text('passport_series', $request->get('passport_series'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('passport_number', 'Номер паспорта') }}
            {{ Form::text('passport_number', $request->get('passport_number'), ['class' => 'form-control']) }}
        </div>


        <div class="col-12 col-lg-3">
            {{ Form::label('issueDate', 'Дата выдачи паспорта') }}
            {{ Form::date('issueDate', $request->get('issueDate'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3"></div>

        <div class="col-12 col-lg-3">
            {{ Form::label('inn', 'ИНН') }}
            {{ Form::text('inn', $request->get('inn'), ['class' => 'form-control']) }}
        </div>
        <div class="col-12 col-lg-9"></div>


        <div class="col-12 col-lg-3">
            {{ Form::label('driver_number', 'Номер ВУ') }}
            {{ Form::text('driver_number', $request->get('driver_number'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('driver_date', 'Дата выдачи ВУ') }}
            {{ Form::date('driver_date', $request->get('driver_date'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-6"></div>

        <div class="col-12 col-lg-3">
            {{ Form::label('mobile_phone', 'Мобильный телефон') }}
            {{ Form::text('mobile_phone', $request->get('mobile_phone'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('home_phone', 'Домашний телефон') }}
            {{ Form::text('home_phone', $request->get('home_phone'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('work_phone', 'Рабочий телефон') }}
            {{ Form::text('work_phone', $request->get('work_phone'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('additional_phone', 'Дополнительный телефон') }}
            {{ Form::text('additional_phone', $request->get('additional_phone'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('email', 'Email') }}
            {{ Form::text('email', $request->get('email'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('additional_email', 'Дополнительный Email') }}
            {{ Form::text('additional_email', $request->get('additional_email'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-6"></div>

        <div class="col-12 col-lg-3">

        {{ Form::label('region_id', 'Регион') }}
        {{ Form::select('region_id', $regions, $request->get('region_id'), ['class' => 'form-select']) }}

        </div>


        <div class="col-12 col-lg-9"></div>

        <div class="col-12 col-lg-6 pt-4">

            {{ Form::checkbox('async', 1, $request->get('async'), ['class' => 'form-check-input']) }}
            {{ Form::label('async', 'Подгружать информацию по мере получения') }}
        </div>



        <div class="col-12 col-lg-12">
            <hr class="my-4"/>

            @foreach($sources as $sid => $source)
                {{ Form::checkbox('sources[]', $sid, (in_array($sid, $request->get('sources', [])) || !is_array($request->get('sources')) && $source[1]), ['class' => 'form-check-input']) }} {{$source[0]}}
                @if($source[3])
                    <br/>
                @endif
            @endforeach
        </div>


        <div class="col-12 col-lg-2 d-grid">
            {{ Form::submit('Найти', array('class' => 'btn btn-primary mt-4')) }}
        </div>


    </div>

    </form>

    @if($xmlRequest)
        <textarea class="mt-4" style="width:100%;height:300px">
        {{$xmlRequest}}
        </textarea>

        <textarea class="mt-4" style="width:100%;height:700px">
        {{$response}}
        </textarea>
    @endif



@endsection
