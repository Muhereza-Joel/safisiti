<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\CollectionRoute;
use App\Models\CollectionRouteWard;
use App\Models\Contact;
use App\Models\Preference;
use App\Models\User;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'collection_route_ward' => CollectionRouteWard::class
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
            'created' => collect(),
            'updated' => $changedRecords,
            'deleted' => $this->getDeletedRecords($model, $query, $lastSyncTime),
        ];
    }

    protected function applyScopes($model, Request $request)
    {
        $query = $this->queryFor($model);

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
        if ($this->modelUsesSoftDeletes($model)) {
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

                foreach (array_merge($data['created'] ?? [], $data['updated'] ?? []) as $record) {
                    $this->syncRecord($model, $record, $request);
                }

                foreach ($data['deleted'] ?? [] as $id) {
                    $this->deleteRecord($model, $id);
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function syncRecord($modelClass, $record, Request $request)
    {
        // Use withTrashed only if model supports it
        if (in_array(SoftDeletes::class, class_uses($modelClass))) {
            $existing = $modelClass::withTrashed()->where('uuid', $record['uuid'] ?? null)->first();
        } else {
            $existing = $modelClass::where('uuid', $record['uuid'] ?? null)->first();
        }

        // Convert timestamp to Carbon
        $clientUpdatedAt = isset($record['updated_at'])
            ? Carbon::createFromTimestampMs($record['updated_at'], 'UTC')
            : null;

        $recordData = $this->mapRecordFields($record, $modelClass);

        // Never allow client to overwrite these IDs
        unset($recordData['organisation_id'], $recordData['user_id'], $recordData['password'], $recordData['password_confirmation']);

        if ($existing) {
            // Server wins if more recent
            if ($clientUpdatedAt && $existing->updated_at > $clientUpdatedAt) {
                return $existing;
            }

            // Restore soft-deleted record if needed
            if (in_array(SoftDeletes::class, class_uses($modelClass)) && $existing->trashed()) {
                $existing->restore();
            }

            $existing->fill($recordData)->save();
            return $existing;
        }

        // Create new record
        if (isset($record['uuid'])) {
            if (Schema::hasColumn((new $modelClass)->getTable(), 'user_id')) {
                $recordData['user_id'] = $request->user()->id;
            }
            if (Schema::hasColumn((new $modelClass)->getTable(), 'organisation_id')) {
                $recordData['organisation_id'] = $request->user()->organisation_id;
            }

            return $modelClass::create($recordData);
        }

        return null;
    }

    protected function deleteRecord($model, $id)
    {
        if ($this->modelUsesSoftDeletes($model)) {
            $model::where('id', $id)->delete();
        } else {
            $model::where('id', $id)->forceDelete();
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

    /**
     * Get query builder for the model, including trashed records if supported.
     */
    protected function queryFor($model)
    {
        return $this->modelUsesSoftDeletes($model)
            ? $model::withTrashed()
            : $model::query();
    }

    /**
     * Check if the given model uses SoftDeletes
     */
    protected function modelUsesSoftDeletes($model): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model));
    }
}
