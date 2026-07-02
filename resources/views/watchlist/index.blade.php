@extends('layouts.app')

@section('title', 'Watchlist & Analytics')

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga Terkini</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SMA (7)</th>
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
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $row['sma'] !== null ? number_format($row['sma'], 2) : 'N/A' }}
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

    <div class="space-y-8">
        @foreach ($assets as $row)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $row['asset']->name }} ({{ $row['asset']->code }})</h3>
                        <p class="text-sm text-gray-500">
                            Harga terkini: <span class="font-medium text-gray-900">{{ $row['latest_close'] !== null ? number_format($row['latest_close'], 2) : '—' }}</span>
                            &middot;
                            SMA (7 hari): <span class="font-medium text-gray-900">{{ $row['sma'] !== null ? number_format($row['sma'], 2) : 'N/A' }}</span>
                        </p>
                    </div>

                    @if ($row['is_discount'])
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Potensi Diskon</span>
                    @endif
                </div>

                @if ($row['history']->isNotEmpty())
                    <div class="h-80">
                        <canvas
                            data-price-chart
                            data-labels="{{ $row['history']->pluck('date')->toJson() }}"
                            data-values="{{ $row['history']->pluck('close')->toJson() }}"
                            data-sma="{{ $row['sma'] }}"
                        ></canvas>
                    </div>
                @else
                    <div class="h-80 flex items-center justify-center text-sm text-gray-400 border border-dashed border-gray-200 rounded-md">
                        Belum ada data harga historis untuk ditampilkan.
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
