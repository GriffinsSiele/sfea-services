@extends('layouts.app-private')

@section('content')

    @can('use-function','clients_own')
        <a href="{{ route('clients.create') }}">Создать клиента</a>
    @endcan

    <form method="get">

        <div class="row">

            <div class="col-12 col-lg-3">
                {{ Form::label('OfficialName', 'Юридическое название') }}
                {{ Form::text('OfficialName', $request->get('OfficialName'), ['class' => 'form-control']) }}
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
            <th scope="col"  width="15%">Код</th>
            <th scope="col">Юридическое название</th>

        </tr>
        </thead>
        <tbody>
        @foreach($clients as $client)
            <tr>
                <th scope="row">{{ $client->id }}</th>
                <th scope="row"><a href="{{ route('clients.edit', ['client'=>$client->id]) }}">{{ $client->Code }}</a></th>
                <td><a href="{{ route('clients.edit', ['client'=>$client->id]) }}">{{ $client->OfficialName }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $clients->onEachSide(1)->links() }}


@endsection
