<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanSosmedController extends Controller
{
    protected function getReports()
    {
        return DB::table('reports')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
    }

    protected function getSchedules()
    {
        return DB::table('schedules')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
    }

    protected function getIdeas()
    {
        return DB::table('ideas')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
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

        $newItem = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? 'Diposting baru saja',
            'platform' => strtolower($validated['platform']),
            'tag' => ($validated['tag'] ?? null) ?: ucfirst($validated['platform']),
            'followers' => !empty($validated['followers']) ? $validated['followers'] : '-',
            'tayangan' => !empty($validated['tayangan']) ? $validated['tayangan'] : '-',
            'jangkauan' => !empty($validated['jangkauan']) ? $validated['jangkauan'] : '-',
            'engagement' => !empty($validated['engagement']) ? (str_contains($validated['engagement'], '%') ? $validated['engagement'] : $validated['engagement'].'%') : '-%',
            'date' => $validated['date'] ?? date('Y-m-d'),
            'period' => $validated['period'] ?? 'mingguan',
            'schedule_id' => $validated['schedule_id'] ?? null,
            'idea_id' => $validated['idea_id'] ?? null,
        ];

        $newItem['id'] = DB::table('reports')->insertGetId($newItem);
        $reports = $this->getReports();

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

        $report = DB::table('reports')->where('id', $id)->first();
        if (!$report) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan'], 404);
            }
            return redirect()->route('laporan-sosmed')->with('error', 'Laporan tidak ditemukan');
        }

        DB::table('reports')->where('id', $id)->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $report->description,
            'platform' => strtolower($validated['platform']),
            'tag' => ($validated['tag'] ?? null) ?: ucfirst($validated['platform']),
            'followers' => !empty($validated['followers']) ? $validated['followers'] : '-',
            'tayangan' => !empty($validated['tayangan']) ? $validated['tayangan'] : '-',
            'jangkauan' => !empty($validated['jangkauan']) ? $validated['jangkauan'] : '-',
            'engagement' => !empty($validated['engagement']) ? (str_contains($validated['engagement'], '%') ? $validated['engagement'] : $validated['engagement'].'%') : '-%',
            'date' => !empty($validated['date']) ? $validated['date'] : $report->date,
            'period' => !empty($validated['period']) ? $validated['period'] : $report->period,
        ]);
        $reports = $this->getReports();

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
        DB::table('reports')->where('id', $id)->delete();
        $reports = $this->getReports();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil dihapus!',
                'reports' => $reports
            ]);
        }

        return redirect()->route('laporan-sosmed')->with('success', 'Laporan berhasil dihapus!');
    }

    public function apiFromSchedule(Request $request, $id)
    {
        $schedule = DB::table('schedules')->where('id', $id)->first();
        if (!$schedule) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan'], 404);
        }
        $schedule = (array) $schedule;

        $newReport = [
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

        $newReport['id'] = DB::table('reports')->insertGetId($newReport);
        $reports = $this->getReports();

        return response()->json([
            'status' => 'success',
            'message' => "Jadwal '{$schedule['title']}' berhasil disinkronkan ke Laporan Sosmed!",
            'data' => $newReport,
            'reports' => $reports
        ]);
    }

    public function apiFromIdea(Request $request, $id)
    {
        $idea = DB::table('ideas')->where('id', $id)->first();
        if (!$idea) {
            return response()->json(['status' => 'error', 'message' => 'Ide tidak ditemukan'], 404);
        }
        $idea = (array) $idea;

        $newReport = [
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

        $newReport['id'] = DB::table('reports')->insertGetId($newReport);
        $reports = $this->getReports();

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
