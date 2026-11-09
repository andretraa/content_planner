<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContentPlannerController;
use App\Http\Controllers\LaporanSosmedController;
use App\Http\Controllers\IdeKontenController;
use Illuminate\Support\Facades\Route;

// Laporan Sosmed (Dashboard Utama)
Route::get('/', [LaporanSosmedController::class, 'index'])->name('laporan-sosmed');
Route::get('/laporan-sosmed', [LaporanSosmedController::class, 'index'])->name('laporan-sosmed.page');
Route::get('/api/reports', [LaporanSosmedController::class, 'apiList'])->name('reports.list');
Route::post('/laporan-sosmed', [LaporanSosmedController::class, 'apiStore'])->name('reports.store');
Route::put('/laporan-sosmed/{id}', [LaporanSosmedController::class, 'apiUpdate'])->name('reports.update');
Route::post('/laporan-sosmed/update/{id}', [LaporanSosmedController::class, 'apiUpdate'])->name('reports.update.post');
Route::delete('/laporan-sosmed/{id}', [LaporanSosmedController::class, 'apiDestroy'])->name('reports.destroy');
Route::post('/laporan-sosmed/delete/{id}', [LaporanSosmedController::class, 'apiDestroy'])->name('reports.destroy.post');
Route::get('/laporan-sosmed/export', [LaporanSosmedController::class, 'exportCsv'])->name('reports.export');
Route::post('/laporan-sosmed/from-schedule/{id}', [LaporanSosmedController::class, 'apiFromSchedule'])->name('reports.from-schedule');
Route::post('/laporan-sosmed/from-idea/{id}', [LaporanSosmedController::class, 'apiFromIdea'])->name('reports.from-idea');

// Content Planner
Route::get('/content-planner', [ContentPlannerController::class, 'index'])->name('content-planner');
Route::get('/api/schedules', [ContentPlannerController::class, 'apiList'])->name('schedules.list');
Route::post('/content-planner', [ContentPlannerController::class, 'apiStore'])->name('schedules.store');
Route::put('/content-planner/{id}', [ContentPlannerController::class, 'apiUpdate'])->name('schedules.update');
Route::post('/content-planner/update/{id}', [ContentPlannerController::class, 'apiUpdate'])->name('schedules.update.post');
Route::delete('/content-planner/{id}', [ContentPlannerController::class, 'apiDestroy'])->name('schedules.destroy');
Route::post('/content-planner/delete/{id}', [ContentPlannerController::class, 'apiDestroy'])->name('schedules.destroy.post');

// Ide Konten
Route::get('/ide-konten', [IdeKontenController::class, 'index'])->name('ide-konten');
Route::get('/api/ideas', [IdeKontenController::class, 'apiList'])->name('ideas.list');
Route::post('/ide-konten', [IdeKontenController::class, 'apiStore'])->name('ideas.store');
Route::put('/ide-konten/{id}', [IdeKontenController::class, 'apiUpdate'])->name('ideas.update');
Route::post('/ide-konten/update/{id}', [IdeKontenController::class, 'apiUpdate'])->name('ideas.update.post');
Route::delete('/ide-konten/{id}', [IdeKontenController::class, 'apiDestroy'])->name('ideas.destroy');
Route::post('/ide-konten/delete/{id}', [IdeKontenController::class, 'apiDestroy'])->name('ideas.destroy.post');
Route::post('/ide-konten/{id}/send-to-planner', [IdeKontenController::class, 'apiSendToPlanner'])->name('ideas.send-to-planner');

// Pengaturan
Route::get('/pengaturan', [DashboardController::class, 'pengaturan'])->name('pengaturan');
