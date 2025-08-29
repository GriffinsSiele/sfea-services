@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($item, array('route' => array('check-types.store'), 'method' => 'POST')) }}


    @include('private/check_type/form_common', ['item'=>$item])

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
