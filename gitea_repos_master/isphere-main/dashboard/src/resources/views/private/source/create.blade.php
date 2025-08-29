@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($item, array('route' => array('sources.store'), 'method' => 'POST')) }}

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('code', 'Код') }}
            {{ Form::text('code', null, ['class' => 'form-control']) }}
        </div>
    </div>

    @include('private/source/form_common', ['item'=>$item])

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
