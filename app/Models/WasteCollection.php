<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class WasteCollection extends Model
{
    protected $fillable = [
        'uuid',
        'amount',
        'units',
        'notes',
        'collection_batch_id',
        'collection_point_id',
        'waste_type_id',
        'user_id',
        'organisation_id',
        'collection_batch_uuid',
        'collection_point_uuid',
        'waste_type_uuid',
        'user_uuid',
        'organisation_uuid',
        'segregated',
    ];

    protected $casts = [
        'uuid' => 'string',
        'amount' => 'integer',
        'units' => 'string',
        'notes' => 'string',
        'collection_batch_id' => 'integer',
        'collection_point_id' => 'integer',
        'waste_type_id' => 'integer',
        'user_id' => 'integer',
        'organisation_id' => 'integer',
        'segregated' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        // Keep IDs and UUIDs in sync
        static::saving(function ($model) {
            // CollectionBatch
            if ($model->collection_batch_id && empty($model->collection_batch_uuid)) {
                $batch = \App\Models\CollectionBatch::find($model->collection_batch_id);
                if ($batch) {
                    $model->collection_batch_uuid = $batch->uuid;
                }
            }
            if (!empty($model->collection_batch_uuid) && (!$model->collection_batch_id || $model->collection_batch_id == 0)) {
                $batch = \App\Models\CollectionBatch::withTrashed()->where('uuid', $model->collection_batch_uuid)->first();
                if ($batch) {
                    $model->collection_batch_id = $batch->id;
                }
            }

            // CollectionPoint
            if ($model->collection_point_id && empty($model->collection_point_uuid)) {
                $point = \App\Models\CollectionPoint::find($model->collection_point_id);
                if ($point) {
                    $model->collection_point_uuid = $point->uuid;
                }
            }
            if (!empty($model->collection_point_uuid) && (!$model->collection_point_id || $model->collection_point_id == 0)) {
                $point = \App\Models\CollectionPoint::withTrashed()->where('uuid', $model->collection_point_uuid)->first();
                if ($point) {
                    $model->collection_point_id = $point->id;
                }
            }

            // WasteType
            if ($model->waste_type_id && empty($model->waste_type_uuid)) {
                $wasteType = \App\Models\WasteType::find($model->waste_type_id);
                if ($wasteType) {
                    $model->waste_type_uuid = $wasteType->uuid;
                }
            }
            if (!empty($model->waste_type_uuid) && (!$model->waste_type_id || $model->waste_type_id == 0)) {
                $wasteType = \App\Models\WasteType::withTrashed()->where('uuid', $model->waste_type_uuid)->first();
                if ($wasteType) {
                    $model->waste_type_id = $wasteType->id;
                }
            }

            // User
            if ($model->user_id && empty($model->user_uuid)) {
                $user = \App\Models\User::find($model->user_id);
                if ($user) {
                    $model->user_uuid = $user->uuid;
                }
            }
            if (!empty($model->user_uuid) && (!$model->user_id || $model->user_id == 0)) {
                $user = \App\Models\User::withTrashed()->where('uuid', $model->user_uuid)->first();
                if ($user) {
                    $model->user_id = $user->id;
                }
            }

            // Organisation
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = \App\Models\Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }
            if (!empty($model->organisation_uuid) && (!$model->organisation_id || $model->organisation_id == 0)) {
                $org = \App\Models\Organisation::withTrashed()->where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                }
            }
        });

        // Auto-fill UUID + organisation_id
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }
        });
    }

    // Relationships
    public function collectionBatch()
    {
        return $this->belongsTo(CollectionBatch::class);
    }

    public function collectionPoint()
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function wasteType()
    {
        return $this->belongsTo(WasteType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
