<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionPoint extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'category',
        'head_name',
        'phone',
        'email',
        'ward_id',
        'ward_uuid',
        'cell_id',
        'cell_uuid',
        'address',
        'latitude',
        'longitude',
        'structure_type',
        'household_size',
        'collection_frequency',
        'bin_count',
        'bin_type',
        'last_collection_date',
        'notes',
        'organisation_id',
        'organisation_uuid',
        'parent_uuid',
        'custom_name',
        'caretaker_name',
        'caretaker_phone',
        'alternate_contact_name',
        'alternate_contact_phone',
    ];

    protected $casts = [
        'uuid' => 'string',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'household_size' => 'integer',
        'bin_count' => 'integer',
        'last_collection_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * OPTIMIZED BOOTED METHOD
     *
     * This method is now much more efficient and avoids N+1 queries.
     */
    protected static function booted()
    {
        // This event runs BEFORE saving (on create and update)
        static::saving(function ($model) {
            // Use relationship properties instead of new queries.
            // This is "query-free" if the relationship is already set.

            // Example:
            // $point->ward = Ward::find(1);
            // $point->save(); // This event will now be efficient.

            // Sync Ward ID/UUID
            if ($model->isDirty('ward_id') && !$model->isDirty('ward_uuid')) {
                // ward_id changed, update uuid from the loaded relationship
                $model->ward_uuid = $model->ward?->uuid;
            } elseif ($model->isDirty('ward_uuid') && !$model->isDirty('ward_id')) {
                // ward_uuid changed, update id from the loaded relationship
                $model->ward_id = $model->ward?->id;
            }

            // Sync Cell ID/UUID
            if ($model->isDirty('cell_id') && !$model->isDirty('cell_uuid')) {
                $model->cell_uuid = $model->cell?->uuid;
            } elseif ($model->isDirty('cell_uuid') && !$model->isDirty('cell_id')) {
                $model->cell_id = $model->cell?->id;
            }

            // Sync Organisation ID/UUID
            if ($model->isDirty('organisation_id') && !$model->isDirty('organisation_uuid')) {
                $model->organisation_uuid = $model->organisation?->uuid;
            } elseif ($model->isDirty('organisation_uuid') && !$model->isDirty('organisation_id')) {
                $model->organisation_id = $model->organisation?->id;
            }
        });

        // This event runs ONLY when creating a new model
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Auto-fill from authenticated user
            if (Auth::check()) {
                if (empty($model->organisation_id)) {
                    $model->organisation_id = Auth::user()->organisation_id;
                }
                if (empty($model->organisation_uuid)) {
                    // Try to get from user's loaded org, fall back to a query if needed
                    $model->organisation_uuid = Auth::user()->organisation?->uuid
                        ?? Organisation::find(Auth::user()->organisation_id)?->uuid;
                }
            }

            // Sync related UUIDs if only IDs are provided
            // This is now the ONLY place that runs queries, and only if needed.
            if (!empty($model->ward_id) && empty($model->ward_uuid)) {
                $model->ward_uuid = Ward::find($model->ward_id)?->uuid;
            }

            if (!empty($model->cell_id) && empty($model->cell_uuid)) {
                $model->cell_uuid = Cell::find($model->cell_id)?->uuid;
            }

            // Sync related IDs if only UUIDs are provided
            if (!empty($model->ward_uuid) && empty($model->ward_id)) {
                $model->ward_id = Ward::where('uuid', $model->ward_uuid)->value('id');
            }
            if (!empty($model->cell_uuid) && empty($model->cell_id)) {
                $model->cell_id = Cell::where('uuid', $model->cell_uuid)->value('id');
            }
            if (!empty($model->organisation_uuid) && empty($model->organisation_id)) {
                $model->organisation_id = Organisation::where('uuid', $model->organisation_uuid)->value('id');
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(Cell::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
