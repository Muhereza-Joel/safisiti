<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AwarenessCampaign;
use App\Models\BugReport;
use App\Models\Cell;
use App\Models\CollectionBatch;
use App\Models\CollectionPoint;
use App\Models\Section;
use App\Models\CollectionRoute;
use App\Models\CollectionRouteWard;
use App\Models\Contact;
use App\Models\DirectCollection;
use App\Models\Preference;
use App\Models\RecyclingMethod;
use App\Models\RecycleRecord;
use App\Models\User;
use App\Models\Ward;
use App\Models\WasteType;
use App\Models\DumpingSite;
use App\Models\PointScan;
use App\Models\RecyclingCenter;
use App\Models\Vehicle;
use App\Models\WasteCollection;
use App\Models\WorkRotta;
use App\Models\WorkRottaCell;
use App\Models\TeamMember;
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
        'bug_reports' => BugReport::class,
        'collection_routes' => CollectionRoute::class,
        'wards' => Ward::class,
        'collection_route_ward' => CollectionRouteWard::class,
        'cells' => Cell::class,
        'collection_points' => CollectionPoint::class,
        'sections' => Section::class,
        'waste_types' => WasteType::class,
        'recycling_methods' => RecyclingMethod::class,
        'dumping_sites' => DumpingSite::class,
        'recycling_centers' => RecyclingCenter::class,
        'vehicles' => Vehicle::class,
        'collection_batches' => CollectionBatch::class,
        'waste_collections' => WasteCollection::class,
        'awareness_campaigns' => AwarenessCampaign::class,
        'direct_collections' => DirectCollection::class,
        'recycle_records' => RecycleRecord::class,
        'point_scans' => PointScan::class,
        'work_rotta' => WorkRotta::class,
        'work_rotta_cells' => WorkRottaCell::class,
        'team_members' => TeamMember::class,
    ];

    protected $syncWindow = [
        'waste_collections'   => '1 months',
        'direct_collections'   => '6 months',
        'recycle_records'     => '6 months',
        'collection_batches'  => '1 year',
        'awareness_campaigns' => '1 year',
        'bug_reports'         => '6 months',
        'contacts'            => '6 year',
        'point_scans'         => '6 months',
        'work_rotta'          => '3 months',
        'work_rotta_cells'    => '3 months'

        // Leave out tables that you always want fully synced
    ];

    // ** NEW: Array for tables to exclude from user-based filtering **
    protected $excludeUserScope = [
        'waste_collections',
        // Add other tables here as needed, e.g., 'dumping_sites', 'recycling_centers'
    ];

    protected $tableTimestampsCache = [];

    // Define timestamp fields that need conversion from milliseconds to seconds
    protected $timestampFields = [
        'updated_at',
        'created_at',
        'deleted_at',
        'date_conducted',
        'last_collection_date',
        'scanned_at',
        'suspended_until',
        'last_login_at',
        'date'
        // Add more timestamp fields here as needed
    ];

    protected $timeOnlyFields = [
        'start_time',
        'end_time',
        'check_in_time',
        'check_out_time'
    ];



    // Never allow client to overwrite these  Fields
    protected $protectedFields = [
        'organisation_id',
        'user_id',
        'password',
        'password_confirmation'
        // Add more protected fields here as needed
    ];


    public function syncPull(Request $request)
    {
        $lastPulledAt = $request->input('last_pulled_at');
        $lastSyncTime = $lastPulledAt ? Carbon::createFromTimestampMs($lastPulledAt, 'UTC') : null;
        $requestedTable = $request->input('table');

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

        $table = (new $model)->getTable();

        $query = $this->applyScopes($model, $request)
            ->select(array_merge(
                (new $model)->getFillable(),
                ['uuid', 'id'],
                $this->timestampColumns((new $model)->getTable())
            ));

        // Exclude soft-deleted records for first-time sync
        if ($this->modelUsesSoftDeletes($model)) {
            $query->whereNull('deleted_at');
        }

        // ⏳ Apply cutoff window if configured
        if (isset($this->syncWindow[$table]) && Schema::hasColumn($table, 'created_at')) {
            $cutoff = now()->sub($this->syncWindow[$table]);
            $query->where('created_at', '>=', $cutoff);
        }

        $query->orderBy('updated_at', 'asc')
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
        $query = $this->applyScopes($model, $request)
            ->where(function ($q) use ($lastSyncTime) {
                $q->where('created_at', '>', $lastSyncTime)
                    ->orWhere('updated_at', '>', $lastSyncTime);
            });

        // Only exclude soft-deleted records if the table has a deleted_at column
        if (Schema::hasColumn((new $model)->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        // ⏳ Apply cutoff window if configured
        if (isset($this->syncWindow[$table]) && Schema::hasColumn((new $model)->getTable(), 'created_at')) {
            $cutoff = now()->sub($this->syncWindow[$table]);
            $query->where('created_at', '>=', $cutoff);
        }

        $updatedRecords = collect();

        $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->chunk(500, function ($chunk) use (&$updatedRecords) {
                $updatedRecords = $updatedRecords->merge($chunk);
            });

        // Create a new query for deleted records instead of cloning the filtered one
        $deletedRecords = $this->getDeletedRecords($model, $request, $lastSyncTime);


        $restoredRecords = $this->getRestoredRecords($model, $request, $lastSyncTime);

        return [
            'created' => $restoredRecords->map(fn($r) => $this->serializeRecord($r)),
            'updated' => $updatedRecords->map(fn($r) => $this->serializeRecord($r)),
            'deleted' => $deletedRecords,
        ];
    }

    protected function serializeRecord($record)
    {
        $table = $record->getTable();
        $timestamps = $this->timestampColumns($table); // Gets ['created_at', 'updated_at', 'deleted_at']

        // 1. Start with base fields
        $serializedData = [
            'uuid' => $record->uuid,
            'id'   => $record->id,
        ];

        // 2. Get ALL fillable fields and add them
        $fillableFields = $record->only((new $record)->getFillable());
        $serializedData = $serializedData + $fillableFields;

        // 3. OVERRIDE standard timestamps (to ensure milliseconds)
        if (in_array('created_at', $timestamps)) {
            $serializedData['created_at'] = $record->created_at?->getTimestampMs();
        }
        if (in_array('updated_at', $timestamps)) {
            $serializedData['updated_at'] = $record->updated_at?->getTimestampMs();
        }
        if (in_array('deleted_at', $timestamps)) {
            $serializedData['deleted_at'] = $record->deleted_at?->getTimestampMs();
        }

        // 4. NEW: OVERRIDE time-only fields (to ensure H:i:s format)
        foreach ($this->timeOnlyFields as $field) {
            // Check if the field exists in the data we're about to send
            if (array_key_exists($field, $serializedData)) {
                // Only format if it's not null
                if (!is_null($serializedData[$field])) {
                    try {
                        // Parse whatever it is (Carbon, string) and format to H:i:s
                        $serializedData[$field] = Carbon::parse($serializedData[$field])->format('H:i:s');
                    } catch (\Exception $e) {
                        Log::warning("Could not parse time field '$field' during syncPull: " . $serializedData[$field]);
                        $serializedData[$field] = null; // Set to null on failure
                    }
                }
            }
        }

        // 5. NEW: OVERRIDE other timestamp fields (like 'date') to ensure milliseconds
        foreach ($this->timestampFields as $field) {
            // Skip fields we've already handled
            if (in_array($field, $this->timeOnlyFields) || in_array($field, $timestamps)) {
                continue;
            }

            // Check if the field exists in the data
            if (array_key_exists($field, $serializedData)) {
                if (!is_null($serializedData[$field])) {
                    try {
                        // Parse whatever it is and convert to Milliseconds
                        $serializedData[$field] = Carbon::parse($serializedData[$field])->getTimestampMs();
                    } catch (\Exception $e) {
                        Log::warning("Could not parse timestamp field '$field' during syncPull: " . $serializedData[$field]);
                        $serializedData[$field] = null;
                    }
                }
            }
        }

        return $serializedData;
    }

    /**
     * Apply scopes for user and organisation filtering.
     * **MODIFIED: Now uses $excludeUserScope to skip user_id filtering for specific tables.**
     */
    protected function applyScopes($model, Request $request)
    {
        $query = $this->queryFor($model);
        $tableName = (new $model)->getTable();

        // Check if the table is in the exclusion list before applying user_id scope
        if (!in_array($tableName, $this->excludeUserScope)) {
            if (Schema::hasColumn($tableName, 'user_id')) {
                $query->where('user_id', $request->user()->id);
            }
        }

        // Organisation scope is applied regardless of the exclusion list
        if (Schema::hasColumn($tableName, 'organisation_id')) {
            $query->where('organisation_id', $request->user()->organisation_id);
        }

        return $query;
    }

    protected function getRestoredRecords($model, Request $request, $lastSyncTime)
    {
        if ($this->modelUsesSoftDeletes($model)) {
            $table = (new $model)->getTable();

            // Alternative approach: Look for records that were in the trash at lastSyncTime but are now restored
            $restoredQuery = $this->applyScopes($model, $request)
                ->where(function ($q) use ($lastSyncTime) {
                    // Records that have been updated (likely due to restoration)
                    $q->where('updated_at', '>', $lastSyncTime)
                        // And were previously soft-deleted (we infer this by checking if they appear in trashed records from before)
                        ->where(function ($q2) use ($lastSyncTime) {
                            $q2->whereNotNull('deleted_at')
                                ->orWhere('deleted_at', '<=', $lastSyncTime);
                        });
                })
                ->whereNull('deleted_at'); // But are now restored

            // ⏳ Apply cutoff window if configured
            if (isset($this->syncWindow[$table]) && Schema::hasColumn($table, 'created_at')) {
                $cutoff = now()->sub($this->syncWindow[$table]);
                $restoredQuery->where('created_at', '>=', $cutoff);
            }

            return $restoredQuery
                ->orderBy('updated_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        return collect();
    }

    protected function getDeletedRecords($model, Request $request, $lastSyncTime)
    {
        if ($this->modelUsesSoftDeletes($model)) {
            $table = (new $model)->getTable();

            // Create a new query without the deleted_at filter
            $deletedQuery = $this->applyScopes($model, $request)
                ->onlyTrashed()
                ->where('deleted_at', '>', $lastSyncTime);

            // ⏳ Apply cutoff window if configured
            if (isset($this->syncWindow[$table]) && Schema::hasColumn($table, 'created_at')) {
                $cutoff = now()->sub($this->syncWindow[$table]);
                $deletedQuery->where('created_at', '>=', $cutoff);
            }

            return $deletedQuery
                ->get(['uuid', 'deleted_at', 'created_at'])
                ->map(function ($record) {
                    return [
                        'uuid'       => $record->uuid,
                        'deleted_at' => $record->deleted_at
                            ? $record->deleted_at->getTimestampMs()
                            : null,
                    ];
                });
        }

        return collect();
    }


    /**
     * NEW: Refactored syncPush to use batch operations (upsert, whereIn)
     * This massively reduces the number of queries from N+1 to ~2 per table.
     */
    public function syncPush(Request $request)
    {
        $allChanges = $request->input('changes', []);

        DB::transaction(function () use ($allChanges, $request) {
            foreach ($allChanges as $table => $data) {
                if (!isset($this->syncableModels[$table])) {
                    continue;
                }

                $modelClass = $this->syncableModels[$table];
                $modelInstance = new $modelClass;
                $tableName = $modelInstance->getTable();

                // --- 1. Batch Create/Update (Upsert) ---
                $upsertData = [];
                $recordsToUpsert = array_merge($data['created'] ?? [], $data['updated'] ?? []);

                foreach ($recordsToUpsert as $record) {
                    // Start with fillable fields
                    $recordData = $this->mapRecordFields($record, $modelClass);

                    // Add uuid, as mapRecordFields might remove it
                    if (isset($record['uuid'])) {
                        $recordData['uuid'] = $record['uuid'];
                    }

                    // Convert timestamps (from old syncRecord)
                    foreach ($this->timestampFields as $field) {
                        if (isset($recordData[$field])) {
                            $timestamp = (int) $recordData[$field];
                            if ($timestamp > 9999999999) { // Is milliseconds
                                $timestamp = (int) floor($timestamp / 1000);
                            }
                            $recordData[$field] = Carbon::createFromTimestamp($timestamp, 'UTC');
                        }
                    }

                    // NEW: Convert time-only fields
                    foreach ($this->timeOnlyFields as $field) {
                        if (isset($recordData[$field]) && !is_null($recordData[$field])) {
                            try {
                                // Parse the time string (e.g., "09:00" or "09:00:00")
                                // and format it for the database.
                                $recordData[$field] = Carbon::parse($recordData[$field])->format('H:i:s');
                            } catch (\Exception $e) {
                                // If parsing fails, set to null
                                Log::warning("Invalid time format for $field in syncPush: " . $recordData[$field]);
                                $recordData[$field] = null;
                            }
                        }
                    }

                    // Remove protected fields (from old syncRecord)
                    foreach ($this->protectedFields as $field) {
                        unset($recordData[$field]);
                    }

                    // Add server-generated fields (from old syncRecord)
                    // These will be used for INSERT, but not for UPDATE (see below)
                    $serverGeneratedFields = [
                        'user_id' => $request->user()->id,
                        'organisation_id' => $request->user()->organisation_id
                    ];

                    foreach ($serverGeneratedFields as $field => $value) {
                        if (Schema::hasColumn($tableName, $field)) {
                            $recordData[$field] = $value;
                        }
                    }

                    // Handle restore-on-update
                    if ($this->modelUsesSoftDeletes($modelClass)) {
                        $recordData['deleted_at'] = null;
                    }

                    $upsertData[] = $recordData;
                }

                if (!empty($upsertData)) {
                    // Define columns that should be UPDATED on duplicate key
                    // We take all keys from the first record, then remove ones we never update
                    $updateColumns = collect(array_keys($upsertData[0]))
                        ->diff($this->protectedFields) // 'user_id', 'organisation_id'
                        ->diff(['uuid', 'id', 'created_at']) // Never update these
                        ->all();

                    // Make sure deleted_at is in update list to handle restores
                    if ($this->modelUsesSoftDeletes($modelClass) && !in_array('deleted_at', $updateColumns)) {
                        $updateColumns[] = 'deleted_at';
                    }

                    $modelClass::upsert($upsertData, ['uuid'], $updateColumns);
                }

                // --- 2. Batch Delete ---
                $deletedUuids = $data['deleted'] ?? [];
                if (!empty($deletedUuids)) {
                    $query = $modelClass::whereIn('uuid', $deletedUuids);

                    // Scope deletes to the user/org
                    if (Schema::hasColumn($tableName, 'user_id') && !in_array($tableName, $this->excludeUserScope)) {
                        $query->where('user_id', $request->user()->id);
                    }
                    if (Schema::hasColumn($tableName, 'organisation_id')) {
                        $query->where('organisation_id', $request->user()->organisation_id);
                    }

                    if ($this->modelUsesSoftDeletes($modelClass)) {
                        $query->delete();
                    } else {
                        $query->forceDelete();
                    }
                }
            }
        });

        return response()->json(['status' => 'ok']);
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
            // We also need to include our custom timestamp fields that might not be fillable
            $allFields = array_unique(array_merge(
                $fillable,
                $this->timestampFields,
                $this->timeOnlyFields // <-- ADD THIS
            ));

            return collect($record)
                ->only($allFields) // Use combined list
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
