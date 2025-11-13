<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionRouteWard extends Pivot
{
    protected $table = 'collection_route_ward';

    protected $fillable = [
        'uuid',
        'collection_route_id',
        'collection_route_uuid',
        'ward_id',
        'ward_uuid',
        'organisation_id',
        'organisation_uuid',
        'collection_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'organisation_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function collectionRoute()
    {
        return $this->belongsTo(CollectionRoute::class, 'collection_route_uuid', 'uuid');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_uuid', 'uuid');
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_uuid', 'uuid');
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Logic (Sync IDs ↔ UUIDs, Generate UUID, Auto-fill Organisation)
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        // Before saving (create or update)
        static::saving(function ($model) {
            // Sync Collection Route ID ↔ UUID
            if ($model->isDirty('collection_route_id') && !$model->isDirty('collection_route_uuid')) {
                $model->collection_route_uuid = $model->collectionRoute?->uuid;
            } elseif ($model->isDirty('collection_route_uuid') && !$model->isDirty('collection_route_id')) {
                $model->collection_route_id = $model->collectionRoute?->id;
            }

            // Sync Ward ID ↔ UUID
            if ($model->isDirty('ward_id') && !$model->isDirty('ward_uuid')) {
                $model->ward_uuid = $model->ward?->uuid;
            } elseif ($model->isDirty('ward_uuid') && !$model->isDirty('ward_id')) {
                $model->ward_id = $model->ward?->id;
            }

            // Sync Organisation ID ↔ UUID
            if ($model->isDirty('organisation_id') && !$model->isDirty('organisation_uuid')) {
                $model->organisation_uuid = $model->organisation?->uuid;
            } elseif ($model->isDirty('organisation_uuid') && !$model->isDirty('organisation_id')) {
                $model->organisation_id = $model->organisation?->id;
            }
        });

        // On create only
        static::creating(function ($model) {
            // Generate UUID if missing
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Auto-fill organisation info from logged-in user
            if (Auth::check()) {
                $user = Auth::user();

                if (empty($model->organisation_id)) {
                    $model->organisation_id = $user->organisation_id;
                }

                if (empty($model->organisation_uuid)) {
                    $model->organisation_uuid = $user->organisation?->uuid
                        ?? \App\Models\Organisation::find($user->organisation_id)?->uuid;
                }
            }

            // Backfill missing UUIDs if only IDs provided
            if (!empty($model->collection_route_id) && empty($model->collection_route_uuid)) {
                $model->collection_route_uuid = \App\Models\CollectionRoute::find($model->collection_route_id)?->uuid;
            }

            if (!empty($model->ward_id) && empty($model->ward_uuid)) {
                $model->ward_uuid = \App\Models\Ward::find($model->ward_id)?->uuid;
            }

            if (!empty($model->organisation_id) && empty($model->organisation_uuid)) {
                $model->organisation_uuid = \App\Models\Organisation::find($model->organisation_id)?->uuid;
            }

            // Backfill missing IDs if only UUIDs provided
            if (!empty($model->collection_route_uuid) && empty($model->collection_route_id)) {
                $model->collection_route_id = \App\Models\CollectionRoute::where('uuid', $model->collection_route_uuid)->value('id');
            }

            if (!empty($model->ward_uuid) && empty($model->ward_id)) {
                $model->ward_id = \App\Models\Ward::where('uuid', $model->ward_uuid)->value('id');
            }

            if (!empty($model->organisation_uuid) && empty($model->organisation_id)) {
                $model->organisation_id = \App\Models\Organisation::where('uuid', $model->organisation_uuid)->value('id');
            }
        });
    }
}
