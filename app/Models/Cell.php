<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class Cell extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'ward_id',
        'ward_uuid',
        'organisation_id',
        'organisation_uuid',
        'updated_at' // Ensure updated_at is fillable for sync tracking
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        // Keep IDs and UUIDs in sync with updated_at tracking
        static::saving(function ($model) {
            $originalUpdatedAt = $model->updated_at;
            $wasChanged = false;

            // Ward relationship backfilling
            if ($model->ward_id && empty($model->ward_uuid)) {
                $ward = Ward::find($model->ward_id);
                if ($ward) {
                    $model->ward_uuid = $ward->uuid;
                    $wasChanged = true;
                }
            }
            if (!empty($model->ward_uuid) && (!$model->ward_id || $model->ward_id == 0)) {
                $ward = Ward::withTrashed()->where('uuid', $model->ward_uuid)->first();
                if ($ward) {
                    $model->ward_id = $ward->id;
                    $wasChanged = true;
                }
            }

            // Organisation relationship backfilling
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                    $wasChanged = true;
                }
            }
            if (!empty($model->organisation_uuid) && (!$model->organisation_id || $model->organisation_id == 0)) {
                $org = Organisation::withTrashed()->where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                    $wasChanged = true;
                }
            }

            // If any backfilling occurred, update the timestamp to force sync
            if ($wasChanged) {
                $model->updated_at = now();
            } else {
                // Preserve the original updated_at if no changes were made
                $model->updated_at = $originalUpdatedAt;
            }
        });

        // Auto-fill UUID + organisation_id during creation
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set organisation_id from authenticated user if not already set
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
                $model->organisation_uuid = Auth::user()->organisation?->uuid;
            }

            // If ward_id exists, get its UUID and attach it
            if (!empty($model->ward_id) && empty($model->ward_uuid)) {
                $ward = Ward::find($model->ward_id);
                if ($ward) {
                    $model->ward_uuid = $ward->uuid;
                }
            }

            // If ward_uuid exists but no ward_id, backfill the ID
            if (!empty($model->ward_uuid) && empty($model->ward_id)) {
                $ward = Ward::where('uuid', $model->ward_uuid)->first();
                if ($ward) {
                    $model->ward_id = $ward->id;
                }
            }

            // Ensure organisation_uuid is set if organisation_id is present
            if (!empty($model->organisation_id) && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }
        });

        // Additional backfilling after creation for any missing relationships
        static::created(function ($model) {
            $needsUpdate = false;

            // Ensure ward relationship is complete
            if (!empty($model->ward_id) && empty($model->ward_uuid)) {
                $ward = Ward::find($model->ward_id);
                if ($ward) {
                    $model->ward_uuid = $ward->uuid;
                    $needsUpdate = true;
                }
            }
            if (!empty($model->ward_uuid) && empty($model->ward_id)) {
                $ward = Ward::where('uuid', $model->ward_uuid)->first();
                if ($ward) {
                    $model->ward_id = $ward->id;
                    $needsUpdate = true;
                }
            }

            // Ensure organisation relationship is complete
            if (!empty($model->organisation_id) && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                    $needsUpdate = true;
                }
            }
            if (!empty($model->organisation_uuid) && empty($model->organisation_id)) {
                $org = Organisation::where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                    $needsUpdate = true;
                }
            }

            // Save if any backfilling was needed
            if ($needsUpdate) {
                $model->save();
            }
        });
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Manual backfill method for existing records
     */
    public static function backfillRelationships()
    {
        $cells = self::withTrashed()->get();

        foreach ($cells as $cell) {
            $needsUpdate = false;

            // Backfill ward_uuid if missing
            if (!empty($cell->ward_id) && empty($cell->ward_uuid)) {
                $ward = Ward::withTrashed()->find($cell->ward_id);
                if ($ward) {
                    $cell->ward_uuid = $ward->uuid;
                    $needsUpdate = true;
                }
            }

            // Backfill ward_id if missing
            if (!empty($cell->ward_uuid) && empty($cell->ward_id)) {
                $ward = Ward::withTrashed()->where('uuid', $cell->ward_uuid)->first();
                if ($ward) {
                    $cell->ward_id = $ward->id;
                    $needsUpdate = true;
                }
            }

            // Backfill organisation_uuid if missing
            if (!empty($cell->organisation_id) && empty($cell->organisation_uuid)) {
                $org = Organisation::withTrashed()->find($cell->organisation_id);
                if ($org) {
                    $cell->organisation_uuid = $org->uuid;
                    $needsUpdate = true;
                }
            }

            // Backfill organisation_id if missing
            if (!empty($cell->organisation_uuid) && empty($cell->organisation_id)) {
                $org = Organisation::withTrashed()->where('uuid', $cell->organisation_uuid)->first();
                if ($org) {
                    $cell->organisation_id = $org->id;
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $cell->updated_at = now(); // Force sync
                $cell->save();
            }
        }
    }
}
