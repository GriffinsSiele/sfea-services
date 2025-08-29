@extends('layouts.app-private')

@section('content')

    @can('use-function','users_own')
        <a href="{{ route('users.create') }}">Создать пользователя</a>
    @endcan

    <form method="get">

        <div class="row">

            <div class="col-12 col-lg-3">
                {{ Form::label('Login', 'Логин') }}
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
            <th scope="col" width="15%">Логин</th>
            <th scope="col">Организация</th>
        </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <th scope="row">{{ $user->Id }}</th>
                <td><a href="{{ route('users.edit', ['user'=>$user->Id]) }}">{{ $user->Login }}</a></td>
                <td>{{ $user->OrgName }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $users->onEachSide(1)->links() }}


@endsection
