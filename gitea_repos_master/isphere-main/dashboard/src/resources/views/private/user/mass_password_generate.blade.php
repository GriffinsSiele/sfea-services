@extends('layouts.app-private')

@section('css-includes')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js-includes')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
@endsection

@section('content')
    <form method="POST">
        @csrf

        <table class="table table-striped table-hover table-sm">
            <thead>
            <tr>
                <th>Логин</th>
                <th>Пароль</th>
            </tr>
            </thead>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->Login }}</td>
                <td>
                    {{ ($pass = $user::generatePassword()) }}
                    <input type="hidden" name="passwords[{{ $user->Id }}]" value="{{ $pass }}">
                </td>
            </tr>
        @endforeach

        </table>

        <br/>

        <button type="submit" class="btn btn-primary">Задать</button>

    </form>

@endsection
