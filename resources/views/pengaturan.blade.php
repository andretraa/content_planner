@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="max-w-4xl">
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Pengaturan</h2>
    <p class="text-gray-500 text-sm mb-8">Kelola preferensi dan konfigurasi akun Anda.</p>

    {{-- Profile Settings --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Profil Pengguna</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Masukkan nama...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="email@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zona Waktu</label>
                <select class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option>GMT+7 (WIB)</option>
                    <option>GMT+8 (WITA)</option>
                    <option>GMT+9 (WIT)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bahasa</label>
                <select class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option>Bahasa Indonesia</option>
                    <option>English</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Connected Accounts --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Akun Terhubung</h3>
        <div class="space-y-3">
            @foreach([
                ['name' => 'Instagram', 'color' => 'from-purple-500 to-pink-500', 'status' => false],
                ['name' => 'TikTok', 'color' => 'from-gray-800 to-gray-900', 'status' => false],
                ['name' => 'YouTube', 'color' => 'from-red-500 to-red-600', 'status' => false],
                ['name' => 'Threads', 'color' => 'from-gray-600 to-gray-700', 'status' => false],
            ] as $account)
            <div class="flex items-center justify-between p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br {{ $account['color'] }} rounded-xl flex items-center justify-center text-white text-sm font-bold">
                        {{ substr($account['name'], 0, 2) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $account['name'] }}</p>
                        <p class="text-xs text-gray-400">Belum terhubung</p>
                    </div>
                </div>
                <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    Hubungkan
                </button>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Notification Settings --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notifikasi</h3>
        <div class="space-y-4">
            @foreach([
                ['label' => 'Notifikasi Email', 'desc' => 'Terima laporan mingguan via email'],
                ['label' => 'Notifikasi Push', 'desc' => 'Pemberitahuan jadwal konten'],
                ['label' => 'Ringkasan Harian', 'desc' => 'Kirim ringkasan performa harian'],
            ] as $notif)
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $notif['label'] }}</p>
                    <p class="text-xs text-gray-400">{{ $notif['desc'] }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                </label>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Save Button --}}
    <div class="flex justify-end">
        <button class="px-6 py-2.5 bg-blue-500 text-white text-sm font-semibold rounded-xl hover:bg-blue-600 transition shadow-lg shadow-blue-500/25">
            Simpan Perubahan
        </button>
    </div>
</div>
@endsection

