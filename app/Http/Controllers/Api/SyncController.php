<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\Cell;
use App\Models\CollectionBatch;
use App\Models\CollectionPoint;
use App\Models\CollectionRoute;
use App\Models\CollectionRouteWard;
use App\Models\Contact;
use App\Models\Preference;
use App\Models\RecyclingMethod;
use App\Models\User;
use App\Models\Ward;
use App\Models\WasteType;
use App\Models\DumpingSite;
use App\Models\RecyclingCenter;
use App\Models\Vehicle;
use App\Models\WasteCollection;
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
        'collection_route_ward' => CollectionRouteWard::class,
        'cells' => Cell::class,
        'collection_points' => CollectionPoint::class,
        'waste_types' => WasteType::class,
        'recycling_methods' => RecyclingMethod::class,
        'dumping_sites' => DumpingSite::class,
        'recycling_centers' => RecyclingCenter::class,
        'vehicles' => Vehicle::class,
        'collection_batches' => CollectionBatch::class,
        'waste_collection' => WasteCollection::class,
    ];

    protected $tableTimestampsCache = [];

    public function syncPull(Request $request)
    {
        $lastPulledAt = $request->input('last_pulled_at');
        $lastSyncTime = $lastPulledAt ? Carbon::createFromTimestampMs($lastPulledAt, 'UTC') : null;
        $requestedTable = $request->input('table');

        Log::info("Sync pull requested", [
            'last_pulled_at' => $lastPulledAt,
            'table' => $requestedTable
        ]);

        $changes = [];

        // Handle table-specific request
        if ($requestedTable && isset($this->syncableModels[$requestedTable])) {
            $model = $this->syncableModels[$requestedTable];
            $changes[$requestedTable] = $lastSyncTime
                ? $this->syncPullIncremental($model, $requestedTable, $request, $lastSyncTime)
                : $this->syncPullFirstTime($model, $request);
        }
        // Full sync for all tables
        else {
            foreach ($this->syncableModels as $table => $model) {
                $changes[$table] = $lastSyncTime
                    ? $this->syncPullIncremental($model, $table, $request, $lastSyncTime)
                    : $this->syncPullFirstTime($model, $request);
            }
        }

        return response()->json([
            'changes' => $changes,
            'timestamp' => now()->timestamp * 1000,
        ]);
    }


    protected function syncPullFirstTime($model, Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 1000);

        Log::info("First-time sync for table: " . (new $model)->getTable(), [
            'page' => $page,
            'per_page' => $perPage
        ]);

        $query = $this->applyScopes($model, $request)
            ->select(array_merge(
                (new $model)->getFillable(),
                ['uuid', 'id'],
                $this->timestampColumns((new $model)->getTable())
            ))

            ->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc');


        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'created' => collect(),
            'updated' => collect($paginated->items())->map(fn($r) => $this->serializeRecord($r)),
            'deleted' => collect(),
            'has_more' => $paginated->hasMorePages(),
            'current_page' => $paginated->currentPage(),
        ];
    }

    protected function timestampColumns($table)
    {
        if (isset($this->tableTimestampsCache[$table])) {
            return $this->tableTimestampsCache[$table];
        }

        $cols = [];
        foreach (['created_at', 'updated_at', 'deleted_at'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $cols[] = $col;
            }
        }

        $this->tableTimestampsCache[$table] = $cols;
        return $cols;
    }



    protected function syncPullIncremental($model, $table, Request $request, $lastSyncTime)
    {
        Log::info("Sync incremental called");
        $query = $this->applyScopes($model, $request)
            ->where(function ($q) use ($lastSyncTime) {
                $q->where('created_at', '>', $lastSyncTime)
                    ->orWhere('updated_at', '>', $lastSyncTime);
            });

        $updatedRecords = collect();

        $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->chunk(500, function ($chunk) use (&$updatedRecords) {
                // merge the chunk into the main collection
                $updatedRecords = $updatedRecords->merge($chunk);
            });

        $deletedRecords = $this->getDeletedRecords($model, clone $query, $lastSyncTime);

        return [
            'created' => collect(),
            'updated' => $updatedRecords->map(fn($r) => $this->serializeRecord($r)),
            'deleted' => $deletedRecords,
        ];
    }

    protected function serializeRecord($record)
    {
        $table = $record->getTable();
        $timestamps = $this->timestampColumns($table);

        $base = [
            'uuid' => $record->uuid,
            'id'   => $record->id,
        ];

        if (in_array('created_at', $timestamps)) {
            $base['created_at'] = $record->created_at?->getTimestampMs();
        }
        if (in_array('updated_at', $timestamps)) {
            $base['updated_at'] = $record->updated_at?->getTimestampMs();
        }
        if (in_array('deleted_at', $timestamps)) {
            $base['deleted_at'] = $record->deleted_at?->getTimestampMs();
        }

        return $base + $record->only((new $record)->getFillable());
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
                ->get(['uuid', 'deleted_at'])
                ->map(function ($record) {
                    return [
                        'uuid' => $record->uuid,
                        'deleted_at' => $record->deleted_at
                            ? $record->deleted_at->getTimestampMs()
                            : null,
                    ];
                });
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

    protected function deleteRecord($model, $uuid)
    {
        if ($this->modelUsesSoftDeletes($model)) {
            $model::where('uuid', $uuid)->delete();
        } else {
            $model::where('uuid', $uuid)->forceDelete();
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
