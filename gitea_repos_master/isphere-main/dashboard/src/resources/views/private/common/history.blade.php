@extends('layouts.app-private')

@section('content')

    <form method="get">

    <div class="row">
        <div class="col-12 col-lg-3">
            {{ Form::label('from', 'С') }}
            {{ Form::date('from', $from, ['class' => 'form-select']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('to', 'По') }}
            {{ Form::date('to', $to, ['class' => 'form-select']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{ Form::label('clientId', 'Клиент') }}
            {{ Form::select('clientId', $clients, $request->get('clientId'), ['class' => 'form-select']) }}
        </div>

        <div class="col-12 col-lg-3">
            {{--
            {{ Form::label('userId', 'Пользователь') }}
            {{ Form::select('userId', $users, $request->get('userId'), ['class' => 'form-select']) }}
            --}}

            {{ Form::label('Login', 'Пользователь') }}
            {{ Form::text('Login', $request->get('Login'), ['class' => 'form-control']) }}
        </div>

        <div class="col-12 col-lg-2 d-grid">
            {{ Form::submit('Фильтровать', array('class' => 'btn btn-primary mt-4')) }}
        </div>
    </div>

    </form>

    <table class="table table-striped mt-2">
        <thead>
        <tr>
            <th scope="col" width="1%">#</th>
            <th scope="col" width="20%">Время</th>
            <th scope="col">Пользователь</th>
            <th scope="col">Тип</th>
            <th scope="col">IP</th>
            <th scope="col">Источники</th>
            <th scope="col">Данные запроса</th>
            <th scope="col">Результат</th>
        </tr>
        </thead>
        <tbody>
        @foreach($history as $row)
            <tr>
                <td>{{ $row->id }}
                    @if($row->external_id)
                    / {{ $row->external_id }}
                    @endif
                </td>
                <td>{{ $row->created_at }}<br/>{{$row->processed_at}}</td>
                <td>{{ $row->user ? $row->user->Login : '-' }}</td>
                <td>{{ $row->type ? $row->type :'api' }}</td>
                <td>{{ $row->ip }}</td>
                <td>
                    @if($row->parsedRequest() && $row->parsedRequest()->sources)
                        {{$row->parsedRequest()->sources}}
                    @elseif($row->parsedRequest() && $row->parsedRequest()->PersonReq->sources)
                        {{$row->parsedRequest()->PersonReq->sources}}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if(!$row->parsedRequest())
                        Недоступны
                    @else
                        @foreach($row->entities() as $entity)
                            {{$entity}}<br/>
                        @endforeach

                    @endif


                </td>
                <td>
                    <a href="{{ route('history-details', ['requestNew'=>$row->id]) }}">Просмотр</a><br/>
                    <a href="{{ route('history-details', ['requestNew'=>$row->id, 'type'=>'pdf']) }}">PDF</a><br/>
                    <a href="{{ route('history-details', ['requestNew'=>$row->id, 'type'=>'xml']) }}">XML</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $history->appends($request->all())->onEachSide(1)->links() }}

@endsection
