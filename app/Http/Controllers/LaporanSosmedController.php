<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaporanSosmedController extends Controller
{
    protected function getStoragePath()
    {
        return storage_path('app/reports.json');
    }

    protected function getReports()
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    protected function saveReports(array $reports)
    {
        file_put_contents($this->getStoragePath(), json_encode(array_values($reports), JSON_PRETTY_PRINT));
    }

    protected function getSchedules()
    {
        $path = storage_path('app/schedules.json');
        if (!file_exists($path)) return [];
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    protected function getIdeas()
    {
        $path = storage_path('app/ideas.json');
        if (!file_exists($path)) return [];
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    public function index()
    {
        $reports = $this->getReports();
        $schedules = $this->getSchedules();
        $ideas = $this->getIdeas();

        return view('laporan-sosmed', [
            'activePage' => 'laporan-sosmed',
            'reports' => $reports,
            'schedules' => $schedules,
            'ideas' => $ideas
        ]);
    }

    public function apiList()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->getReports()
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'required|string',
            'tag' => 'nullable|string',
            'followers' => 'nullable|string',
            'tayangan' => 'nullable|string',
            'jangkauan' => 'nullable|string',
            'engagement' => 'nullable|string',
            'date' => 'nullable|string',
            'period' => 'nullable|string',
            'schedule_id' => 'nullable|integer',
            'idea_id' => 'nullable|integer',
        ]);

        $reports = $this->getReports();
        $newId = count($reports) > 0 ? (max(array_column($reports, 'id')) + 1) : 1;

        $newItem = [
            'id' => $newId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? 'Diposting baru saja',
            'platform' => strtolower($validated['platform']),
            'tag' => $validated['tag'] ?: ucfirst($validated['platform']),
            'followers' => !empty($validated['followers']) ? $validated['followers'] : '-',
            'tayangan' => !empty($validated['tayangan']) ? $validated['tayangan'] : '-',
            'jangkauan' => !empty($validated['jangkauan']) ? $validated['jangkauan'] : '-',
            'engagement' => !empty($validated['engagement']) ? (str_contains($validated['engagement'], '%') ? $validated['engagement'] : $validated['engagement'].'%') : '-%',
            'date' => $validated['date'] ?? date('Y-m-d'),
            'period' => $validated['period'] ?? 'mingguan',
            'schedule_id' => $validated['schedule_id'] ?? null,
            'idea_id' => $validated['idea_id'] ?? null,
        ];

        $reports[] = $newItem;
        $this->saveReports($reports);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan konten berhasil ditambahkan!',
                'data' => $newItem,
                'reports' => $reports
            ]);
        }

        return redirect()->route('laporan-sosmed')->with('success', 'Laporan konten berhasil ditambahkan!');
    }

    public function apiUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'required|string',
            'tag' => 'nullable|string',
            'followers' => 'nullable|string',
            'tayangan' => 'nullable|string',
            'jangkauan' => 'nullable|string',
            'engagement' => 'nullable|string',
            'date' => 'nullable|string',
            'period' => 'nullable|string',
        ]);

        $reports = $this->getReports();
        $found = false;

        foreach ($reports as &$item) {
            if ($item['id'] == $id) {
                $item['title'] = $validated['title'];
                $item['description'] = $validated['description'] ?? $item['description'];
                $item['platform'] = strtolower($validated['platform']);
                $item['tag'] = $validated['tag'] ?: ucfirst($validated['platform']);
                $item['followers'] = !empty($validated['followers']) ? $validated['followers'] : '-';
                $item['tayangan'] = !empty($validated['tayangan']) ? $validated['tayangan'] : '-';
                $item['jangkauan'] = !empty($validated['jangkauan']) ? $validated['jangkauan'] : '-';
                $item['engagement'] = !empty($validated['engagement']) ? (str_contains($validated['engagement'], '%') ? $validated['engagement'] : $validated['engagement'].'%') : '-%';
                if (!empty($validated['date'])) $item['date'] = $validated['date'];
                if (!empty($validated['period'])) $item['period'] = $validated['period'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan'], 404);
            }
            return redirect()->route('laporan-sosmed')->with('error', 'Laporan tidak ditemukan');
        }

        $this->saveReports($reports);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil diperbarui!',
                'reports' => $reports
            ]);
        }

        return redirect()->route('laporan-sosmed')->with('success', 'Laporan berhasil diperbarui!');
    }

    public function apiDestroy(Request $request, $id)
    {
        $reports = $this->getReports();
        $filtered = array_filter($reports, function ($item) use ($id) {
            return $item['id'] != $id;
        });

        $this->saveReports($filtered);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil dihapus!',
                'reports' => array_values($filtered)
            ]);
        }

        return redirect()->route('laporan-sosmed')->with('success', 'Laporan berhasil dihapus!');
    }

    public function apiFromSchedule(Request $request, $id)
    {
        $schedules = $this->getSchedules();
        $schedule = null;
        foreach ($schedules as $s) {
            if ($s['id'] == $id) {
                $schedule = $s;
                break;
            }
        }
        if (!$schedule) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        $reports = $this->getReports();
        $newId = count($reports) > 0 ? (max(array_column($reports, 'id')) + 1) : 1;

        $newReport = [
            'id' => $newId,
            'title' => $schedule['title'],
            'description' => $schedule['description'] ?: 'Diposting dari Content Planner',
            'platform' => strtolower($schedule['platform']),
            'tag' => $schedule['tag'] ?: ucfirst($schedule['platform']),
            'followers' => $request->input('followers', '-'),
            'tayangan' => $request->input('tayangan', '-'),
            'jangkauan' => $request->input('jangkauan', '-'),
            'engagement' => $request->input('engagement', '-%'),
            'date' => $schedule['date'] ?? date('Y-m-d'),
            'period' => $request->input('period', 'mingguan'),
            'schedule_id' => $schedule['id'],
            'idea_id' => $schedule['idea_id'] ?? null,
        ];

        $reports[] = $newReport;
        $this->saveReports($reports);

        return response()->json([
            'status' => 'success',
            'message' => "Jadwal '{$schedule['title']}' berhasil disinkronkan ke Laporan Sosmed!",
            'data' => $newReport,
            'reports' => $reports
        ]);
    }

    public function apiFromIdea(Request $request, $id)
    {
        $ideas = $this->getIdeas();
        $idea = null;
        foreach ($ideas as $item) {
            if ($item['id'] == $id) {
                $idea = $item;
                break;
            }
        }
        if (!$idea) {
            return response()->json(['status' => 'error', 'message' => 'Ide tidak ditemukan'], 404);
        }

        $reports = $this->getReports();
        $newId = count($reports) > 0 ? (max(array_column($reports, 'id')) + 1) : 1;

        $newReport = [
            'id' => $newId,
            'title' => $idea['title'],
            'description' => $idea['description'] ?: 'Diposting dari Ide Konten',
            'platform' => strtolower($idea['platform']),
            'tag' => $idea['category'] ?: ucfirst($idea['platform']),
            'followers' => $request->input('followers', '-'),
            'tayangan' => $request->input('tayangan', '-'),
            'jangkauan' => $request->input('jangkauan', '-'),
            'engagement' => $request->input('engagement', '-%'),
            'date' => date('Y-m-d'),
            'period' => $request->input('period', 'mingguan'),
            'schedule_id' => null,
            'idea_id' => $idea['id'],
        ];

        $reports[] = $newReport;
        $this->saveReports($reports);

        return response()->json([
            'status' => 'success',
            'message' => "Ide '{$idea['title']}' berhasil disinkronkan ke Laporan Sosmed!",
            'data' => $newReport,
            'reports' => $reports
        ]);
    }

    public function exportCsv()
    {
        $reports = $this->getReports();
        $filename = "laporan-sosmed-creatorpulse-" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Judul Konten', 'Platform', 'Tag/Format', 'Followers', 'Tayangan', 'Jangkauan', 'Engagement', 'Tanggal', 'Periode', 'Deskripsi']);

            foreach ($reports as $row) {
                fputcsv($handle, [
                    $row['id'] ?? '',
                    $row['title'] ?? '',
                    $row['platform'] ?? '',
                    $row['tag'] ?? '',
                    $row['followers'] ?? '-',
                    $row['tayangan'] ?? '-',
                    $row['jangkauan'] ?? '-',
                    $row['engagement'] ?? '-%',
                    $row['date'] ?? '',
                    $row['period'] ?? '',
                    $row['description'] ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
