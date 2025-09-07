<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class RecycleRecord extends Model
{
    use HasFactory, SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'recycling_center_id',
        'recycling_center_uuid',
        'recycling_method_id',
        'recycling_method_uuid',
        'quantity',
        'units',
        'notes',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'uuid' => 'string',
        'quantity' => 'integer',
        'units' => 'string',
        'notes' => 'string',
        'recycling_center_id' => 'integer',
        'recycling_method_id' => 'integer',
        'organisation_id' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        // Keep IDs and UUIDs in sync
        static::saving(function ($model) {

            // Organisation
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }
            if (!empty($model->organisation_uuid) && (!$model->organisation_id || $model->organisation_id == 0)) {
                $org = Organisation::withTrashed()->where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                }
            }

            // Recycling Center
            if ($model->recycling_center_id && empty($model->recycling_center_uuid)) {
                $center = RecyclingCenter::find($model->recycling_center_id);
                if ($center) {
                    $model->recycling_center_uuid = $center->uuid;
                }
            }
            if (!empty($model->recycling_center_uuid) && (!$model->recycling_center_id || $model->recycling_center_id == 0)) {
                $center = RecyclingCenter::withTrashed()->where('uuid', $model->recycling_center_uuid)->first();
                if ($center) {
                    $model->recycling_center_id = $center->id;
                }
            }

            // Recycling Method
            if ($model->recycling_method_id && empty($model->recycling_method_uuid)) {
                $method = RecyclingMethod::find($model->recycling_method_id);
                if ($method) {
                    $model->recycling_method_uuid = $method->uuid;
                }
            }
            if (!empty($model->recycling_method_uuid) && (!$model->recycling_method_id || $model->recycling_method_id == 0)) {
                $method = RecyclingMethod::withTrashed()->where('uuid', $model->recycling_method_uuid)->first();
                if ($method) {
                    $model->recycling_method_id = $method->id;
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Auto set organisation_id from authenticated user
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }
        });
    }


    public function recyclingCenter()
    {
        return $this->belongsTo(RecyclingCenter::class);
    }

    public function recyclingMethod()
    {
        return $this->belongsTo(RecyclingMethod::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
