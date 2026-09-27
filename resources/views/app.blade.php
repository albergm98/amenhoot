<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lilita+One&display=swap" rel="stylesheet">

        @php
            $reverb = config('broadcasting.connections.reverb');
            $ecoHost = $reverb['options']['host'] ?? null;
            if (! is_string($ecoHost) || $ecoHost === '' || $ecoHost === 'localhost') {
                $ecoHost = request()->getHost();
            }
        @endphp
        <script>
            window.__AMENHOOT_ECHO__ = {
                key: @json($reverb['key'] ?? null),
                host: @json($ecoHost),
                port: @json((int) ($reverb['options']['port'] ?? (request()->isSecure() ? 443 : 80))),
                scheme: @json($reverb['options']['scheme'] ?? (request()->isSecure() ? 'https' : 'http')),
            };
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
