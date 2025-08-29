@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($access, array('route' => array('accesses.store'), 'method' => 'POST')) }}

    {{--
    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Level', 'Id') }}
            {{ Form::text('Level', null, ['class' => 'form-control']) }}
        </div>
    </div>
    --}}

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Name', 'Название') }}
            {{ Form::text('Name', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
