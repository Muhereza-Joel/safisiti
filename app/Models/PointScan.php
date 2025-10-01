<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class PointScan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'scan_type',
        'scanned_value',
        'extracted_uuid',
        'latitude',
        'longitude',
        'scanned_at',
        'user_id',
        'user_uuid',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Booted Hooks for Auto-Fill + Backfilling
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        // Keep IDs and UUIDs in sync when saving
        static::saving(function ($model) {
            $originalUpdatedAt = $model->updated_at;
            $wasChanged = false;

            // --- Organisation backfilling ---
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                    $wasChanged = true;
                }
            }
            if (!empty($model->organisation_uuid) && (empty($model->organisation_id) || $model->organisation_id == 0)) {
                $org = Organisation::withTrashed()->where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                    $wasChanged = true;
                }
            }

            // --- User backfilling ---
            if ($model->user_id && empty($model->user_uuid)) {
                $user = User::find($model->user_id);
                if ($user) {
                    $model->user_uuid = $user->uuid;
                    $wasChanged = true;
                }
            }
            if (!empty($model->user_uuid) && (empty($model->user_id) || $model->user_id == 0)) {
                $user = User::withTrashed()->where('uuid', $model->user_uuid)->first();
                if ($user) {
                    $model->user_id = $user->id;
                    $wasChanged = true;
                }
            }

            // If any backfilling occurred, update timestamp
            if ($wasChanged) {
                $model->updated_at = now();
            } else {
                $model->updated_at = $originalUpdatedAt;
            }
        });

        // Auto-fill UUID + organisation_id during creation
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set organisation from authenticated user
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
                $model->organisation_uuid = Auth::user()->organisation?->uuid;
            }

            // Set user info if authenticated
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
                $model->user_uuid = Auth::user()->uuid;
            }

            // Ensure organisation_uuid is set
            if (!empty($model->organisation_id) && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }
        });

        // After creation, ensure consistency
        static::created(function ($model) {
            $needsUpdate = false;

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

            if (!empty($model->user_id) && empty($model->user_uuid)) {
                $user = User::find($model->user_id);
                if ($user) {
                    $model->user_uuid = $user->uuid;
                    $needsUpdate = true;
                }
            }
            if (!empty($model->user_uuid) && empty($model->user_id)) {
                $user = User::where('uuid', $model->user_uuid)->first();
                if ($user) {
                    $model->user_id = $user->id;
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $model->save();
            }
        });
    }
}
