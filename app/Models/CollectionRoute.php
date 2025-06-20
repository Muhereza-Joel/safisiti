<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'frequency',
        'collection_days',
        'start_time',
        'end_time',
        'status',
        'notes',
        'organisation_id'
    ];

    protected $casts = [
        'collection_days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
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

    public function wards()
    {
        return $this->belongsToMany(Ward::class, 'collection_route_ward')
            ->withPivot('collection_order')
            ->withTimestamps();
    }

    public function cells()
    {
        return $this->hasManyThrough(
            Cell::class,
            'collection_route_ward',
            'collection_route_id', // Foreign key on pivot table
            'ward_id',             // Foreign key on cells table
            'id',                 // Local key on collection_routes table
            'ward_id'              // Local key on pivot table
        );
    }

    public function collectionPoints()
    {
        return $this->hasManyThrough(
            CollectionPoint::class,
            Cell::class,
            'ward_id', // Foreign key on cells table
            'cell_id',  // Foreign key on collection_points table
            'id',       // Local key on collection_routes table (via wards)
            'id'        // Local key on cells table
        )->whereIn('ward_id', $this->wards->pluck('id'));
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
