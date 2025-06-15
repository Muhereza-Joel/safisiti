<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionPoint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'category',
        'head_name',
        'phone',
        'email',
        'ward_id',
        'cell_id',
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
        'organisation_id'
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
