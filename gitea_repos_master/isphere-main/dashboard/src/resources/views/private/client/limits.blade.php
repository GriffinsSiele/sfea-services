@extends('layouts.app-private')

@section('content')

    <div class="row">
        <div class="col-3">
            <a href="{{ route('clients.edit', $client->id) }}">Редактирование клиента</a>
        </div>
    </div>

    <br/>


    @if(count($availableLimits)> 0)

        <form method="POST" action="{{ route('clients.limits.add', $client->id) }}">
        @csrf
        <div class="row">
            <div class="col-4 col-lg-3">
                {{ Form::label('type', 'Период') }}
                {{ Form::select('PeriodType', $availableLimits, null, ['class' => 'form-select']) }}
            </div>

            <div class="col-4 col-lg-3">
                {{ Form::label('PriceLimit', 'Общая стоимость (р.)') }}
                {{ Form::text('PriceLimit', null, ['class' => 'form-control']) }}
            </div>

            <div class="col-4 col-lg-3">
                {{ Form::label('CountLimit', 'Общее количество') }}
                {{ Form::text('CountLimit', null, ['class' => 'form-control']) }}
            </div>

            <div class="col-2 d-grid pt-3">
                {{ Form::submit('Добавить', array('class' => 'btn btn-primary')) }}
            </div>
        </div>


        </form>
    @endif

    <table class="table table-striped table-hover table-sm mt-3">
        <thead>
            <tr>
                <th>Период</th>
                <th>Общая стоимость (р.)</th>
                <th>Общее количество</th>
                <th></th>
            </tr>
        </thead>
    @foreach($limits as $limit)
        <tr>
            <td>{{$limit->getPeriodTypeLabel()}}</td>
            <td>@if($limit->PriceLimit){{$limit->PriceLimit}} @else не ограничено @endif</td>
            <td>@if($limit->CountLimit){{$limit->CountLimit}} @else не ограничено @endif</td>
            <td><a href="{{ route('clients.limits.remove', ['client'=>$client->id, 'limit'=>$limit->Id]) }}">удалить</a></td>
        </tr>

    @endforeach
    </table>



@endsection
