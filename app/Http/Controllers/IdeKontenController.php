<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdeKontenController extends Controller
{
    protected function getIdeas()
    {
        return DB::table('ideas')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
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

        $newItem = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'platform' => strtolower($validated['platform']),
            'category' => ($validated['category'] ?? null) ?: 'General',
            'column' => $validated['column'],
            'badge' => ($validated['badge'] ?? null) ?: null,
            'time_ago' => 'Baru saja'
        ];

        $newItem['id'] = DB::table('ideas')->insertGetId($newItem);
        $ideas = $this->getIdeas();

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

        $idea = DB::table('ideas')->where('id', $id)->first();
        if (!$idea) {
            return response()->json(['status' => 'error', 'message' => 'Ide tidak ditemukan'], 404);
        }

        $changes = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'platform' => strtolower($validated['platform']),
            'category' => ($validated['category'] ?? null) ?: $idea->category,
            'column' => $validated['column'],
        ];
        if (isset($validated['badge'])) {
            $changes['badge'] = $validated['badge'];
        }

        DB::table('ideas')->where('id', $id)->update($changes);
        $ideas = $this->getIdeas();

        return response()->json([
            'status' => 'success',
            'message' => 'Ide berhasil diperbarui!',
            'ideas' => $ideas
        ]);
    }

    public function apiDestroy($id)
    {
        DB::table('ideas')->where('id', $id)->delete();
        $ideas = $this->getIdeas();

        return response()->json([
            'status' => 'success',
            'message' => 'Ide berhasil dihapus!',
            'ideas' => $ideas
        ]);
    }

    public function apiSendToPlanner(Request $request, $id)
    {
        $date = $request->input('date', date('Y-m-d', strtotime('+2 days')));
        $time = $request->input('time', '14:00');
        $result = DB::transaction(function () use ($id, $date, $time) {
            $idea = DB::table('ideas')->where('id', $id)->lockForUpdate()->first();
            if (!$idea) {
                return null;
            }

            $idea = (array) $idea;
            $newSchedule = [
                'platform' => $idea['platform'],
                'tag' => $idea['category'] ?? ucfirst($idea['platform']),
                'title' => $idea['title'],
                'date' => $date,
                'time' => $time,
                'status' => 'scheduled',
                'description' => $idea['description'],
                'idea_id' => $idea['id'],
            ];
            $newSchedule['id'] = DB::table('schedules')->insertGetId($newSchedule);

            DB::table('ideas')->where('id', $id)->update([
                'column' => 'ready_to_shoot',
                'scheduled_note' => "Scheduled for $date, $time",
            ]);

            return ['idea' => $idea, 'schedule' => $newSchedule];
        });

        if (!$result) {
            return response()->json(['status' => 'error', 'message' => 'Ide tidak ditemukan'], 404);
        }

        $idea = $result['idea'];
        $newSchedule = $result['schedule'];
        $ideas = $this->getIdeas();

        return response()->json([
            'status' => 'success',
            'message' => "Ide '{$idea['title']}' berhasil dijadwalkan ke Content Planner!",
            'schedule' => $newSchedule,
            'ideas' => $ideas
        ]);
    }
}
