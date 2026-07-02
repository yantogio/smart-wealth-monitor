@extends('layouts.app')

@section('title', 'Simulasi DCA')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Kalkulator DCA</h2>

            @if ($error)
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    {{ $error }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('dca.simulate') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="asset_id" class="block text-sm font-medium text-gray-700">Aset</label>
                    <select id="asset_id" name="asset_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}" @selected(old('asset_id', $selectedAsset->id ?? null) == $asset->id)>
                                {{ $asset->name }} ({{ $asset->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="monthly_amount" class="block text-sm font-medium text-gray-700">Nominal Investasi Bulanan</label>
                    <input type="number" step="0.01" min="0.01" id="monthly_amount" name="monthly_amount"
                           value="{{ old('monthly_amount') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>

                <div>
                    <label for="start_month" class="block text-sm font-medium text-gray-700">Bulan Mulai</label>
                    <input type="month" id="start_month" name="start_month"
                           value="{{ old('start_month') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Hitung Simulasi
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Hasil Simulasi</h2>

            @if ($result)
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Jumlah Bulan Investasi</dt>
                        <dd class="text-sm font-medium">{{ $result['months_invested'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Modal Diinvestasikan</dt>
                        <dd class="text-sm font-medium">{{ number_format($result['total_capital'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Unit Terkumpul</dt>
                        <dd class="text-sm font-medium">{{ number_format($result['total_units'], 6) }}</dd>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                        <dt class="text-sm text-gray-700 font-semibold">Nilai Aset Saat Ini</dt>
                        <dd class="text-sm font-bold {{ $result['current_value'] >= $result['total_capital'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($result['current_value'], 2) }}
                        </dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500">Isi form untuk melihat hasil simulasi DCA.</p>
            @endif
        </div>
    </div>
@endsection
