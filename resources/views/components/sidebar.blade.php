@php
    $links = [
        'dashboard' => ['label' => 'Dashboard', 'route' => 'dashboard'],
        'watchlist' => ['label' => 'Watchlist & Analytics', 'route' => 'watchlist.index'],
        'dca' => ['label' => 'Simulasi DCA', 'route' => 'dca.index'],
        'settings' => ['label' => 'System Settings', 'route' => 'settings.index'],
    ];
@endphp

<aside class="w-64 bg-gray-900 text-gray-100 flex flex-col">
    <div class="px-6 py-5 text-lg font-bold border-b border-gray-800">
        Smart Wealth Monitor
    </div>

    <nav class="flex-1 px-2 py-4 space-y-1">
        @foreach ($links as $key => $link)
            @php $active = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}"
               class="block rounded-md px-4 py-2 text-sm font-medium {{ $active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
