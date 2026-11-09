<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function laporanSosmed()
    {
        return view('laporan-sosmed', ['activePage' => 'laporan-sosmed']);
    }

    public function contentPlanner()
    {
        return view('content-planner', ['activePage' => 'content-planner']);
    }

    public function ideKonten()
    {
        return view('ide-konten', ['activePage' => 'ide-konten']);
    }

    public function pengaturan()
    {
        return view('pengaturan', ['activePage' => 'pengaturan']);
    }
}

