@props(['title' => config('app.name'), 'bodyDataSidebar' => 'dark'])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/hotelier/hotelierv3.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/hotelier/hotelierv3.png') }}">

    <x-layouts.shell.head-css />
    {{ $head ?? '' }}
    @livewireStyles
</head>
<body data-sidebar="{{ $bodyDataSidebar }}">
    <div id="layout-wrapper">
        <x-layouts.shell.topbar />
        <x-layouts.shell.sidebar />

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>

            <x-layouts.shell.footer />
        </div>
    </div>

    {{ $scripts ?? '' }}
    <x-layouts.shell.vendor-scripts />
</body>
</html>
