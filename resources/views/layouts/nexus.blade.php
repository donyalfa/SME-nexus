<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dasbor') | SME NEXUS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-paper font-sans text-ink antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ route('diagnostics.index') }}" class="text-lg font-semibold tracking-tight">SME NEXUS</a>
            <div class="flex items-center gap-5 text-sm font-medium">
                <a href="{{ route('diagnostics.index') }}" class="text-slate-600 hover:text-ink focus-visible:outline-2 focus-visible:outline-teal-600">Dasbor</a>
                <a href="{{ route('diagnostics.create') }}" class="rounded-lg bg-ink px-3 py-1.5 text-white hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">Diagnosis baru</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div role="status" class="mb-6 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
