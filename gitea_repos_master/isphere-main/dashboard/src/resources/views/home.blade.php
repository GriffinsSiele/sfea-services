@extends('layouts.app-private')

@section('content')
    {{ Auth::user()->Login }}, добро пожаловать!
@endsection
