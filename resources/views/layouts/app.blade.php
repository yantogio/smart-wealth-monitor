<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Smart Wealth Monitor')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
    <div class="flex min-h-screen">
        <x-sidebar />

        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b border-gray-200 px-6 py-4">
                <h1 class="text-xl font-semibold">@yield('title', 'Smart Wealth Monitor')</h1>
            </header>

            <main class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm whitespace-pre-line">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm whitespace-pre-line">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
