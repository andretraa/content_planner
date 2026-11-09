<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TABLES = [
        'schedules' => [
            'platform', 'tag', 'title', 'date', 'time', 'status', 'description', 'idea_id',
        ],
        'ideas' => [
            'title', 'description', 'platform', 'category', 'column', 'badge', 'time_ago', 'scheduled_note',
        ],
        'reports' => [
            'title', 'description', 'platform', 'tag', 'followers', 'tayangan', 'jangkauan',
            'engagement', 'date', 'period', 'schedule_id', 'idea_id',
        ],
    ];

    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('platform');
            $table->string('tag')->nullable();
            $table->string('title');
            $table->string('date');
            $table->string('time');
            $table->string('status');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('idea_id')->nullable();
        });

        Schema::create('ideas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform');
            $table->string('category')->default('General');
            $table->string('column');
            $table->string('badge')->nullable();
            $table->string('time_ago')->nullable();
            $table->string('scheduled_note')->nullable();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform');
            $table->string('tag')->nullable();
            $table->string('followers')->default('-');
            $table->string('tayangan')->default('-');
            $table->string('jangkauan')->default('-');
            $table->string('engagement')->default('-%');
            $table->string('date')->nullable();
            $table->string('period')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->unsignedBigInteger('idea_id')->nullable();
        });

        $this->importLegacyJson();
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('ideas');
        Schema::dropIfExists('schedules');
    }

    private function importLegacyJson(): void
    {
        foreach (self::LEGACY_TABLES as $table => $fields) {
            $path = storage_path("app/{$table}.json");
            if (!is_file($path)) {
                continue;
            }

            $records = json_decode(file_get_contents($path), true);
            if (!is_array($records)) {
                throw new \RuntimeException("Unable to import legacy data from {$path}: invalid JSON.");
            }

            foreach (array_chunk($records, 500) as $chunk) {
                $rows = [];
                foreach ($chunk as $record) {
                    if (!is_array($record) || !isset($record['id']) || !is_numeric($record['id'])) {
                        throw new \RuntimeException("Unable to import legacy data from {$path}: a record has no numeric ID.");
                    }

                    $row = ['id' => (int) $record['id']];
                    foreach ($fields as $field) {
                        $row[$field] = $record[$field] ?? match ($field) {
                            'category' => 'General',
                            'followers', 'tayangan', 'jangkauan' => '-',
                            'engagement' => '-%',
                            default => null,
                        };
                    }
                    $rows[] = $row;
                }

                if ($rows !== []) {
                    DB::table($table)->insert($rows);
                }
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement(
                    "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$table}"
                );
            }
        }
    }
};
