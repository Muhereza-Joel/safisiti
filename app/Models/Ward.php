<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
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
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set organisation_id from authenticated user if not already set
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }
        });
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
