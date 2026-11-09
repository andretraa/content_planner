<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentPlannerController extends Controller
{
    protected function getSchedules()
    {
        return DB::table('schedules')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
    }

    public function index()
    {
        $schedules = $this->getSchedules();
        return view('content-planner', [
            'activePage' => 'content-planner',
            'schedules' => $schedules
        ]);
    }

    public function apiList()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->getSchedules()
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|string',
            'time' => 'required|string',
            'status' => 'required|string|in:scheduled,draft,published',
            'tag' => 'nullable|string'
        ]);

        $newItem = [
            'platform' => strtolower($validated['platform']),
            'tag' => ($validated['tag'] ?? null) ?: ucfirst($validated['platform']),
            'title' => $validated['title'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? ''
        ];

        $newItem['id'] = DB::table('schedules')->insertGetId($newItem);
        $schedules = $this->getSchedules();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal berhasil ditambahkan!',
                'data' => $newItem,
                'schedules' => $schedules
            ]);
        }

        return redirect()->route('content-planner')->with('success', 'Jadwal berhasil ditambahkan!');
    }

    public function apiUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'platform' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|string',
            'time' => 'required|string',
            'status' => 'required|string|in:scheduled,draft,published',
            'tag' => 'nullable|string'
        ]);

        $updated = DB::table('schedules')->where('id', $id)->update([
            'platform' => strtolower($validated['platform']),
            'tag' => ($validated['tag'] ?? null) ?: ucfirst($validated['platform']),
            'title' => $validated['title'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? '',
        ]);

        if (!$updated && !DB::table('schedules')->where('id', $id)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan'], 404);
            }
            return redirect()->route('content-planner')->with('error', 'Jadwal tidak ditemukan');
        }

        $schedules = $this->getSchedules();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal berhasil diperbarui!',
                'schedules' => $schedules
            ]);
        }

        return redirect()->route('content-planner')->with('success', 'Jadwal berhasil diperbarui!');
    }

    public function apiDestroy(Request $request, $id)
    {
        DB::table('schedules')->where('id', $id)->delete();
        $schedules = $this->getSchedules();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal berhasil dihapus!',
                'schedules' => $schedules
            ]);
        }

        return redirect()->route('content-planner')->with('success', 'Jadwal berhasil dihapus!');
    }
}
