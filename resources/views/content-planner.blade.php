@extends('layouts.app')

@section('title', 'Content Planner')

@section('content')
<div class="space-y-8" id="content-planner-app">

    {{-- Alert Toast Notification --}}
    <div id="toast-notification" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-gray-700">
        <div id="toast-icon" class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <div>
            <p id="toast-title" class="text-sm font-semibold">Berhasil</p>
            <p id="toast-message" class="text-xs text-gray-300">Jadwal berhasil diperbarui.</p>
        </div>
    </div>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Content Planner</h2>
            <p class="mt-1 text-sm text-gray-500">Orchestrate and schedule your multi-platform content ecosystem with fluid drag-and-drop precision.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            {{-- View Switcher Buttons --}}
            <div class="inline-flex rounded-xl border border-gray-200 bg-gray-100 p-1 shadow-xs">
                <button type="button" onclick="switchView('monthly')" id="btn-view-monthly" class="px-4 py-2 text-sm font-semibold rounded-lg bg-white text-gray-900 shadow-sm transition">
                    Monthly
                </button>
                <button type="button" onclick="switchView('weekly')" id="btn-view-weekly" class="px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition">
                    Weekly
                </button>
                <button type="button" onclick="switchView('list')" id="btn-view-list" class="px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition">
                    List
                </button>
            </div>

            {{-- New Schedule Button --}}
            <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-blue-500/25 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Schedule
            </button>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
        {{-- Scheduled This Month --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 flex items-center gap-4 shadow-sm">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" id="stat-scheduled">-</p>
                <p class="text-xs font-medium text-gray-500">Scheduled This Month</p>
            </div>
        </div>

        {{-- Pending Drafts --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 flex items-center gap-4 shadow-sm">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" id="stat-drafts">-</p>
                <p class="text-xs font-medium text-gray-500">Pending Drafts</p>
            </div>
        </div>

        {{-- Published Successfully --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 flex items-center gap-4 shadow-sm">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900" id="stat-published">-</p>
                <p class="text-xs font-medium text-gray-500">Published Successfully</p>
            </div>
        </div>

        {{-- Active Calendar Navigator --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 flex flex-col justify-between shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Active Calendar</p>
                    <p class="text-sm font-bold text-gray-900 mt-0.5"><span id="active-month-label">October 2023</span> • <span class="text-xs text-gray-400 font-normal">GMT+7</span></p>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="navigateMonth(-1)" class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-500 active:scale-95 transition" title="Bulan Sebelumnya">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" onclick="navigateMonth(1)" class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-500 active:scale-95 transition" title="Bulan Selanjutnya">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Platform Bar --}}
    <div class="flex items-center justify-between flex-wrap gap-3 bg-white p-3.5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <span class="text-xs font-semibold text-gray-400 uppercase mr-1">Filter:</span>
            <button type="button" onclick="filterPlatform('all')" id="filter-all" class="platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 text-white shadow-xs transition">
                Semua Platform
            </button>
            <button type="button" onclick="filterPlatform('instagram')" id="filter-instagram" class="platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-500 to-pink-500"></span>
                Instagram
            </button>
            <button type="button" onclick="filterPlatform('tiktok')" id="filter-tiktok" class="platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-gray-900"></span>
                TikTok
            </button>
            <button type="button" onclick="filterPlatform('youtube')" id="filter-youtube" class="platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                YouTube
            </button>
            <button type="button" onclick="filterPlatform('threads')" id="filter-threads" class="platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-gray-700"></span>
                Threads
            </button>
        </div>

        <div class="text-xs text-gray-400">
            Total Jadwal: <span id="total-schedules-count" class="font-bold text-gray-700">0</span> postingan
        </div>
    </div>

    {{-- VIEW 1: MONTHLY CALENDAR VIEW --}}
    <div id="view-monthly" class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-sm">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50/50">
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">MON</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">TUE</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">WED</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">THU</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">FRI</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">SAT</div>
            <div class="py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">SUN</div>
        </div>

        {{-- Dynamic Day Cells Container --}}
        <div id="calendar-grid-cells" class="grid grid-cols-7">
            {{-- Injected dynamically by renderCalendar() --}}
        </div>
    </div>

    {{-- VIEW 2: WEEKLY VIEW (HIDDEN BY DEFAULT) --}}
    <div id="view-weekly" class="hidden bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Weekly Content Pipeline</h3>
                <p class="text-xs text-gray-400" id="weekly-range-label">Minggu ini</p>
            </div>
            <button type="button" onclick="openAddModal()" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                + Tambah Konten Minggu Ini
            </button>
        </div>
        <div id="weekly-columns-container" class="grid grid-cols-1 md:grid-cols-7 gap-3">
            {{-- Injected dynamically by renderWeeklyView() --}}
        </div>
    </div>

    {{-- VIEW 3: LIST VIEW (HIDDEN BY DEFAULT) --}}
    <div id="view-list" class="hidden bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Daftar Semua Jadwal Konten</h3>
                <p class="text-xs text-gray-400">Kelola, edit, dan hapus seluruh konten media sosial</p>
            </div>
            <button type="button" onclick="openAddModal()" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition shadow-xs">
                + Jadwal Baru
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-3">Platform</th>
                        <th class="py-3 px-3">Judul Konten</th>
                        <th class="py-3 px-3">Tanggal &amp; Waktu</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="list-table-body" class="divide-y divide-gray-50">
                    {{-- Injected dynamically by renderListView() --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Upcoming Queue & Details --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Upcoming Queue &amp; Details</h3>
                <p class="text-xs text-gray-400 font-medium">Showing <span id="queue-count">0</span> pending posts</p>
            </div>
            <div class="text-xs text-gray-400">
                Klik kartu untuk edit atau hapus jadwal
            </div>
        </div>

        <div id="upcoming-queue-cards" class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Dynamic cards injected by renderUpcomingQueue() --}}
        </div>
    </div>

    {{-- MODAL: TAMBAH / EDIT JADWAL --}}
    <div id="schedule-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div>
                    <h3 id="modal-title" class="text-lg font-bold text-gray-900">Tambah Jadwal Baru</h3>
                    <p class="text-xs text-gray-400">Atur konten multi-platform Anda</p>
                </div>
                <button type="button" onclick="closeModal()" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="schedule-form" onsubmit="handleFormSubmit(event)" class="mt-5 space-y-4">
                <input type="hidden" id="form-schedule-id" value="">

                {{-- Platform Selection --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Platform</label>
                    <div class="grid grid-cols-4 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="form-platform" value="instagram" class="peer sr-only" checked>
                            <div class="p-2.5 rounded-xl border border-gray-200 text-center text-xs font-semibold peer-checked:bg-pink-50 peer-checked:border-pink-500 peer-checked:text-pink-600 hover:bg-gray-50 transition">
                                Instagram
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="form-platform" value="tiktok" class="peer sr-only">
                            <div class="p-2.5 rounded-xl border border-gray-200 text-center text-xs font-semibold peer-checked:bg-gray-900 peer-checked:border-gray-900 peer-checked:text-white hover:bg-gray-50 transition">
                                TikTok
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="form-platform" value="youtube" class="peer sr-only">
                            <div class="p-2.5 rounded-xl border border-gray-200 text-center text-xs font-semibold peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-600 hover:bg-gray-50 transition">
                                YouTube
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="form-platform" value="threads" class="peer sr-only">
                            <div class="p-2.5 rounded-xl border border-gray-200 text-center text-xs font-semibold peer-checked:bg-gray-100 peer-checked:border-gray-700 peer-checked:text-gray-900 hover:bg-gray-50 transition">
                                Threads
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Judul Konten <span class="text-rose-500">*</span></label>
                    <input type="text" id="form-title" required placeholder="Contoh: POV: You automated your content workflow" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Tag / Format --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Format / Tag</label>
                    <input type="text" id="form-tag" placeholder="Contoh: IG Reel, Tutorial, Carousel, Masterclass" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Date & Time --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" id="form-date" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Waktu / Jam <span class="text-rose-500">*</span></label>
                        <input type="time" id="form-time" required value="10:00" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Status Postingan</label>
                    <select id="form-status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white font-medium">
                        <option value="scheduled">Scheduled (Terjadwal)</option>
                        <option value="draft">Draft (Konsep)</option>
                        <option value="published">Published (Sudah Terbit)</option>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Deskripsi / Catatan Konten</label>
                    <textarea id="form-description" rows="3" placeholder="Tulis catatan ide atau ringkasan script..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center justify-between gap-2 pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-delete-in-modal" onclick="confirmDeleteFromModal()" class="hidden px-4 py-2 text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Jadwal
                        </button>
                        <button type="button" id="btn-sync-to-report-modal" onclick="syncCurrentScheduleToReport()" class="hidden px-4 py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Jadikan Laporan Sosmed
                        </button>
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-semibold transition">
                            Batal
                        </button>
                        <button type="submit" id="btn-submit-modal" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-lg shadow-blue-500/25 transition">
                            Simpan Jadwal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: KONFIRMASI HAPUS --}}
    <div id="delete-confirm-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 text-center">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h4 class="text-base font-bold text-gray-900 mb-1">Hapus Jadwal Ini?</h4>
            <p id="delete-schedule-title" class="text-xs text-gray-500 mb-6 font-medium line-clamp-2">"Judul postingan"</p>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-semibold transition">
                    Batal
                </button>
                <button type="button" onclick="executeDelete()" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-lg shadow-rose-500/25 transition">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Initial schedules data from server
    let schedules = @json($schedules ?? []);
    const today = new Date();
    let currentYear = today.getFullYear();
    let currentMonth = today.getMonth();
    let activeView = 'monthly';
    let activeFilter = 'all';
    let scheduleToDeleteId = null;
    const publishingScheduleIds = new Set();

    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    document.addEventListener('DOMContentLoaded', () => {
        renderAll();
    });

    function renderAll() {
        updateCalendarLabel();
        updateScheduleStats();
        renderCalendar();
        renderWeeklyView();
        renderListView();
        renderUpcomingQueue();
        updateCountBadge();
    }

    function updateScheduleStats() {
        const monthPrefix = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;
        const filteredSchedules = activeFilter === 'all'
            ? schedules
            : schedules.filter(schedule => (schedule.platform || '').toLowerCase() === activeFilter);
        const scheduledThisMonth = filteredSchedules.filter(schedule =>
            schedule.status === 'scheduled' && (schedule.date || '').startsWith(monthPrefix)
        ).length;
        const pendingDrafts = filteredSchedules.filter(schedule => schedule.status === 'draft').length;
        const publishedSuccessfully = filteredSchedules.filter(schedule => schedule.status === 'published').length;

        document.getElementById('stat-scheduled').textContent = scheduledThisMonth;
        document.getElementById('stat-drafts').textContent = pendingDrafts;
        document.getElementById('stat-published').textContent = publishedSuccessfully;
    }

    // --- VIEW SWITCHING ---
    function switchView(view) {
        activeView = view;
        ['monthly', 'weekly', 'list'].forEach(v => {
            const el = document.getElementById(`view-${v}`);
            const btn = document.getElementById(`btn-view-${v}`);
            if (v === view) {
                el.classList.remove('hidden');
                btn.className = "px-4 py-2 text-sm font-semibold rounded-lg bg-white text-gray-900 shadow-sm transition";
            } else {
                el.classList.add('hidden');
                btn.className = "px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition";
            }
        });
    }

    // --- MONTH NAVIGATION ---
    function navigateMonth(direction) {
        currentMonth += direction;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        } else if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        updateCalendarLabel();
        updateScheduleStats();
        renderCalendar();
        renderWeeklyView();
    }

    function updateCalendarLabel() {
        const label = document.getElementById('active-month-label');
        if (label) {
            label.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        }
    }

    // --- PLATFORM FILTER ---
    function filterPlatform(platform) {
        activeFilter = platform;
        document.querySelectorAll('.platform-filter-pill').forEach(btn => {
            btn.className = "platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition";
        });
        const activeBtn = document.getElementById(`filter-${platform}`);
        if (activeBtn) {
            activeBtn.className = "platform-filter-pill px-3 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 text-white shadow-xs transition";
        }
        renderCalendar();
        renderWeeklyView();
        renderListView();
        renderUpcomingQueue();
        updateScheduleStats();
    }

    function getFilteredSchedules() {
        if (activeFilter === 'all') return schedules;
        return schedules.filter(s => s.platform.toLowerCase() === activeFilter.toLowerCase());
    }

    // --- RENDER MONTHLY CALENDAR ---
    function renderCalendar() {
        const grid = document.getElementById('calendar-grid-cells');
        if (!grid) return;
        grid.innerHTML = '';

        const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        
        // Convert JS Sunday=0 to Monday=0: (day + 6) % 7
        const startDayOffset = (firstDayOfMonth.getDay() + 6) % 7;
        
        // Prev month days
        const prevMonthLastDate = new Date(currentYear, currentMonth, 0).getDate();
        for (let i = startDayOffset - 1; i >= 0; i--) {
            const dayNum = prevMonthLastDate - i;
            const cell = document.createElement('div');
            cell.className = "min-h-[115px] border-b border-r border-gray-100 p-2.5 bg-gray-50/40";
            cell.innerHTML = `<span class="text-xs font-semibold text-gray-300">${dayNum}</span>`;
            grid.appendChild(cell);
        }

        // Current month days
        const filtered = getFilteredSchedules();
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const daySchedules = filtered.filter(s => s.date === dateStr);
            const isHighlighted = (currentYear === 2023 && currentMonth === 9 && d === 11);

            const cell = document.createElement('div');
            cell.className = `min-h-[115px] border-b border-r border-gray-100 p-2.5 relative group transition ${
                isHighlighted ? 'bg-blue-50/40 ring-2 ring-blue-500 ring-inset rounded-xl' : 'bg-white hover:bg-gray-50/80'
            }`;

            // Cell header
            let cellHtml = `
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-bold ${isHighlighted ? 'text-blue-600' : 'text-gray-700'}">${d}</span>
                    <div class="flex items-center gap-1">
                        ${isHighlighted ? '<span class="w-2 h-2 rounded-full bg-blue-500"></span>' : ''}
                        <button type="button" onclick="openAddModalForDate('${dateStr}')" title="Tambah Jadwal di tanggal ini" class="opacity-0 group-hover:opacity-100 w-5 h-5 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center text-xs font-bold transition">
                            +
                        </button>
                    </div>
                </div>
            `;

            // Render schedule pills inside cell
            cellHtml += `<div class="space-y-1.5">`;
            daySchedules.forEach(item => {
                const colors = getPlatformColorStyles(item.platform, item.status);
                cellHtml += `
                    <div onclick="openEditModal(${item.id})" class="p-1.5 rounded-lg text-[10px] space-y-0.5 shadow-2xs cursor-pointer hover:scale-[1.02] transition-transform ${colors.containerClass}" title="${item.title} (${item.time})">
                        <div class="flex items-center justify-between font-bold ${colors.textClass}">
                            <span class="truncate">${item.tag || item.platform}</span>
                            <span class="font-normal opacity-75">${item.time}</span>
                        </div>
                        <p class="truncate font-medium text-gray-800">${item.title}</p>
                        ${item.status !== 'published' ? `<button type="button" onclick="event.stopPropagation(); markSchedulePublished(${item.id})" class="mt-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">Selesai posting</button>` : ''}
                    </div>
                `;
            });
            cellHtml += `</div>`;

            cell.innerHTML = cellHtml;
            grid.appendChild(cell);
        }

        // Trailing cells to fill grid (multiple of 7)
        const totalRendered = startDayOffset + daysInMonth;
        const remaining = (7 - (totalRendered % 7)) % 7;
        for (let i = 1; i <= remaining; i++) {
            const cell = document.createElement('div');
            cell.className = "min-h-[115px] border-b border-r border-gray-100 p-2.5 bg-gray-50/40";
            cell.innerHTML = `<span class="text-xs font-semibold text-gray-300">${i}</span>`;
            grid.appendChild(cell);
        }
    }

    // --- RENDER WEEKLY VIEW ---
    function renderWeeklyView() {
        const container = document.getElementById('weekly-columns-container');
        if (!container) return;
        container.innerHTML = '';

        const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        const filtered = getFilteredSchedules();

        // Sample week from 9th to 15th
        days.forEach((dayName, idx) => {
            const dayNum = 9 + idx;
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
            const dayItems = filtered.filter(s => s.date === dateStr);

            const col = document.createElement('div');
            col.className = "bg-gray-50/70 rounded-xl p-3 border border-gray-100 flex flex-col justify-between min-h-[220px]";

            let colHtml = `
                <div>
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-700">${dayName}</span>
                        <span class="text-xs text-gray-400 font-semibold">${dayNum}</span>
                    </div>
                    <div class="space-y-2">
            `;

            if (dayItems.length === 0) {
                colHtml += `<p class="text-[11px] text-gray-400 italic py-4 text-center">Tidak ada jadwal</p>`;
            } else {
                dayItems.forEach(item => {
                    const colors = getPlatformColorStyles(item.platform, item.status);
                    colHtml += `
                        <div onclick="openEditModal(${item.id})" class="p-2.5 bg-white rounded-xl border border-gray-200/80 shadow-xs cursor-pointer hover:border-blue-300 transition">
                            <div class="flex items-center justify-between text-[10px] mb-1">
                                <span class="font-bold ${colors.textClass}">${item.platform.toUpperCase()}</span>
                                <span class="text-gray-400">${item.time}</span>
                            </div>
                            <h5 class="text-xs font-bold text-gray-900 leading-snug line-clamp-2">${item.title}</h5>
                            ${item.status !== 'published' ? `<button type="button" onclick="event.stopPropagation(); markSchedulePublished(${item.id})" class="mt-2 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-semibold hover:bg-emerald-100 transition">Selesai posting</button>` : ''}
                        </div>
                    `;
                });
            }

            colHtml += `
                    </div>
                </div>
                <button type="button" onclick="openAddModalForDate('${dateStr}')" class="mt-2 w-full py-1 text-center text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    + Jadwal
                </button>
            `;

            col.innerHTML = colHtml;
            container.appendChild(col);
        });
    }

    // --- RENDER LIST VIEW ---
    function renderListView() {
        const tbody = document.getElementById('list-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        const filtered = getFilteredSchedules();
        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-gray-400 text-xs">Belum ada jadwal yang sesuai filter</td></tr>`;
            return;
        }

        filtered.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50/70 transition";
            const badge = getStatusBadge(item.status);

            tr.innerHTML = `
                <td class="py-3 px-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold capitalize bg-gray-100 text-gray-800">
                        ${getPlatformDot(item.platform)}
                        ${item.platform}
                    </span>
                </td>
                <td class="py-3 px-3 font-semibold text-gray-900">
                    <div class="truncate max-w-xs">${item.title}</div>
                    <div class="text-xs font-normal text-gray-400 truncate max-w-xs">${item.description || '-'}</div>
                </td>
                <td class="py-3 px-3 text-xs text-gray-600">
                    ${item.date} • <span class="font-bold">${item.time}</span>
                </td>
                <td class="py-3 px-3">
                    ${badge}
                </td>
                <td class="py-3 px-3 text-right">
                    <div class="inline-flex items-center gap-1">
                        ${item.status !== 'published' ? `<button type="button" onclick="markSchedulePublished(${item.id})" class="px-2 py-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition whitespace-nowrap" title="Tandai selesai posting">Selesai posting</button>` : ''}
                        <button type="button" onclick="sendScheduleToReport(${item.id})" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition" title="Kirim ke Laporan Sosmed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </button>
                        <button type="button" onclick="openEditModal(${item.id})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" onclick="promptDelete(${item.id})" class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- RENDER UPCOMING QUEUE CARDS ---
    function renderUpcomingQueue() {
        const container = document.getElementById('upcoming-queue-cards');
        const countSpan = document.getElementById('queue-count');
        if (!container) return;
        container.innerHTML = '';

        const filtered = getFilteredSchedules();
        if (countSpan) countSpan.textContent = filtered.length;

        if (filtered.length === 0) {
            container.innerHTML = `<div class="col-span-3 text-center py-10 text-gray-400 text-sm">Tidak ada jadwal konten dalam antrean.</div>`;
            return;
        }

        // Display top 3 or all filtered
        const itemsToShow = filtered.slice(0, 3);
        itemsToShow.forEach(item => {
            const card = document.createElement('div');
            card.className = "bg-white rounded-2xl border border-gray-200/80 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col justify-between";

            const headerGradient = getCardHeaderGradient(item.platform);
            const statusBadge = getStatusBadge(item.status);

            card.innerHTML = `
                <div>
                    <div class="h-44 ${headerGradient} relative p-4 flex flex-col justify-between text-white">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 backdrop-blur text-white text-xs font-semibold rounded-full border border-white/10">
                                ${getPlatformIcon(item.platform)}
                                ${item.platform.toUpperCase()}
                            </span>
                            ${statusBadge}
                        </div>
                        <div class="flex items-center justify-center py-4">
                            <div class="px-4 py-2 bg-white/10 backdrop-blur-xs rounded-xl border border-white/20 text-white/70 text-xs font-medium">
                                ${item.tag || item.platform}
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <h4 onclick="openEditModal(${item.id})" class="font-bold text-gray-900 text-sm mb-1.5 hover:text-blue-600 cursor-pointer transition">${item.title}</h4>
                        <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">${item.description || 'Tidak ada deskripsi tambahan.'}</p>
                    </div>
                </div>
                <div class="px-5 pb-4 pt-2 flex items-center justify-between border-t border-gray-100 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5 font-medium text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ${item.date} • ${item.time}
                    </span>
                    <div class="flex items-center gap-1">
                        ${item.status !== 'published' ? `<button type="button" onclick="markSchedulePublished(${item.id})" class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-semibold hover:bg-emerald-100 transition whitespace-nowrap">Selesai posting</button>` : ''}
                        <button type="button" onclick="sendScheduleToReport(${item.id})" class="p-1.5 rounded-lg hover:bg-emerald-50 text-gray-400 hover:text-emerald-600 transition" title="Kirim ke Laporan Sosmed">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </button>
                        <button type="button" onclick="openEditModal(${item.id})" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-blue-600 transition" title="Edit Jadwal">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" onclick="promptDelete(${item.id})" class="p-1.5 rounded-lg hover:bg-rose-50 text-gray-400 hover:text-rose-600 transition" title="Hapus Jadwal">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function updateCountBadge() {
        const countEl = document.getElementById('total-schedules-count');
        if (countEl) countEl.textContent = schedules.length;
    }

    // --- MODAL FUNCTIONS ---
    function openAddModal() {
        document.getElementById('modal-title').textContent = "Tambah Jadwal Baru";
        document.getElementById('form-schedule-id').value = "";
        document.getElementById('form-title').value = "";
        document.getElementById('form-tag').value = "";
        document.getElementById('form-date').value = "2023-10-15";
        document.getElementById('form-time').value = "10:00";
        document.getElementById('form-status').value = "scheduled";
        document.getElementById('form-description').value = "";
        document.getElementById('btn-submit-modal').textContent = "Simpan Jadwal";
        document.getElementById('btn-delete-in-modal').classList.add('hidden');
        const syncModalBtn = document.getElementById('btn-sync-to-report-modal');
        if (syncModalBtn) syncModalBtn.classList.add('hidden');

        // Reset platform radio
        const firstRadio = document.querySelector('input[name="form-platform"][value="instagram"]');
        if (firstRadio) firstRadio.checked = true;

        document.getElementById('schedule-modal').classList.remove('hidden');
    }

    function openAddModalForDate(dateStr) {
        openAddModal();
        document.getElementById('form-date').value = dateStr;
    }

    function openEditModal(id) {
        const item = schedules.find(s => s.id == id);
        if (!item) return;

        document.getElementById('modal-title').textContent = "Edit Jadwal Konten";
        document.getElementById('form-schedule-id').value = item.id;
        document.getElementById('form-title').value = item.title;
        document.getElementById('form-tag').value = item.tag || "";
        document.getElementById('form-date').value = item.date;
        document.getElementById('form-time').value = item.time;
        document.getElementById('form-status').value = item.status;
        document.getElementById('form-description').value = item.description || "";
        document.getElementById('btn-submit-modal').textContent = "Simpan Perubahan";
        document.getElementById('btn-delete-in-modal').classList.remove('hidden');
        const syncModalBtn = document.getElementById('btn-sync-to-report-modal');
        if (syncModalBtn) syncModalBtn.classList.remove('hidden');

        const radio = document.querySelector(`input[name="form-platform"][value="${item.platform.toLowerCase()}"]`);
        if (radio) radio.checked = true;

        document.getElementById('schedule-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('schedule-modal').classList.add('hidden');
    }

    async function markSchedulePublished(id) {
        const item = schedules.find(schedule => schedule.id == id);
        if (!item || item.status === 'published' || publishingScheduleIds.has(id)) return;

        publishingScheduleIds.add(id);
        try {
            const response = await fetch(`/content-planner/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    platform: item.platform,
                    title: item.title,
                    tag: item.tag || '',
                    date: item.date,
                    time: item.time,
                    status: 'published',
                    description: item.description || ''
                })
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success' || !Array.isArray(result.schedules)) {
                throw new Error(result.message || 'Status posting gagal diperbarui.');
            }

            schedules = result.schedules;
            renderAll();
            showToast('Posting selesai', `Jadwal "${item.title}" berhasil ditandai Published.`);
        } catch (error) {
            console.error(error);
            showToast('Gagal memperbarui status', error.message || 'Silakan coba lagi.', true);
        } finally {
            publishingScheduleIds.delete(id);
        }
    }

    // --- SYNC TO LAPORAN SOSMED ---
    async function sendScheduleToReport(id) {
        const s = schedules.find(item => item.id == id);
        if (!s) return;

        try {
            const res = await fetch(`/laporan-sosmed/from-schedule/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({})
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                showToast('Tersinkronisasi', `Jadwal "${s.title}" berhasil dijadikan Laporan Sosmed!`);
            } else {
                showToast('Gagal', data.message || 'Gagal sinkron ke Laporan Sosmed', true);
            }
        } catch(e) {
            console.error(e);
            showToast('Kesalahan', 'Gagal menghubungi server', true);
        }
    }

    async function syncCurrentScheduleToReport() {
        const id = document.getElementById('form-schedule-id').value;
        if (!id) return;
        closeModal();
        await sendScheduleToReport(id);
    }

    // --- FORM SUBMIT (ADD / EDIT) ---
    async function handleFormSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('form-schedule-id').value;
        const platform = document.querySelector('input[name="form-platform"]:checked').value;
        const title = document.getElementById('form-title').value;
        const tag = document.getElementById('form-tag').value;
        const date = document.getElementById('form-date').value;
        const time = document.getElementById('form-time').value;
        const status = document.getElementById('form-status').value;
        const description = document.getElementById('form-description').value;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const payload = { platform, title, tag, date, time, status, description };

        try {
            if (id) {
                // UPDATE
                const response = await fetch(`/content-planner/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const resData = await response.json();
                if (!response.ok || resData.status !== 'success') {
                    throw new Error(resData.message || 'Jadwal gagal diperbarui.');
                }
                schedules = resData.schedules;
                showToast('Sukses', 'Jadwal berhasil diperbarui!');
            } else {
                // CREATE
                const response = await fetch('/content-planner', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const resData = await response.json();
                if (!response.ok || resData.status !== 'success') {
                    throw new Error(resData.message || 'Jadwal gagal ditambahkan.');
                }
                schedules = resData.schedules;
                showToast('Sukses', 'Jadwal baru berhasil ditambahkan!');
            }
        } catch (err) {
            console.error(err);
            showToast('Gagal menyimpan jadwal', err.message || 'Silakan coba lagi.', true);
            return;
        }

        closeModal();
        renderAll();
    }

    // --- DELETE FUNCTIONS ---
    function promptDelete(id) {
        scheduleToDeleteId = id;
        const item = schedules.find(s => s.id == id);
        if (item) {
            document.getElementById('delete-schedule-title').textContent = `"${item.title}" (${item.platform} • ${item.date})`;
        }
        document.getElementById('delete-confirm-modal').classList.remove('hidden');
    }

    function confirmDeleteFromModal() {
        const id = document.getElementById('form-schedule-id').value;
        closeModal();
        if (id) {
            promptDelete(id);
        }
    }

    function closeDeleteModal() {
        document.getElementById('delete-confirm-modal').classList.add('hidden');
        scheduleToDeleteId = null;
    }

    async function executeDelete() {
        if (!scheduleToDeleteId) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(`/content-planner/${scheduleToDeleteId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const resData = await response.json();
            if (!response.ok || resData.status !== 'success') {
                throw new Error(resData.message || 'Jadwal gagal dihapus.');
            }
            schedules = resData.schedules;
        } catch (err) {
            console.error(err);
            showToast('Gagal menghapus jadwal', err.message || 'Silakan coba lagi.', true);
            return;
        }

        showToast('Terhapus', 'Jadwal berhasil dihapus dari sistem.');
        closeDeleteModal();
        renderAll();
    }

    // --- TOAST NOTIFICATION ---
    function showToast(title, message, isError = false) {
        const toast = document.getElementById('toast-notification');
        const icon = document.getElementById('toast-icon');
        document.getElementById('toast-title').textContent = title;
        document.getElementById('toast-message').textContent = message;
        if (isError) {
            icon.className = 'w-6 h-6 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
        } else {
            icon.className = 'w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>';
        }

        toast.classList.remove('translate-y-20', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }

    // --- HELPER STYLES ---
    function getPlatformColorStyles(platform, status) {
        switch (platform.toLowerCase()) {
            case 'instagram':
                return {
                    containerClass: 'bg-pink-50 border border-pink-200/60 text-pink-700',
                    textClass: 'text-pink-600'
                };
            case 'tiktok':
                return {
                    containerClass: 'bg-sky-50 border border-sky-200/60 text-sky-800',
                    textClass: 'text-sky-600'
                };
            case 'youtube':
                return {
                    containerClass: 'bg-rose-50 border border-rose-200/60 text-rose-800',
                    textClass: 'text-rose-600'
                };
            case 'threads':
                return {
                    containerClass: 'bg-gray-100 border border-gray-200 text-gray-800',
                    textClass: 'text-gray-800'
                };
            default:
                return {
                    containerClass: 'bg-blue-50 border border-blue-200 text-blue-700',
                    textClass: 'text-blue-600'
                };
        }
    }

    function getCardHeaderGradient(platform) {
        switch (platform.toLowerCase()) {
            case 'instagram':
                return 'bg-gradient-to-br from-amber-950 via-rose-950 to-purple-950';
            case 'tiktok':
                return 'bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950';
            case 'youtube':
                return 'bg-gradient-to-br from-neutral-900 via-stone-900 to-red-950';
            case 'threads':
                return 'bg-gradient-to-br from-gray-900 via-zinc-900 to-neutral-950';
            default:
                return 'bg-gradient-to-br from-blue-900 to-indigo-950';
        }
    }

    function getStatusBadge(status) {
        switch (status) {
            case 'scheduled':
                return `<span class="inline-flex items-center px-2.5 py-1 bg-sky-500 text-white text-[11px] font-semibold rounded-full shadow-xs">Scheduled</span>`;
            case 'draft':
                return `<span class="inline-flex items-center px-2.5 py-1 bg-rose-500 text-white text-[11px] font-semibold rounded-full shadow-xs">Draft</span>`;
            case 'published':
                return `<span class="inline-flex items-center px-2.5 py-1 bg-emerald-500 text-white text-[11px] font-semibold rounded-full shadow-xs">Published</span>`;
            default:
                return `<span class="inline-flex items-center px-2.5 py-1 bg-gray-500 text-white text-[11px] font-semibold rounded-full shadow-xs">Draft</span>`;
        }
    }

    function getPlatformDot(platform) {
        switch (platform.toLowerCase()) {
            case 'instagram': return '<span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-500 to-pink-500"></span>';
            case 'tiktok': return '<span class="w-2 h-2 rounded-full bg-gray-900"></span>';
            case 'youtube': return '<span class="w-2 h-2 rounded-full bg-red-500"></span>';
            case 'threads': return '<span class="w-2 h-2 rounded-full bg-gray-700"></span>';
            default: return '<span class="w-2 h-2 rounded-full bg-blue-500"></span>';
        }
    }

    function getPlatformIcon(platform) {
        switch (platform.toLowerCase()) {
            case 'tiktok':
                return `<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.8.1V9.01a6.27 6.27 0 00-.8-.05 6.34 6.34 0 00-6.34 6.34 6.34 0 006.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.84a4.84 4.84 0 01-1-.15z"/></svg>`;
            case 'instagram':
                return `<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>`;
            case 'youtube':
                return `<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>`;
            default:
                return `<span class="w-2 h-2 rounded-full bg-white"></span>`;
        }
    }
</script>
@endpush
@endsection
