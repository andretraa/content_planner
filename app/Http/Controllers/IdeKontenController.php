<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdeKontenController extends Controller
{
    protected function getStoragePath()
    {
        return storage_path('app/ideas.json');
    }

    protected function getIdeas()
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    protected function saveIdeas(array $ideas)
    {
        file_put_contents($this->getStoragePath(), json_encode(array_values($ideas), JSON_PRETTY_PRINT));
    }

    public function index()
    {
        $ideas = $this->getIdeas();
        return view('ide-konten', [
            'activePage' => 'ide-konten',
            'ideas' => $ideas
        ]);
    }

    public function apiList()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->getIdeas()
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'required|string',
            'category' => 'nullable|string',
            'column' => 'required|string|in:ideas,in_progress,ready_to_shoot,completed',
            'badge' => 'nullable|string',
        ]);

        $ideas = $this->getIdeas();
        $newId = count($ideas) > 0 ? (max(array_column($ideas, 'id')) + 1) : 1;

        $newItem = [
            'id' => $newId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'platform' => strtolower($validated['platform']),
            'category' => $validated['category'] ?: 'General',
            'column' => $validated['column'],
            'badge' => $validated['badge'] ?: null,
            'time_ago' => 'Baru saja'
        ];

        $ideas[] = $newItem;
        $this->saveIdeas($ideas);

        return response()->json([
            'status' => 'success',
            'message' => 'Ide konten berhasil ditambahkan!',
            'data' => $newItem,
            'ideas' => $ideas
        ]);
    }

    public function apiUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'required|string',
            'category' => 'nullable|string',
            'column' => 'required|string|in:ideas,in_progress,ready_to_shoot,completed',
            'badge' => 'nullable|string',
        ]);

        $ideas = $this->getIdeas();
        $found = false;

        foreach ($ideas as &$item) {
            if ($item['id'] == $id) {
                $item['title'] = $validated['title'];
                $item['description'] = $validated['description'] ?? '';
                $item['platform'] = strtolower($validated['platform']);
                $item['category'] = $validated['category'] ?: $item['category'];
                $item['column'] = $validated['column'];
                if (isset($validated['badge'])) $item['badge'] = $validated['badge'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json(['status' => 'error', 'message' => 'Ide tidak ditemukan'], 404);
        }

        $this->saveIdeas($ideas);

        return response()->json([
            'status' => 'success',
            'message' => 'Ide berhasil diperbarui!',
            'ideas' => $ideas
        ]);
    }

    public function apiDestroy($id)
    {
        $ideas = $this->getIdeas();
        $filtered = array_filter($ideas, function ($item) use ($id) {
            return $item['id'] != $id;
        });

        $this->saveIdeas($filtered);

        return response()->json([
            'status' => 'success',
            'message' => 'Ide berhasil dihapus!',
            'ideas' => array_values($filtered)
        ]);
    }

    public function apiSendToPlanner(Request $request, $id)
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

        // Add to schedules.json
        $schedPath = storage_path('app/schedules.json');
        $schedules = file_exists($schedPath) ? json_decode(file_get_contents($schedPath), true) : [];
        if (!is_array($schedules)) $schedules = [];

        $newSchedId = count($schedules) > 0 ? (max(array_column($schedules, 'id')) + 1) : 1;

        $date = $request->input('date', date('Y-m-d', strtotime('+2 days')));
        $time = $request->input('time', '14:00');

        $newSchedule = [
            'id' => $newSchedId,
            'platform' => $idea['platform'],
            'tag' => $idea['category'] ?? ucfirst($idea['platform']),
            'title' => $idea['title'],
            'date' => $date,
            'time' => $time,
            'status' => 'scheduled',
            'description' => $idea['description'],
            'idea_id' => $idea['id']
        ];

        $schedules[] = $newSchedule;
        file_put_contents($schedPath, json_encode(array_values($schedules), JSON_PRETTY_PRINT));

        // Update idea column to ready_to_shoot or completed
        foreach ($ideas as &$item) {
            if ($item['id'] == $id) {
                $item['column'] = 'ready_to_shoot';
                $item['scheduled_note'] = "Scheduled for $date, $time";
                break;
            }
        }
        $this->saveIdeas($ideas);

        return response()->json([
            'status' => 'success',
            'message' => "Ide '{$idea['title']}' berhasil dijadwalkan ke Content Planner!",
            'schedule' => $newSchedule,
            'ideas' => $ideas
        ]);
    }
}

