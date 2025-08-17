<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
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
        'waste_type',
        'collection_frequency',
        'bin_count',
        'bin_type',
        'last_collection_date',
        'notes',
        'organisation_id',
        'organisation_uuid'
    ];

    protected $casts = [
        'uuid' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'household_size' => 'integer',
        'bin_count' => 'integer',
        'last_collection_date' => 'date',
        'deleted_at' => 'datetime',
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

            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }

            // If ward_id exists, get its UUID and attach it
            if (!empty($model->ward_id)) {
                $model->ward_uuid = Ward::find($model->ward_id)?->uuid;
            }

            // If cell_id exists, get its UUID and attach it
            if (!empty($model->cell_id)) {
                $model->cell_uuid = Cell::find($model->cell_id)?->uuid;
            }

            // Get the organisation UUID from current user and attach it
            if (Auth::check() && empty($model->organisation_uuid)) {
                $model->organisation_uuid = Auth::user()->organisation?->uuid;
            }
        });
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
