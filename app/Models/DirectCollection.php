<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class DirectCollection extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'contact',
        'quantity',
        'units',
        'notes',
        'waste_type_id',
        'waste_type_uuid',
        'segregated',
        'user_id',
        'user_uuid',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'uuid' => 'string',
        'quantity' => 'integer',
        'units' => 'string',
        'notes' => 'string',
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
