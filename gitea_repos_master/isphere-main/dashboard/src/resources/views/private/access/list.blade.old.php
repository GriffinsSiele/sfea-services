@extends('layouts.app-private')

@section('content')

    @can('use-function','clients_own')
        <a href="{{ route('accesses.create') }}">Создать доступ</a>
    @endcan

    <table class="table table-striped mt-2">
        <thead>
        <tr>
            <th scope="col" width="1%">#</th>
            <th scope="col"  width="15%">Название</th>

        </tr>
        </thead>
        <tbody>
        @foreach($accesses as $access)
            <tr>
                <th scope="row">{{ $access->Level }}</th>
                <td><a href="{{ route('accesses.edit', ['access'=>$access->Level]) }}">{{ $access->Name }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $accesses->onEachSide(1)->links() }}


@endsection
