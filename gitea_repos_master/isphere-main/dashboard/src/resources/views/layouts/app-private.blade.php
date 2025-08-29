<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    @yield('css-includes')
    @yield('js-includes')
</head>
<body>
    <div id="app">



        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">

                <a class="navbar-brand" href="{{ url('/home') }}">
                    {{ config('app.name', 'Инфосфера') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">Вход</a>
                                </li>
                            @endif

                            <?php
                            /*
                             * @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                             */
                            ?>

                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->Login }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Выход
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">

            <div class="container">

                @if (Auth::user()->message || (Auth::user()->client && Auth::user()->client->message))
                <div class="row justify-content-center mb-2">
                    <div class="col-12 p-0">

                            <div class="card-body pb-1">
                                @if (Auth::user()->message)
                                <div class="alert alert-warning" role="alert">
                                    {!!  Auth::user()->message->Text !!}
                                </div>
                                @endif

                                @if (Auth::user()->client && Auth::user()->client->message)
                                    <div class="alert alert-warning" role="alert">
                                        {!! Auth::user()->client->message->Text !!}
                                    </div>
                                @endif
                            </div>

                    </div>
                </div>
                @endif

                <div class="row justify-content-center">


                    <div class="col-12 col-lg-3">


                                <ul class="list-group ">

                                    @can('use-function','users')
                                        <li class="list-group-item"><a href="{{ route('users.index'/*, ['filters'=>['Locked'=>0]]*/) }}">Пользователи</a></li>
                                    @endcan

                                    @can('use-function','clients_own')
                                        <li class="list-group-item"><a href="{{ route('clients.index'/*, ['filters'=>['Status'=>1]]*/) }}">Клиенты</a></li>
                                    @endcan

                                    @can('use-function','messages')
                                        <li class="list-group-item"><a href="{{ route('messages.index') }}">Объявления</a></li>
                                    @endcan

                                    @can('use-function','messages')
                                        <li class="list-group-item"><a href="{{ route('fields.index') }}">Поля источников</a></li>
                                    @endcan

                                    @can('use-function','source_account')
                                        <li class="list-group-item"><a href="{{ route('source-accounts.index') }}">Аккаунты источников</a></li>
                                    @endcan

                                    @can('use-function','access_levels_all')
                                        <li class="list-group-item"><a href="{{ route('accesses.index') }}">Доступы</a></li>
                                    @endcan

                                    @can('use-function','manage_system')
                                        <li class="list-group-item"><a href="{{ route('proxies.index') }}">Прокси</a></li>
                                    @endcan

                                    {{--
                                    @can('use-function','manage_system')
                                        <li class="list-group-item"><a href="{{ route('sources.index') }}">Источники</a></li>
                                    @endcan
                                    --}}

                                    @can('use-function','source_account')
                                        <li class="list-group-item"><a href="{{ route('check-types.index') }}">Проверки</a></li>
                                    @endcan

                                    @can('use-function','history')
                                        <li class="list-group-item"><a href="{{ route('history') }}">История</a></li>
                                    @endcan

                                    {{--
                                    @can('use-function','check')
                                        <li class="list-group-item"><a href="{{ route('check') }}">Ручные проверки</a></li>
                                    @endcan

                                    @can('use-function','stats')
                                        <li class="list-group-item"><a href="#">Статистика</a></li>
                                    @endcan
                                    --}}
                                </ul>


                    </div>

                    <div class="col-12 col-lg-9">

                        <div class="card">
                            <div class="card-header">{{ $pageTitle ?? 'Главная' }}</div>

                            <div class="card-body">

                                @if (session('status'))
                                    <div class="alert alert-success" role="alert">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                @yield('content')
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</body>
</html>
