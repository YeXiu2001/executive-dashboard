@props(['title' => config('app.name')])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} | {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/images/hotelier/hotelierv3.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/images/hotelier/hotelierv3.png') }}">
        <x-layouts.shell.head-css />
        {{ $head ?? '' }}
        @livewireStyles
    </head>
    <body>
        <div class="account-pages my-5 pt-sm-5">
            <div class="container">
                {{ $slot }}
            </div>
        </div>

        {{ $scripts ?? '' }}
        <x-layouts.shell.vendor-scripts />
    </body>
</html>
