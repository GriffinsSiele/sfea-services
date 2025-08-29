@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($message, array('route' => array('messages.store'), 'method' => 'POST')) }}


    <div class="row">
        <div class="col-12 col-lg-12">
            {{ Form::label('Text', 'Текст') }}
            {{ Form::textarea('Text', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
