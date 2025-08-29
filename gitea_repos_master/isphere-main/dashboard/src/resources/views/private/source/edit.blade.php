@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($item, array('route' => array('sources.update', $item->id), 'method' => 'PUT')) }}

    @include('private/source/form_common', ['item'=>$item])

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Сохранить', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        <b>Параметры:</b><br/><br/>
        status: {{$item->status}}<br/>        
        </div>
    </div>




    
@endsection
