<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class Ward extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'population',
        'area_sq_km',
        'description',
        'latitude',
        'longitude',
        'organisation_id'
    ];

    protected $casts = [
        'population'   => 'integer',
        'area_sq_km'   => 'float',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Assign UUID
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set organisation_id from authenticated user if not already set
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }
        });

        // When updating, also make sure updated_at is refreshed
        static::updating(function ($model) {
            $model->updated_at = now();
        });
    }

    /**
     * Ensure the ward code is unique, adjusting sequence number if necessary.
     *
     * @param string $initialCode e.g. "FP-WD-100"
     * @return string
     */


    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
