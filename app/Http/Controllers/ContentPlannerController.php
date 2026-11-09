<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContentPlannerController extends Controller
{
    protected function getStoragePath()
    {
        return storage_path('app/schedules.json');
    }

    protected function getSchedules()
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    protected function saveSchedules(array $schedules)
    {
        file_put_contents($this->getStoragePath(), json_encode(array_values($schedules), JSON_PRETTY_PRINT));
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

        $schedules = $this->getSchedules();
        $newId = count($schedules) > 0 ? (max(array_column($schedules, 'id')) + 1) : 1;

        $newItem = [
            'id' => $newId,
            'platform' => strtolower($validated['platform']),
            'tag' => $validated['tag'] ?: ucfirst($validated['platform']),
            'title' => $validated['title'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? ''
        ];

        $schedules[] = $newItem;
        $this->saveSchedules($schedules);

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

        $schedules = $this->getSchedules();
        $found = false;

        foreach ($schedules as &$item) {
            if ($item['id'] == $id) {
                $item['platform'] = strtolower($validated['platform']);
                $item['tag'] = $validated['tag'] ?: ucfirst($validated['platform']);
                $item['title'] = $validated['title'];
                $item['date'] = $validated['date'];
                $item['time'] = $validated['time'];
                $item['status'] = $validated['status'];
                $item['description'] = $validated['description'] ?? '';
                $found = true;
                break;
            }
        }

        if (!$found) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan'], 404);
            }
            return redirect()->route('content-planner')->with('error', 'Jadwal tidak ditemukan');
        }

        $this->saveSchedules($schedules);

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
        $schedules = $this->getSchedules();
        $filtered = array_filter($schedules, function ($item) use ($id) {
            return $item['id'] != $id;
        });

        $this->saveSchedules($filtered);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal berhasil dihapus!',
                'schedules' => array_values($filtered)
            ]);
        }

        return redirect()->route('content-planner')->with('success', 'Jadwal berhasil dihapus!');
    }
}

