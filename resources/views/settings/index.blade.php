@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">MetalpriceAPI API Key</h2>

            @if ($maskedApiKey)
                <p class="text-sm text-gray-500 mb-4">Kunci saat ini: <span class="font-mono">{{ $maskedApiKey }}</span></p>
            @else
                <p class="text-sm text-gray-500 mb-4">Belum ada API key yang tersimpan.</p>
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

            <form method="POST" action="{{ route('settings.api-key.update') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="metals_api_key" class="block text-sm font-medium text-gray-700">MetalpriceAPI API Key</label>
                    <input type="password" id="metals_api_key" name="metals_api_key"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                           placeholder="Masukkan API key baru" required>
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Simpan API Key
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Sinkronisasi Data</h2>
            <p class="text-sm text-gray-500 mb-4">
                Jalankan Catch-Up Sync secara manual untuk mengambil data harga terbaru yang belum tersimpan.
            </p>

            <form method="POST" action="{{ route('settings.force-sync') }}">
                @csrf
                <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                    Force Sync Data
                </button>
            </form>
        </div>
    </div>
@endsection
