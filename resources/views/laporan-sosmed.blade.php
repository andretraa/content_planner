@extends('layouts.app')

@section('title', 'Laporan Sosmed')

@section('content')
<div class="space-y-6" id="laporan-sosmed-app">
    {{-- Toast Notification --}}
    <div id="toast-notification" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-gray-700">
        <div id="toast-icon" class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <div>
            <p id="toast-title" class="text-sm font-semibold">Berhasil</p>
            <p id="toast-message" class="text-xs text-gray-300">Data laporan berhasil disimpan.</p>
        </div>
    </div>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                    REALTIME ANALYTICS
                </span>
                <span class="text-sm text-gray-500 flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-400 rounded-full inline-block animate-pulse"></span>
                    Updated 5 mins ago
                </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Overview Laporan Sosmed</h1>
            <p class="text-gray-500 text-sm mt-1">Pantau performa media sosial Anda secara menyeluruh dan real-time</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            {{-- Period Filter Switcher --}}
            <div class="inline-flex items-center bg-gray-100 rounded-xl p-1 border border-gray-200">
                <button type="button" id="btn-period-mingguan" onclick="setPeriod('mingguan')" class="px-4 py-2 text-sm font-semibold rounded-lg bg-white text-gray-900 shadow-sm transition">
                    Mingguan
                </button>
                <button type="button" id="btn-period-bulanan" onclick="setPeriod('bulanan')" class="px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition">
                    Bulanan
                </button>
            </div>

            {{-- Export CSV Button --}}
            <a href="{{ route('reports.export') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Laporan
            </a>

            {{-- Tambah Laporan Button --}}
            <button type="button" onclick="openReportModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-500/25 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Laporan
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-end gap-3 bg-white rounded-2xl border border-gray-200 p-4 shadow-xs">
        <div>
            <label for="filter-date-from" class="block text-xs font-semibold text-gray-600 mb-1">Dari tanggal</label>
            <input type="date" id="filter-date-from" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="filter-date-to" class="block text-xs font-semibold text-gray-600 mb-1">Sampai tanggal</label>
            <input type="date" id="filter-date-to" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="button" onclick="applyDateFilter()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition">Terapkan Filter</button>
        <button type="button" onclick="resetReportFilters()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">Reset</button>
        <p id="filter-range-label" class="text-xs text-gray-500 sm:ml-auto sm:pb-2"></p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Followers --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-500 font-medium">Total Followers</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div id="stat-total-followers" class="text-3xl font-bold text-gray-900 mb-1">-</div>
            <div class="text-sm text-gray-400" id="stat-period-label-1">pada periode terpilih</div>
        </div>

        {{-- Engagement Rate --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-500 font-medium">Engagement Rate</span>
                <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div id="stat-engagement" class="text-3xl font-bold text-gray-900 mb-1">-</div>
            <div class="text-sm text-gray-400" id="stat-period-label-2">rata-rata periode terpilih</div>
        </div>

        {{-- Jangkauan (Reach) --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-500 font-medium">Jangkauan (Reach)</span>
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
            </div>
            <div id="stat-reach" class="text-3xl font-bold text-gray-900 mb-1">-</div>
            <div class="text-sm text-gray-400" id="stat-period-label-3">total periode terpilih</div>
        </div>

        {{-- Total Tayangan --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-500 font-medium">Total Tayangan</span>
                <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>
            <div id="stat-impressions" class="text-3xl font-bold text-gray-900 mb-1">-</div>
            <div class="text-sm text-gray-400" id="stat-period-label-4">total periode terpilih</div>
        </div>
    </div>

    {{-- Middle Section: Chart + Distribution --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        {{-- Interactive Performance Chart --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-lg font-semibold text-gray-900">Grafik Performa Interaktif</h2>
                <span id="chart-period-badge" class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">Periode 7 Hari</span>
            </div>
            <p class="text-sm text-gray-500 mb-4" id="chart-sub-label">Perbandingan jangkauan dan tayangan 7 hari terakhir</p>
            <div class="flex items-center gap-5 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-sky-300"></span>
                    <span class="text-sm text-gray-600">Jangkauan</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-700"></span>
                    <span class="text-sm text-gray-600">Tayangan</span>
                </div>
            </div>
            <div class="relative" style="height: 250px;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        {{-- Platform Distribution --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Distribusi Platform</h2>
                <p class="text-sm text-gray-500 mb-5">Kontribusi performa per platform</p>
                <div class="space-y-4">
                    {{-- Instagram --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                <span class="text-sm font-medium text-gray-700">Instagram</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-700" id="dist-instagram-pct">-%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div id="dist-instagram-bar" class="bg-pink-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    {{-- TikTok --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-900"></span>
                                <span class="text-sm font-medium text-gray-700">TikTok</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-700" id="dist-tiktok-pct">-%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div id="dist-tiktok-bar" class="bg-gray-900 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    {{-- YouTube --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                <span class="text-sm font-medium text-gray-700">YouTube</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-700" id="dist-youtube-pct">-%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div id="dist-youtube-bar" class="bg-red-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    {{-- Threads --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                <span class="text-sm font-medium text-gray-700">Threads</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-700" id="dist-threads-pct">-%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div id="dist-threads-bar" class="bg-gray-400 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wide">Platform Teraktif</span>
                    <div class="mt-1">
                        <span id="platform-teraktif" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">-</span>
                    </div>
                </div>
                <button type="button" onclick="openReportModal()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    + Sinkron Konten Baru
                </button>
            </div>
        </div>
    </div>

    {{-- Bottom Section: Top Performing Content with FULL CRUD --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Konten Performa Terbaik</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola, perbarui, dan pantau metrik konten dari berbagai platform</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Platform Filter Dropdown --}}
                <select id="table-platform-filter" onchange="filterReports()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 shadow-xs">
                    <option value="all">Semua Platform</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="youtube">YouTube</option>
                    <option value="threads">Threads</option>
                </select>

                {{-- Search Input --}}
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="report-search" oninput="filterReports()" placeholder="Cari konten atau deskripsi..." class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-60 shadow-xs">
                </div>

                {{-- Tambah Konten Button --}}
                <button type="button" onclick="openReportModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-blue-500/20 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Konten
                </button>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pr-4">KONTEN / DESKRIPSI</th>
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">PLATFORM</th>
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">FOLLOWERS</th>
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">TAYANGAN</th>
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">JANGKAUAN</th>
                        <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 px-4">ENGAGEMENT</th>
                        <th class="text-center text-xs font-semibold text-gray-400 uppercase tracking-wider pb-3 pl-4">AKSI</th>
                    </tr>
                </thead>
                <tbody id="reports-table-body" class="divide-y divide-gray-50">
                    {{-- Dynamically rendered via JavaScript --}}
                </tbody>
            </table>
        </div>

        {{-- Empty State --}}
        <div id="reports-empty-state" class="hidden py-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto text-gray-400 mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </div>
            <p class="text-gray-700 font-semibold text-base mb-1">Belum ada data laporan konten</p>
            <p class="text-gray-400 text-sm max-w-sm mx-auto mb-4">Tambahkan laporan performa konten baru atau tarik dari jadwal Content Planner &amp; Ide Konten.</p>
            <button type="button" onclick="openReportModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition shadow-md shadow-blue-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Konten Pertama
            </button>
        </div>

        {{-- Pagination & Count Info --}}
        <div class="flex flex-col sm:flex-row items-center justify-between mt-5 pt-4 border-t border-gray-100 gap-3">
            <span class="text-sm text-gray-500" id="pagination-info">Menampilkan 0 dari 0 konten terbaik</span>
            <div class="flex items-center gap-1" id="pagination-buttons">
                <button type="button" class="px-3 py-1.5 text-sm text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed" disabled>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg">1</button>
                <button type="button" class="px-3 py-1.5 text-sm text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed" disabled>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH / EDIT LAPORAN KONTEN --}}
<div id="modal-report" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Backdrop --}}
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" onclick="closeReportModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        {{-- Modal Box --}}
        <div class="inline-block w-full max-w-xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl relative z-10 border border-gray-100">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="report-modal-title">Tambah Laporan Konten</h3>
                        <p class="text-xs text-gray-500">Isi metrik performa atau tarik otomatis dari Content Planner &amp; Ide Konten</p>
                    </div>
                </div>
                <button type="button" onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-report" onsubmit="handleReportSubmit(event)" class="mt-5 space-y-4">
                <input type="hidden" id="report-id" value="">

                {{-- SYNC SELECTOR: Tarik dari Content Planner / Ide Konten --}}
                <div class="p-3.5 bg-gradient-to-r from-blue-50 to-indigo-50/60 rounded-2xl border border-blue-100/80">
                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"/></svg>
                        Sinkronkan dari Content Planner / Ide Konten
                    </label>
                    <select id="report-sync-select" onchange="applySyncSelection(this.value)" class="w-full px-3 py-2 text-xs bg-white border border-blue-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800">
                        <option value="">-- Buat Manual / Atau Pilih Konten yang Ingin Disinkronkan --</option>
                        <optgroup label="📅 Dari Content Planner (Jadwal)">
                            @foreach($schedules as $sched)
                                <option value="sched_{{ $sched['id'] }}">
                                    [{{ ucfirst($sched['platform']) }}] {{ $sched['title'] }} ({{ $sched['date'] }})
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="💡 Dari Ide Konten (Kanban)">
                            @foreach($ideas as $idea)
                                <option value="idea_{{ $idea['id'] }}">
                                    [{{ ucfirst($idea['platform']) }}] {{ $idea['title'] }} (Status: {{ str_replace('_', ' ', $idea['column']) }})
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                {{-- Judul Konten --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Judul Konten <span class="text-red-500">*</span></label>
                    <input type="text" id="report-title" required placeholder="Contoh: Tips & Trik Edit Reels Viral..." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Deskripsi / Keterangan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Keterangan / Waktu Posting</label>
                    <input type="text" id="report-description" placeholder="Contoh: Diposting 3 hari lalu" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Platform & Format Tag --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Platform <span class="text-red-500">*</span></label>
                        <select id="report-platform" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="youtube">YouTube</option>
                            <option value="threads">Threads</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Format / Tag</label>
                        <input type="text" id="report-tag" placeholder="Reels, Video, TikTok, dsb." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Periode & Tanggal --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Periode Laporan</label>
                        <select id="report-period" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="mingguan">Mingguan</option>
                            <option value="bulanan">Bulanan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Tanggal</label>
                        <input type="date" id="report-date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Metrik: Tayangan, Jangkauan, Engagement (Boleh diisi angka atau dikosongkan "-") --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tayangan</label>
                        <input type="text" id="report-tayangan" placeholder="45,200 atau -" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="text-[10px] text-gray-400">Kosongkan untuk "-"</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jangkauan</label>
                        <input type="text" id="report-jangkauan" placeholder="38,100 atau -" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="text-[10px] text-gray-400">Kosongkan untuk "-"</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Engagement (%)</label>
                        <input type="text" id="report-engagement" placeholder="12.4% atau -%" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="text-[10px] text-gray-400">Kosongkan untuk "-%"</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Total Followers Platform</label>
                    <input type="text" id="report-followers" placeholder="Contoh: 12500" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-[10px] text-gray-400">Masukkan jumlah followers saat tanggal laporan; isi ulang saat ada perubahan.</span>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeReportModal()" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-xl hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" id="btn-save-report" class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-lg shadow-blue-500/25 active:scale-95">
                        Simpan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL HAPUS KONFIRMASI --}}
<div id="modal-delete" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" onclick="closeDeleteModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl relative z-10 border border-gray-100 text-center">
            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Hapus Laporan Konten?</h3>
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus laporan "<span id="delete-item-title" class="font-medium text-gray-700"></span>"? Data yang dihapus tidak dapat dikembalikan.</p>
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-xl hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="button" id="btn-confirm-delete" onclick="confirmDeleteReport()" class="px-6 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-lg shadow-red-500/25 active:scale-95">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Global State
    let reportsData = @json($reports ?? []);
    const schedulesSource = @json($schedules ?? []);
    const ideasSource = @json($ideas ?? []);

    let currentPeriod = 'mingguan';
    let currentPlatformFilter = 'all';
    let currentSearch = '';
    let activeDateFrom = '';
    let activeDateTo = '';
    let reportToDeleteId = null;
    let performanceChartInstance = null;

    function getLocalDateString(date = new Date()) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function updatePeriodLabels() {
        const from = activeDateFrom;
        const to = activeDateTo;
        const badge = document.getElementById('chart-period-badge');
        const rangeLabel = document.getElementById('filter-range-label');
        const periodLabel = currentPeriod === 'custom'
            ? 'Periode khusus'
            : currentPeriod === 'mingguan' ? 'Periode 7 Hari' : 'Periode 30 Hari';
        const rangeText = from && to ? `${from} s.d. ${to}` : 'periode terpilih';

        badge.innerText = periodLabel;
        document.getElementById('chart-sub-label').innerText = `Perbandingan jangkauan dan tayangan ${rangeText}`;
        rangeLabel.innerText = rangeText;
        document.getElementById('btn-period-mingguan').className = currentPeriod === 'mingguan'
            ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-white text-gray-900 shadow-sm transition'
            : 'px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition';
        document.getElementById('btn-period-bulanan').className = currentPeriod === 'bulanan'
            ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-white text-gray-900 shadow-sm transition'
            : 'px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition';
    }

    function setPeriod(period) {
        currentPeriod = period;
        const end = new Date();
        const start = new Date(end);
        start.setDate(start.getDate() - (period === 'mingguan' ? 6 : 29));
        document.getElementById('filter-date-from').value = getLocalDateString(start);
        document.getElementById('filter-date-to').value = getLocalDateString(end);
        activeDateFrom = document.getElementById('filter-date-from').value;
        activeDateTo = document.getElementById('filter-date-to').value;
        updatePeriodLabels();
        renderDashboard();
    }

    function applyDateFilter() {
        const from = document.getElementById('filter-date-from').value;
        const to = document.getElementById('filter-date-to').value;
        if (!from || !to || from > to) {
            showToast('Rentang tanggal tidak valid', 'Pilih tanggal awal dan akhir yang benar.', true);
            return;
        }

        currentPeriod = 'custom';
        activeDateFrom = from;
        activeDateTo = to;
        updatePeriodLabels();
        renderDashboard();
    }

    function resetReportFilters() {
        currentPlatformFilter = 'all';
        currentSearch = '';
        document.getElementById('table-platform-filter').value = 'all';
        document.getElementById('report-search').value = '';
        setPeriod('mingguan');
    }

    function getSelectedReports() {
        const from = activeDateFrom;
        const to = activeDateTo;
        if (!from || !to || from > to) return [];

        return reportsData.filter(item => {
            const date = item.date || '';
            const platformMatches = currentPlatformFilter === 'all'
                || (item.platform || '').toLowerCase() === currentPlatformFilter.toLowerCase();
            return platformMatches && date >= from && date <= to;
        });
    }

    function parseMetric(value) {
        if (value === null || value === undefined || value === '' || value === '-' || value === '-%') return null;
        const parsed = Number(String(value).replace(/%/g, '').replace(/,/g, '').trim());
        return Number.isFinite(parsed) ? parsed : null;
    }

    function formatMetric(value) {
        return value === null ? '-' : Math.round(value).toLocaleString('id-ID');
    }

    function updateStats() {
        const selected = getSelectedReports();
        const snapshots = new Map();
        selected.forEach(report => {
            const followers = parseMetric(report.followers);
            if (followers === null) return;
            const platform = (report.platform || 'lainnya').toLowerCase();
            const previous = snapshots.get(platform);
            if (!previous || (report.date || '') > (previous.date || '')
                || ((report.date || '') === (previous.date || '') && Number(report.id) > Number(previous.id))) {
                snapshots.set(platform, report);
            }
        });

        const followerTotal = [...snapshots.values()].reduce((total, report) => total + parseMetric(report.followers), 0);
        const reachValues = selected.map(report => parseMetric(report.jangkauan)).filter(value => value !== null);
        const impressionValues = selected.map(report => parseMetric(report.tayangan)).filter(value => value !== null);
        const engagementValues = selected.map(report => parseMetric(report.engagement)).filter(value => value !== null);

        document.getElementById('stat-total-followers').innerText = snapshots.size ? formatMetric(followerTotal) : '-';
        document.getElementById('stat-reach').innerText = reachValues.length
            ? formatMetric(reachValues.reduce((total, value) => total + value, 0)) : '-';
        document.getElementById('stat-impressions').innerText = impressionValues.length
            ? formatMetric(impressionValues.reduce((total, value) => total + value, 0)) : '-';
        document.getElementById('stat-engagement').innerText = engagementValues.length
            ? `${(engagementValues.reduce((total, value) => total + value, 0) / engagementValues.length).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`
            : '-';
    }

    function renderDashboard() {
        renderReportsTable();
        updateStats();
        updateDistribution();
        updateChart();
    }

    // Toast helper
    function showToast(title, message, isError = false) {
        const toast = document.getElementById('toast-notification');
        const toastTitle = document.getElementById('toast-title');
        const toastMsg = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');

        toastTitle.innerText = title;
        toastMsg.innerText = message;

        if (isError) {
            toastIcon.className = "w-6 h-6 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center flex-shrink-0";
            toastIcon.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
        } else {
            toastIcon.className = "w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0";
            toastIcon.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>`;
        }

        toast.classList.remove('translate-y-20', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3200);
    }

    // Modal Handlers
    function openReportModal(editId = null) {
        const modal = document.getElementById('modal-report');
        const form = document.getElementById('form-report');
        const modalTitle = document.getElementById('report-modal-title');
        const syncSelect = document.getElementById('report-sync-select');

        form.reset();
        document.getElementById('report-id').value = '';
        syncSelect.value = '';

        if (editId) {
            const item = reportsData.find(r => r.id == editId);
            if (item) {
                modalTitle.innerText = 'Edit Laporan Konten';
                document.getElementById('report-id').value = item.id;
                document.getElementById('report-title').value = item.title;
                document.getElementById('report-description').value = item.description || '';
                document.getElementById('report-platform').value = item.platform || 'instagram';
                document.getElementById('report-tag').value = item.tag || '';
                document.getElementById('report-period').value = item.period || 'mingguan';
                document.getElementById('report-date').value = item.date || new Date().toISOString().split('T')[0];
                document.getElementById('report-followers').value = item.followers === '-' ? '' : (item.followers || '');
                document.getElementById('report-tayangan').value = item.tayangan === '-' ? '' : item.tayangan;
                document.getElementById('report-jangkauan').value = item.jangkauan === '-' ? '' : item.jangkauan;
                document.getElementById('report-engagement').value = item.engagement === '-%' ? '' : item.engagement;
            }
        } else {
            modalTitle.innerText = 'Tambah Laporan Konten';
            document.getElementById('report-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('report-period').value = currentPeriod === 'bulanan' ? 'bulanan' : 'mingguan';
        }

        modal.classList.remove('hidden');
    }

    function closeReportModal() {
        document.getElementById('modal-report').classList.add('hidden');
    }

    // Sync from Content Planner or Ide Konten
    function applySyncSelection(val) {
        if (!val) return;

        if (val.startsWith('sched_')) {
            const schedId = parseInt(val.replace('sched_', ''));
            const s = schedulesSource.find(item => item.id === schedId);
            if (s) {
                document.getElementById('report-title').value = s.title;
                document.getElementById('report-platform').value = s.platform;
                document.getElementById('report-tag').value = s.tag || ucfirst(s.platform);
                document.getElementById('report-description').value = s.description ? `Jadwal: ${s.description}` : `Diposting dari Content Planner (${s.time})`;
                if (s.date) document.getElementById('report-date').value = s.date;
                showToast('Sinkronisasi', `Data dari jadwal "${s.title}" berhasil disalin.`);
            }
        } else if (val.startsWith('idea_')) {
            const ideaId = parseInt(val.replace('idea_', ''));
            const idea = ideasSource.find(item => item.id === ideaId);
            if (idea) {
                document.getElementById('report-title').value = idea.title;
                document.getElementById('report-platform').value = idea.platform;
                document.getElementById('report-tag').value = idea.category || ucfirst(idea.platform);
                document.getElementById('report-description').value = idea.description ? `Ide: ${idea.description}` : `Diposting dari Ide Konten`;
                showToast('Sinkronisasi', `Data dari ide "${idea.title}" berhasil disalin.`);
            }
        }
    }

    // Submit Add/Edit Report
    async function handleReportSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('report-id').value;
        const btnSave = document.getElementById('btn-save-report');
        btnSave.disabled = true;
        btnSave.innerText = 'Menyimpan...';

        const payload = {
            title: document.getElementById('report-title').value,
            description: document.getElementById('report-description').value,
            platform: document.getElementById('report-platform').value,
            tag: document.getElementById('report-tag').value,
            period: document.getElementById('report-period').value,
            date: document.getElementById('report-date').value,
            followers: document.getElementById('report-followers').value,
            tayangan: document.getElementById('report-tayangan').value,
            jangkauan: document.getElementById('report-jangkauan').value,
            engagement: document.getElementById('report-engagement').value,
        };

        const url = id ? `/laporan-sosmed/${id}` : '/laporan-sosmed';
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                if (data.reports) {
                    reportsData = data.reports;
                } else {
                    // Refresh from API
                    await reloadReports();
                }
                closeReportModal();
                renderDashboard();
                showToast('Sukses', id ? 'Laporan berhasil diperbarui!' : 'Laporan baru berhasil ditambahkan!');
            } else {
                showToast('Gagal', data.message || 'Terjadi kesalahan saat menyimpan', true);
            }
        } catch (err) {
            console.error(err);
            showToast('Kesalahan', 'Gagal menghubungi server', true);
        } finally {
            btnSave.disabled = false;
            btnSave.innerText = 'Simpan Laporan';
        }
    }

    // Delete Modal
    function openDeleteModal(id, title) {
        reportToDeleteId = id;
        document.getElementById('delete-item-title').innerText = title;
        document.getElementById('modal-delete').classList.remove('hidden');
    }

    function closeDeleteModal() {
        reportToDeleteId = null;
        document.getElementById('modal-delete').classList.add('hidden');
    }

    async function confirmDeleteReport() {
        if (!reportToDeleteId) return;
        const btnDelete = document.getElementById('btn-confirm-delete');
        btnDelete.disabled = true;
        btnDelete.innerText = 'Menghapus...';

        try {
            const res = await fetch(`/laporan-sosmed/${reportToDeleteId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                if (data.reports) {
                    reportsData = data.reports;
                } else {
                    reportsData = reportsData.filter(r => r.id != reportToDeleteId);
                }
                closeDeleteModal();
                renderDashboard();
                showToast('Dihapus', 'Laporan berhasil dihapus!');
            } else {
                showToast('Gagal', data.message || 'Gagal menghapus laporan', true);
            }
        } catch (err) {
            console.error(err);
            showToast('Kesalahan', 'Terjadi kesalahan koneksi', true);
        } finally {
            btnDelete.disabled = false;
            btnDelete.innerText = 'Ya, Hapus';
        }
    }

    async function reloadReports() {
        try {
            const res = await fetch('/api/reports');
            const result = await res.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                reportsData = result.data;
            }
        } catch(e) {
            console.error(e);
        }
    }

    // Filtering & Table Render
    function filterReports() {
        currentPlatformFilter = document.getElementById('table-platform-filter').value;
        currentSearch = document.getElementById('report-search').value.toLowerCase().trim();
        renderDashboard();
    }

    function renderReportsTable() {
        const tbody = document.getElementById('reports-table-body');
        const emptyState = document.getElementById('reports-empty-state');
        const paginationInfo = document.getElementById('pagination-info');

        let filtered = getSelectedReports().filter(item => {
            // Filter Search
            if (currentSearch) {
                const title = (item.title || '').toLowerCase();
                const desc = (item.description || '').toLowerCase();
                const tag = (item.tag || '').toLowerCase();
                if (!title.includes(currentSearch) && !desc.includes(currentSearch) && !tag.includes(currentSearch)) {
                    return false;
                }
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
            paginationInfo.innerText = `Menampilkan 0 dari ${getSelectedReports().length} konten`;
            return;
        }

        emptyState.classList.add('hidden');
        paginationInfo.innerText = `Menampilkan ${filtered.length} dari ${getSelectedReports().length} konten`;

        let html = '';
        filtered.forEach(item => {
            const p = (item.platform || '').toLowerCase();
            let platformBadge = '';
            let avatarGradient = 'from-amber-100 to-orange-100 border-amber-200 text-amber-700';

            if (p === 'instagram') {
                platformBadge = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-pink-50 text-pink-600 border border-pink-100"><span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span>Instagram</span>`;
                avatarGradient = 'from-pink-100 to-rose-100 border-pink-200/60 text-pink-700';
            } else if (p === 'tiktok') {
                platformBadge = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200"><span class="w-1.5 h-1.5 rounded-full bg-gray-900"></span>TikTok</span>`;
                avatarGradient = 'from-slate-100 to-gray-200 border-gray-200 text-gray-800';
            } else if (p === 'youtube') {
                platformBadge = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-100"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>YouTube</span>`;
                avatarGradient = 'from-red-100 to-rose-100 border-red-200 text-red-700';
            } else {
                platformBadge = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"><span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>Threads</span>`;
                avatarGradient = 'from-gray-100 to-slate-200 border-slate-200 text-slate-700';
            }

            const tagText = item.tag || (p === 'instagram' ? 'Reels' : (p === 'youtube' ? 'Vlog' : 'Post'));
            const followersText = item.followers || '-';
            const tayanganText = item.tayangan || '-';
            const jangkauanText = item.jangkauan || '-';
            const engagementText = item.engagement || '-%';

            html += `
            <tr class="hover:bg-gray-50/70 transition-colors group">
                <td class="py-4 pr-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br ${avatarGradient} border flex-shrink-0 flex items-center justify-center font-bold text-xs shadow-xs">
                            ${escapeHtml(tagText.substring(0, 7))}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-gray-900 truncate max-w-xs sm:max-w-md">${escapeHtml(item.title)}</div>
                            <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-2">
                                <span>${escapeHtml(item.description || 'Diposting baru saja')}</span>
                                ${item.date ? `<span>•</span><span>${item.date}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-4 whitespace-nowrap">
                    ${platformBadge}
                </td>
                <td class="py-4 px-4 text-sm font-medium text-gray-600 whitespace-nowrap">${escapeHtml(followersText)}</td>
                <td class="py-4 px-4 text-sm font-medium text-gray-600 whitespace-nowrap">${escapeHtml(tayanganText)}</td>
                <td class="py-4 px-4 text-sm font-medium text-gray-600 whitespace-nowrap">${escapeHtml(jangkauanText)}</td>
                <td class="py-4 px-4 text-sm font-medium text-emerald-600 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        ${escapeHtml(engagementText)}
                    </span>
                </td>
                <td class="py-4 pl-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" onclick="openReportModal(${item.id})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition" title="Edit Laporan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </button>
                        <button type="button" onclick="openDeleteModal(${item.id}, '${escapeQuote(item.title)}')" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Hapus Laporan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
    }

    // Platform Distribution Updates
    function updateDistribution() {
        const counts = { instagram: 0, tiktok: 0, youtube: 0, threads: 0 };
        const selected = getSelectedReports();
        selected.forEach(r => {
            const p = (r.platform || '').toLowerCase();
            if (counts[p] !== undefined) counts[p]++;
        });

        const total = selected.length;
        const platforms = ['instagram', 'tiktok', 'youtube', 'threads'];
        let maxCount = -1;
        let activePlatform = '-';

        platforms.forEach(p => {
            const pct = total > 0 ? Math.round((counts[p] / total) * 100) : 0;
            const pctEl = document.getElementById(`dist-${p}-pct`);
            const barEl = document.getElementById(`dist-${p}-bar`);
            if (pctEl) pctEl.innerText = total > 0 ? `${pct}%` : '-%';
            if (barEl) barEl.style.width = `${pct}%`;

            if (counts[p] > maxCount && counts[p] > 0) {
                maxCount = counts[p];
                activePlatform = ucfirst(p);
            }
        });

        const activeEl = document.getElementById('platform-teraktif');
        if (activeEl) {
            activeEl.innerText = activePlatform;
            if (activePlatform !== '-') {
                activeEl.className = "inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200";
            } else {
                activeEl.className = "inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600";
            }
        }
    }

    // Chart.js initialization & update
    function initChart() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const labels = currentPeriod === 'mingguan' ? ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] : ['Mgg 1', 'Mgg 2', 'Mgg 3', 'Mgg 4'];

        performanceChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Jangkauan',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(125, 211, 252, 0.75)',
                        borderColor: 'rgba(56, 189, 248, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.7,
                    },
                    {
                        label: 'Tayangan',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(29, 78, 216, 0.85)',
                        borderColor: 'rgba(30, 58, 138, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.7,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 12 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(243, 244, 246, 1)' },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 },
                            callback: value => Number(value).toLocaleString('id-ID')
                        },
                    }
                }
            }
        });
    }

    function updateChart() {
        if (!performanceChartInstance) return;
        const from = activeDateFrom;
        const to = activeDateTo;
        if (!from || !to || from > to) {
            performanceChartInstance.data.labels = [];
            performanceChartInstance.data.datasets.forEach(dataset => dataset.data = []);
            performanceChartInstance.update();
            return;
        }

        const start = new Date(`${from}T00:00:00Z`);
        const end = new Date(`${to}T00:00:00Z`);
        const dayCount = Math.floor((end - start) / 86400000) + 1;
        const groupByMonth = dayCount > 31;
        const buckets = new Map();
        const cursor = groupByMonth
            ? new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth(), 1))
            : new Date(start);

        while (cursor <= end) {
            const date = cursor.toISOString().slice(0, 10);
            const key = groupByMonth ? date.slice(0, 7) : date;
            buckets.set(key, { reach: 0, impressions: 0 });
            if (groupByMonth) cursor.setUTCMonth(cursor.getUTCMonth() + 1);
            else cursor.setUTCDate(cursor.getUTCDate() + 1);
        }

        getSelectedReports().forEach(report => {
            if (!report.date) return;
            const key = groupByMonth ? report.date.slice(0, 7) : report.date;
            const bucket = buckets.get(key);
            if (!bucket) return;
            bucket.reach += parseMetric(report.jangkauan) || 0;
            bucket.impressions += parseMetric(report.tayangan) || 0;
        });

        const formatDate = key => {
            const date = new Date(`${key}${groupByMonth ? '-01' : ''}T00:00:00`);
            return new Intl.DateTimeFormat('id-ID', groupByMonth
                ? { month: 'short', year: 'numeric' }
                : { day: 'numeric', month: 'short' }).format(date);
        };
        const entries = [...buckets.entries()];
        performanceChartInstance.data.labels = entries.map(([key]) => formatDate(key));
        performanceChartInstance.data.datasets[0].data = entries.map(([, values]) => values.reach);
        performanceChartInstance.data.datasets[1].data = entries.map(([, values]) => values.impressions);
        performanceChartInstance.update();
    }

    // Helper utilities
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeQuote(text) {
        if (!text) return '';
        return String(text).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', function() {
        initChart();

        // Listen for platform filter in URL query if any (e.g. ?platform=instagram)
        const urlParams = new URLSearchParams(window.location.search);
        const queryPlatform = urlParams.get('platform');
        if (queryPlatform) {
            const filterEl = document.getElementById('table-platform-filter');
            if (filterEl) {
                filterEl.value = queryPlatform.toLowerCase();
                currentPlatformFilter = queryPlatform.toLowerCase();
            }
        }

        setPeriod('mingguan');
    });
</script>
@endpush
@endsection
