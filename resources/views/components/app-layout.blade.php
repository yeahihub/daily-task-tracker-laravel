<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Daily Task Tracker') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- JavaScript Translations -->
    <script>
        window.translations = {
            confirmDelete: "{{ __('messages.js.confirm_delete') }}",
            unableToConnect: "{{ __('messages.js.unable_to_connect') }}",
            permissionDenied: "{{ __('messages.js.permission_denied') }}",
            resourceNotFound: "{{ __('messages.js.resource_not_found') }}",
            serverError: "{{ __('messages.js.server_error') }}",
            unexpectedError: "{{ __('messages.js.unexpected_error') }}",
        };
    </script>

    @stack('scripts')
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <x-navigation />

        <!-- Page Heading -->
        @isset($header)
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>