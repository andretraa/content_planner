@extends('layouts.app')

@section('title', 'Ide Konten')

@section('content')
<div class="space-y-8" id="ide-konten-app">

    {{-- Toast Notification --}}
    <div id="toast-notification" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-gray-700">
        <div id="toast-icon" class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <div>
            <p id="toast-title" class="text-sm font-semibold">Berhasil</p>
            <p id="toast-message" class="text-xs text-gray-300">Data ide konten berhasil diperbarui.</p>
        </div>
    </div>

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Content Ideation &amp; Brainstorming</h2>
            <p class="text-gray-500 mt-1 text-sm">Capture, research, and orchestrate cross-platform content pipelines from spark to screen.</p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-width="2" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="idea-search" oninput="filterIdeas()" placeholder="Search ideas, tags, hooks..." class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64 bg-white shadow-xs">
            </div>
            <button type="button" onclick="openIdeaModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-blue-500/20 transition-all duration-200 flex items-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                New Idea
            </button>
        </div>
    </div>

    {{-- Summary Cards (All numbers emptied to "-" per instructions) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Active Spark Pool --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">ACTIVE SPARK POOL</span>
                <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center text-sky-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-1">- <span class="text-sm font-medium text-gray-400">Ideas</span></div>
            <div class="flex items-center gap-1 text-xs text-sky-600 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                -+ added this week
            </div>
        </div>

        {{-- In Production --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">IN PRODUCTION</span>
                <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center text-sky-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-1">- <span class="text-sm font-medium text-gray-400">Scripts</span></div>
            <div class="flex items-center gap-1 text-xs text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                - due today
            </div>
        </div>

        {{-- Ready to Shoot --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">READY TO SHOOT</span>
                <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center text-sky-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-1">- <span class="text-sm font-medium text-gray-400">Batches</span></div>
            <div class="flex items-center gap-1 text-xs text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Studio booked
            </div>
        </div>

        {{-- Trending Topic Index --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">TRENDING TOPIC INDEX</span>
                <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center text-sky-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-1">AI Workflow</div>
            <div class="flex items-center gap-1 text-xs text-sky-600 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                -+% velocity
            </div>
        </div>
    </div>

    {{-- Interactive Kanban Board --}}
    <div class="overflow-x-auto -mx-2 px-2 pb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 min-w-[1020px]">

            {{-- Column 1: Ideas --}}
            <div class="flex flex-col bg-gray-50/60 p-3.5 rounded-3xl border border-gray-200/60 min-h-[480px]">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-gray-400 rounded-full"></span>
                        <h3 class="text-sm font-bold text-gray-700">Ideas</h3>
                        <span id="badge-count-ideas" class="bg-gray-200/80 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
                    </div>
                    <button type="button" onclick="openIdeaModal(null, 'ideas')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-500 hover:text-gray-800 transition" title="Tambah Ide ke Kolom Ini">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div id="col-ideas" class="space-y-3 flex-1">
                    {{-- Rendered by JS --}}
                </div>
            </div>

            {{-- Column 2: In Progress --}}
            <div class="flex flex-col bg-gray-50/60 p-3.5 rounded-3xl border border-gray-200/60 min-h-[480px]">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-blue-500 rounded-full"></span>
                        <h3 class="text-sm font-bold text-gray-700">In Progress</h3>
                        <span id="badge-count-in_progress" class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
                    </div>
                    <button type="button" onclick="openIdeaModal(null, 'in_progress')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-500 hover:text-gray-800 transition" title="Tambah ke In Progress">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div id="col-in_progress" class="space-y-3 flex-1">
                    {{-- Rendered by JS --}}
                </div>
            </div>

            {{-- Column 3: Ready to Shoot --}}
            <div class="flex flex-col bg-gray-50/60 p-3.5 rounded-3xl border border-gray-200/60 min-h-[480px]">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-sky-500 rounded-full"></span>
                        <h3 class="text-sm font-bold text-gray-700">Ready to Shoot</h3>
                        <span id="badge-count-ready_to_shoot" class="bg-sky-100 text-sky-700 text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
                    </div>
                    <button type="button" onclick="openIdeaModal(null, 'ready_to_shoot')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-500 hover:text-gray-800 transition" title="Tambah ke Ready to Shoot">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div id="col-ready_to_shoot" class="space-y-3 flex-1">
                    {{-- Rendered by JS --}}
                </div>
            </div>

            {{-- Column 4: Completed --}}
            <div class="flex flex-col bg-gray-50/60 p-3.5 rounded-3xl border border-gray-200/60 min-h-[480px]">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-teal-500 rounded-full"></span>
                        <h3 class="text-sm font-bold text-gray-700">Completed</h3>
                        <span id="badge-count-completed" class="bg-teal-100 text-teal-700 text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
                    </div>
                    <button type="button" onclick="openIdeaModal(null, 'completed')" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-500 hover:text-gray-800 transition" title="Tambah ke Completed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div id="col-completed" class="space-y-3 flex-1">
                    {{-- Rendered by JS --}}
                </div>
            </div>

        </div>
    </div>

    {{-- Reference & Trend Vault --}}
    <div class="space-y-4 pt-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Reference &amp; Trend Vault</h3>
                <p class="text-gray-500 text-xs mt-0.5">External links, inspirational posts, and audio assets bookmarked for upcoming production.</p>
            </div>
            <button type="button" onclick="showToast('Reference Vault', 'Fitur simpan referensi tren aktif.')" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                Add Reference
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Reference Card 1 --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-xs hover:shadow-md transition group">
                <div class="h-32 bg-gradient-to-br from-slate-100 via-gray-100 to-indigo-100 flex items-center justify-center border-b border-gray-100">
                    <div class="px-3 py-1.5 bg-white/80 backdrop-blur rounded-lg shadow-xs text-xs font-semibold text-gray-600">
                        Typography Inspiration
                    </div>
                </div>
                <div class="p-4">
                    <span class="inline-block px-2.5 py-1 bg-pink-50 text-pink-600 text-[10px] font-bold rounded-md mb-2 border border-pink-100">Instagram Reel Hook Style</span>
                    <h4 class="text-sm font-bold text-gray-900 mb-1 leading-snug">Fast Kinetic Typography Reel</h4>
                    <p class="text-xs text-gray-500 mb-3 leading-relaxed line-clamp-2">Reference structure for high retention intro hooks using dynamic text animations.</p>
                    <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-100">
                        <span class="text-gray-400">Saved by Sarah</span>
                        <a href="javascript:void(0)" onclick="showToast('Trend Vault', 'Membuka preview sumber referensi...')" class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                            View Source
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Reference Card 2 --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-xs hover:shadow-md transition group">
                <div class="h-32 bg-gradient-to-br from-purple-100 via-sky-50 to-indigo-100 flex items-center justify-center border-b border-gray-100">
                    <div class="px-3 py-1.5 bg-white/80 backdrop-blur rounded-lg shadow-xs text-xs font-semibold text-gray-600">
                        Lo-Fi Studio Audio
                    </div>
                </div>
                <div class="p-4">
                    <span class="inline-block px-2.5 py-1 bg-sky-50 text-sky-600 text-[10px] font-bold rounded-md mb-2 border border-sky-100">YouTube Audio Trend</span>
                    <h4 class="text-sm font-bold text-gray-900 mb-1 leading-snug">Cinematic Lo-Fi Transition Beats</h4>
                    <p class="text-xs text-gray-500 mb-3 leading-relaxed line-clamp-2">Trending background track with massive usage volume in tech workspace breakdowns.</p>
                    <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-100">
                        <span class="text-gray-400">Saved by Alex</span>
                        <a href="javascript:void(0)" onclick="showToast('Audio Trend', 'Memutar pratinjau audio tren...')" class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                            Listen Audio
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Reference Card 3 --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-xs hover:shadow-md transition group">
                <div class="h-32 bg-gradient-to-br from-emerald-50 via-teal-50 to-sky-100 flex items-center justify-center border-b border-gray-100">
                    <div class="px-3 py-1.5 bg-white/80 backdrop-blur rounded-lg shadow-xs text-xs font-semibold text-gray-600">
                        Thread Writing Anatomy
                    </div>
                </div>
                <div class="p-4">
                    <span class="inline-block px-2.5 py-1 bg-purple-50 text-purple-600 text-[10px] font-bold rounded-md mb-2 border border-purple-100">Threads Format</span>
                    <h4 class="text-sm font-bold text-gray-900 mb-1 leading-snug">The 3-Part Bullet Framework</h4>
                    <p class="text-xs text-gray-500 mb-3 leading-relaxed line-clamp-2">Anatomy of a viral informational thread with maximum save and share metrics.</p>
                    <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-100">
                        <span class="text-gray-400">Saved by Team</span>
                        <a href="javascript:void(0)" onclick="showToast('Thread Framework', 'Membuka template panduan thread...')" class="text-blue-600 font-semibold hover:underline flex items-center gap-1">
                            View Source
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MODAL TAMBAH / EDIT IDE --}}
<div id="modal-idea" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" onclick="closeIdeaModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl relative z-10 border border-gray-100">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="idea-modal-title">Tambah Ide Konten</h3>
                        <p class="text-xs text-gray-500">Kumpulkan ide konten dan alur produksi Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeIdeaModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-idea" onsubmit="handleIdeaSubmit(event)" class="mt-5 space-y-4">
                <input type="hidden" id="idea-id" value="">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Judul Ide <span class="text-red-500">*</span></label>
                    <input type="text" id="idea-title" required placeholder="Contoh: 5 Hidden ChatGPT Prompts..." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Deskripsi / Konsep Konten</label>
                    <textarea id="idea-description" rows="3" placeholder="Deskripsikan ide, hook pembuka, atau format konten..." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Platform <span class="text-red-500">*</span></label>
                        <select id="idea-platform" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="youtube">YouTube</option>
                            <option value="threads">Threads</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Status Kolom <span class="text-red-500">*</span></label>
                        <select id="idea-column" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="ideas">Ideas</option>
                            <option value="in_progress">In Progress</option>
                            <option value="ready_to_shoot">Ready to Shoot</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Kategori / Topik</label>
                        <input type="text" id="idea-category" placeholder="AI & Productivity, Tech, Design, etc." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Badge / Catatan Tambahan</label>
                        <input type="text" id="idea-badge" placeholder="Studio B, Outdoor, Scripting, etc." class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeIdeaModal()" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-xl hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit" id="btn-save-idea" class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-lg shadow-blue-500/25 active:scale-95">
                        Simpan Ide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL SCHEDULE TO PLANNER --}}
<div id="modal-send-to-planner" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" onclick="closeSendToPlannerModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl relative z-10 border border-gray-100">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Jadwalkan ke Content Planner</h3>
            <p class="text-xs text-gray-500 mb-4">Pindahkan ide "<span id="planner-sync-title" class="font-semibold text-gray-800"></span>" ke jadwal tayang kalender.</p>

            <form onsubmit="handleSendToPlannerSubmit(event)" class="space-y-4">
                <input type="hidden" id="planner-sync-id" value="">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Tanggal Jadwal</label>
                    <input type="date" id="planner-sync-date" required value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Jam Posting</label>
                    <input type="time" id="planner-sync-time" required value="14:00" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeSendToPlannerModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                    <button type="submit" id="btn-submit-planner-sync" class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 active:scale-95">
                        Jadwalkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL HAPUS IDE --}}
<div id="modal-delete-idea" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" onclick="closeDeleteIdeaModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl relative z-10 border border-gray-100 text-center">
            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Hapus Ide Konten?</h3>
            <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus ide "<span id="delete-idea-title" class="font-medium text-gray-700"></span>"?</p>
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteIdeaModal()" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-xl hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="button" id="btn-confirm-delete-idea" onclick="confirmDeleteIdea()" class="px-6 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-lg shadow-red-500/25 active:scale-95">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let ideasData = @json($ideas ?? []);
    let ideaSearchQuery = '';
    let ideaToDeleteId = null;

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

    // Modal Add / Edit Idea
    function openIdeaModal(editId = null, defaultColumn = 'ideas') {
        const modal = document.getElementById('modal-idea');
        const form = document.getElementById('form-idea');
        const modalTitle = document.getElementById('idea-modal-title');

        form.reset();
        document.getElementById('idea-id').value = '';
        document.getElementById('idea-column').value = defaultColumn;

        if (editId) {
            const item = ideasData.find(i => i.id == editId);
            if (item) {
                modalTitle.innerText = 'Edit Ide Konten';
                document.getElementById('idea-id').value = item.id;
                document.getElementById('idea-title').value = item.title;
                document.getElementById('idea-description').value = item.description || '';
                document.getElementById('idea-platform').value = item.platform || 'instagram';
                document.getElementById('idea-column').value = item.column || 'ideas';
                document.getElementById('idea-category').value = item.category || '';
                document.getElementById('idea-badge').value = item.badge || '';
            }
        } else {
            modalTitle.innerText = 'Tambah Ide Konten';
        }

        modal.classList.remove('hidden');
    }

    function closeIdeaModal() {
        document.getElementById('modal-idea').classList.add('hidden');
    }

    // Submit Add/Edit Idea
    async function handleIdeaSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('idea-id').value;
        const btnSave = document.getElementById('btn-save-idea');
        btnSave.disabled = true;
        btnSave.innerText = 'Menyimpan...';

        const payload = {
            title: document.getElementById('idea-title').value,
            description: document.getElementById('idea-description').value,
            platform: document.getElementById('idea-platform').value,
            column: document.getElementById('idea-column').value,
            category: document.getElementById('idea-category').value,
            badge: document.getElementById('idea-badge').value,
        };

        const url = id ? `/ide-konten/${id}` : '/ide-konten';
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
                if (data.ideas) {
                    ideasData = data.ideas;
                } else {
                    await reloadIdeas();
                }
                closeIdeaModal();
                renderKanban();
                showToast('Sukses', id ? 'Ide berhasil diperbarui!' : 'Ide baru berhasil ditambahkan!');
            } else {
                showToast('Gagal', data.message || 'Gagal menyimpan ide', true);
            }
        } catch (err) {
            console.error(err);
            showToast('Kesalahan', 'Gagal menghubungi server', true);
        } finally {
            btnSave.disabled = false;
            btnSave.innerText = 'Simpan Ide';
        }
    }

    // Delete Idea
    function openDeleteIdeaModal(id, title) {
        ideaToDeleteId = id;
        document.getElementById('delete-idea-title').innerText = title;
        document.getElementById('modal-delete-idea').classList.remove('hidden');
    }

    function closeDeleteIdeaModal() {
        ideaToDeleteId = null;
        document.getElementById('modal-delete-idea').classList.add('hidden');
    }

    async function confirmDeleteIdea() {
        if (!ideaToDeleteId) return;
        const btnDelete = document.getElementById('btn-confirm-delete-idea');
        btnDelete.disabled = true;
        btnDelete.innerText = 'Menghapus...';

        try {
            const res = await fetch(`/ide-konten/${ideaToDeleteId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                if (data.ideas) {
                    ideasData = data.ideas;
                } else {
                    ideasData = ideasData.filter(i => i.id != ideaToDeleteId);
                }
                closeDeleteIdeaModal();
                renderKanban();
                showToast('Dihapus', 'Ide berhasil dihapus!');
            } else {
                showToast('Gagal', data.message || 'Gagal menghapus ide', true);
            }
        } catch (err) {
            console.error(err);
            showToast('Kesalahan', 'Terjadi kesalahan koneksi', true);
        } finally {
            btnDelete.disabled = false;
            btnDelete.innerText = 'Ya, Hapus';
        }
    }

    // Move column directly
    async function moveIdeaColumn(id, newCol) {
        const idea = ideasData.find(i => i.id == id);
        if (!idea) return;

        const payload = {
            title: idea.title,
            description: idea.description,
            platform: idea.platform,
            category: idea.category,
            column: newCol,
            badge: idea.badge
        };

        try {
            const res = await fetch(`/ide-konten/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                idea.column = newCol;
                renderKanban();
                showToast('Dipindahkan', `Ide dipindahkan ke ${newCol.replace('_', ' ')}.`);
            }
        } catch(e) {
            console.error(e);
        }
    }

    // Send to Planner Dialog
    function openSendToPlannerModal(id, title) {
        document.getElementById('planner-sync-id').value = id;
        document.getElementById('planner-sync-title').innerText = title;
        document.getElementById('modal-send-to-planner').classList.remove('hidden');
    }

    function closeSendToPlannerModal() {
        document.getElementById('modal-send-to-planner').classList.add('hidden');
    }

    async function handleSendToPlannerSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('planner-sync-id').value;
        const date = document.getElementById('planner-sync-date').value;
        const time = document.getElementById('planner-sync-time').value;
        const btn = document.getElementById('btn-submit-planner-sync');
        btn.disabled = true;
        btn.innerText = 'Menjadwalkan...';

        try {
            const res = await fetch(`/ide-konten/${id}/send-to-planner`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ date: date, time: time })
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                if (data.ideas) {
                    ideasData = data.ideas;
                }
                closeSendToPlannerModal();
                renderKanban();
                showToast('Tersinkronisasi', data.message || 'Berhasil dijadwalkan ke Content Planner!');
            } else {
                showToast('Gagal', data.message || 'Gagal sinkronisasi ke Planner', true);
            }
        } catch (err) {
            console.error(err);
            showToast('Kesalahan', 'Gagal mengirim data ke Content Planner', true);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Jadwalkan Sekarang';
        }
    }

    // Send to Laporan Sosmed (1-Click Sync!)
    async function sendIdeaToReport(id) {
        const idea = ideasData.find(i => i.id == id);
        if (!idea) return;

        try {
            const res = await fetch(`/laporan-sosmed/from-idea/${id}`, {
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
                showToast('Tersinkronisasi', `Ide "${idea.title}" berhasil dijadikan Laporan Sosmed!`);
            } else {
                showToast('Gagal', data.message || 'Gagal mengirim ke Laporan Sosmed', true);
            }
        } catch(e) {
            console.error(e);
            showToast('Kesalahan', 'Gagal sinkron ke Laporan Sosmed', true);
        }
    }

    async function reloadIdeas() {
        try {
            const res = await fetch('/api/ideas');
            const data = await res.json();
            if (data.status === 'success' && Array.isArray(data.data)) {
                ideasData = data.data;
            }
        } catch(e) {
            console.error(e);
        }
    }

    // Filter & Kanban Render
    function filterIdeas() {
        ideaSearchQuery = document.getElementById('idea-search').value.toLowerCase().trim();
        renderKanban();
    }

    function renderKanban() {
        const columns = ['ideas', 'in_progress', 'ready_to_shoot', 'completed'];
        const counts = { ideas: 0, in_progress: 0, ready_to_shoot: 0, completed: 0 };

        columns.forEach(col => {
            const container = document.getElementById(`col-${col}`);
            if (!container) return;

            let items = ideasData.filter(i => (i.column || 'ideas') === col);

            if (ideaSearchQuery) {
                items = items.filter(i => {
                    const t = (i.title || '').toLowerCase();
                    const d = (i.description || '').toLowerCase();
                    const c = (i.category || '').toLowerCase();
                    return t.includes(ideaSearchQuery) || d.includes(ideaSearchQuery) || c.includes(ideaSearchQuery);
                });
            }

            counts[col] = items.length;

            if (items.length === 0) {
                container.innerHTML = `
                <div class="py-8 px-4 text-center rounded-2xl border border-dashed border-gray-200 bg-white/40">
                    <p class="text-xs text-gray-400">Belum ada ide di kolom ini</p>
                    <button type="button" onclick="openIdeaModal(null, '${col}')" class="mt-2 text-xs text-blue-600 hover:text-blue-700 font-semibold">+ Tambah</button>
                </div>`;
                return;
            }

            let cardsHtml = '';
            items.forEach(item => {
                const p = (item.platform || '').toLowerCase();
                let platformPill = '';
                if (p === 'instagram') {
                    platformPill = `<span class="inline-flex items-center gap-1 px-2.5 py-1 bg-pink-50 text-pink-600 text-[11px] font-semibold rounded-md border border-pink-100">Instagram</span>`;
                } else if (p === 'tiktok') {
                    platformPill = `<span class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-600 text-[11px] font-semibold rounded-md border border-sky-100">TikTok</span>`;
                } else if (p === 'youtube') {
                    platformPill = `<span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 text-[11px] font-semibold rounded-md border border-red-100">YouTube</span>`;
                } else {
                    platformPill = `<span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-800 text-[11px] font-semibold rounded-md border border-gray-200">Threads</span>`;
                }

                const borderStyle = col === 'ready_to_shoot' ? 'border-sky-300' : 'border-gray-200/80';

                // Next/prev column movement shortcuts
                const colOrder = ['ideas', 'in_progress', 'ready_to_shoot', 'completed'];
                const curIdx = colOrder.indexOf(col);
                const prevCol = curIdx > 0 ? colOrder[curIdx - 1] : null;
                const nextCol = curIdx < colOrder.length - 1 ? colOrder[curIdx + 1] : null;

                cardsHtml += `
                <div class="bg-white rounded-2xl border ${borderStyle} p-4 shadow-xs hover:shadow-md transition relative group">
                    <div class="flex items-center justify-between mb-2.5">
                        ${platformPill}
                        <div class="flex items-center gap-1">
                            ${item.badge ? `<span class="text-[10px] px-2 py-0.5 bg-blue-50 text-blue-700 font-medium rounded-md">${escapeHtml(item.badge)}</span>` : ''}
                            <span class="text-[11px] text-gray-400">${item.time_ago || ''}</span>
                        </div>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 mb-1 leading-snug">${escapeHtml(item.title)}</h4>
                    ${item.description ? `<p class="text-xs text-gray-500 mb-2 leading-relaxed">${escapeHtml(item.description)}</p>` : ''}

                    ${item.scheduled_note ? `
                    <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mb-2 bg-sky-50/70 p-2 rounded-lg border border-sky-100 text-sky-800">
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>${escapeHtml(item.scheduled_note)}</span>
                    </div>` : ''}

                    {{-- Actions Bar --}}
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between mt-2">
                        <span class="text-[11px] font-medium text-gray-500 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            ${escapeHtml(item.category || 'General')}
                        </span>

                        <div class="flex items-center gap-1">
                            {{-- Sync to Planner --}}
                            <button type="button" onclick="openSendToPlannerModal(${item.id}, '${escapeQuote(item.title)}')" class="p-1 text-gray-400 hover:text-blue-600 rounded hover:bg-blue-50 transition" title="Jadwalkan ke Content Planner">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>

                            {{-- Sync to Report --}}
                            <button type="button" onclick="sendIdeaToReport(${item.id})" class="p-1 text-gray-400 hover:text-emerald-600 rounded hover:bg-emerald-50 transition" title="Kirim ke Laporan Sosmed">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </button>

                            {{-- Move Prev --}}
                            ${prevCol ? `
                            <button type="button" onclick="moveIdeaColumn(${item.id}, '${prevCol}')" class="p-1 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100 transition" title="Mundur ke ${prevCol.replace('_', ' ')}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>` : ''}

                            {{-- Move Next --}}
                            ${nextCol ? `
                            <button type="button" onclick="moveIdeaColumn(${item.id}, '${nextCol}')" class="p-1 text-gray-400 hover:text-gray-700 rounded hover:bg-gray-100 transition" title="Maju ke ${nextCol.replace('_', ' ')}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>` : ''}

                            {{-- Edit --}}
                            <button type="button" onclick="openIdeaModal(${item.id})" class="p-1 text-gray-400 hover:text-blue-600 rounded hover:bg-blue-50 transition" title="Edit Ide">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </button>

                            {{-- Delete --}}
                            <button type="button" onclick="openDeleteIdeaModal(${item.id}, '${escapeQuote(item.title)}')" class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Hapus Ide">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>`;
            });

            container.innerHTML = cardsHtml;
        });

        // Update column count badges
        columns.forEach(col => {
            const badge = document.getElementById(`badge-count-${col}`);
            if (badge) badge.innerText = counts[col];
        });
    }

    // Helper functions
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

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        renderKanban();
    });
</script>
@endpush
@endsection
