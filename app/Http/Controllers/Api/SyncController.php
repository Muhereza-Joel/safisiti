<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\CollectionRoute;
use App\Models\Contact;
use App\Models\Preference;
use App\Models\User;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    protected $syncableModels = [
        'users' => User::class,
        'contacts' => Contact::class,
        'preferences' => Preference::class,
        'bug_reports' => BugReport::class,
        'collection_routes' => CollectionRoute::class,
        'wards' => Ward::class,
    ];

    public function syncPull(Request $request)
    {
        $lastPulledAt = $request->input('last_pulled_at');
        $lastSyncTime = $lastPulledAt ? Carbon::createFromTimestampMs($lastPulledAt, 'UTC') : null;
        Log::info("Sync pull requested", ['last_pulled_at' => $lastPulledAt]);

        $knownRecords = json_decode($request->input('known_records', '{}'), true);
        $changes = [];

        foreach ($this->syncableModels as $table => $model) {
            $changes[$table] = $lastSyncTime
                ? $this->syncPullIncremental($model, $table, $request, $lastSyncTime, $knownRecords)
                : $this->syncPullFirstTime($model, $request);
        }

        return response()->json([
            'changes' => $changes,
            'timestamp' => now()->timestamp * 1000,
        ]);
    }

    protected function syncPullFirstTime($model, Request $request)
    {
        $query = $this->applyScopes($model, $request);

        return [
            'created' => $query->get(),
            'updated' => collect(),
            'deleted' => collect(),
        ];
    }

    protected function syncPullIncremental($model, $table, Request $request, $lastSyncTime, $knownRecords)
    {
        $query = $this->applyScopes($model, $request);
        $knownIds = $knownRecords[$table] ?? [];

        // For WatermelonDB with sendCreatedAsUpdated: true, we combine created and updated
        $changedRecords = (clone $query)
            ->where(function ($q) use ($lastSyncTime, $knownIds) {
                $q->where('created_at', '>', $lastSyncTime)
                    ->orWhere('updated_at', '>', $lastSyncTime)
                    ->orWhere(function ($q) use ($lastSyncTime, $knownIds) {
                        $q->where('created_at', '<=', $lastSyncTime)
                            ->whereNotIn('id', $knownIds);
                    });
            })
            ->get();

        return [
            'created' => collect(), // Empty for sendCreatedAsUpdated
            'updated' => $changedRecords, // All changes go here
            'deleted' => $this->getDeletedRecords($model, $query, $lastSyncTime),
        ];
    }

    protected function applyScopes($model, Request $request)
    {
        $query = $model::query();

        if (Schema::hasColumn((new $model)->getTable(), 'user_id')) {
            $query->where('user_id', $request->user()->id);
        }
        if (Schema::hasColumn((new $model)->getTable(), 'organisation_id')) {
            $query->where('organisation_id', $request->user()->organisation_id);
        }

        return $query;
    }

    protected function getDeletedRecords($model, $query, $lastSyncTime)
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            return (clone $query)
                ->onlyTrashed()
                ->where('deleted_at', '>', $lastSyncTime)
                ->pluck('id');
        }

        return collect();
    }

    public function syncPush(Request $request)
    {
        $allChanges = $request->input('changes', []);

        DB::transaction(function () use ($allChanges, $request) {
            foreach ($allChanges as $table => $data) {
                if (!isset($this->syncableModels[$table])) {
                    continue;
                }

                $model = $this->syncableModels[$table];

                // Process all changes (created/updated treated the same way)
                foreach (array_merge($data['created'] ?? [], $data['updated'] ?? []) as $record) {
                    $this->syncRecord($model, $record, $request);
                }

                foreach ($data['deleted'] ?? [] as $id) {
                    $model::where('id', $id)->delete();
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function syncRecord($model, $record, Request $request)
    {
        $existing = $model::withTrashed()->where('uuid', $record['uuid'] ?? null)->first();
        $clientUpdatedAt = isset($record['updated_at'])
            ? Carbon::createFromTimestampMs($record['updated_at'], 'UTC')
            : null;

        $recordData = $this->mapRecordFields($record, $model);

        // Security: Never allow these fields to be updated from client
        unset(
            $recordData['organisation_id'],
            $recordData['user_id'],
            $recordData['password'],
            $recordData['password_confirmation']
        );

        if ($existing) {
            if ($clientUpdatedAt && $existing->updated_at > $clientUpdatedAt) {
                return; // Server wins in conflict
            }

            if ($existing->trashed() && method_exists($existing, 'restore')) {
                $existing->restore();
            }

            $existing->fill($recordData)->save();
        } elseif (isset($record['uuid'])) {
            // For new records, set protected fields from authenticated user
            if (Schema::hasColumn((new $model)->getTable(), 'user_id')) {
                $recordData['user_id'] = $request->user()->id;
            }
            if (Schema::hasColumn((new $model)->getTable(), 'organisation_id')) {
                $recordData['organisation_id'] = $request->user()->organisation_id;
            }

            $model::create($recordData);
        }
    }

    protected function mapRecordFields(array $record, $model = null): array
    {
        $excludedFields = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'password_confirmation',
            'remember_token'
        ];

        if (!$model && isset($record['_table'])) {
            $model = $this->syncableModels[$record['_table']] ?? null;
        }

        if ($model) {
            $fillable = (new $model)->getFillable();
            return collect($record)
                ->only($fillable)
                ->except($excludedFields)
                ->toArray();
        }

        return collect($record)
            ->except($excludedFields)
            ->toArray();
    }
}
