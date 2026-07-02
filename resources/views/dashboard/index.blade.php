@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if ($discounted->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @foreach ($discounted as $row)
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800">Potensi Diskon</p>
                    <p class="text-lg font-bold text-amber-900">{{ $row['asset']->name }} ({{ $row['asset']->code }})</p>
                    <p class="text-sm text-amber-700">Harga terkini: {{ number_format($row['latest_close'], 2) }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga Terkini</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($assets as $row)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium">{{ $row['asset']->code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $row['asset']->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $row['latest_close'] !== null ? number_format($row['latest_close'], 2) : '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($row['is_discount'])
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Potensi Diskon</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
