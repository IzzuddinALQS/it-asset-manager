<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#fafafa">

        <title inertia>{{ config('app.name', 'IT Asset Manager') }}</title>

        @routes
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    </head>
    <body class="h-full font-sans antialiased">
        @inertia
    </body>
</html>
