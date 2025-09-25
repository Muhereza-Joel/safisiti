<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionRoute extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'frequency',
        'start_time',
        'end_time',
        'status',
        'notes',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
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

            // Get the organisation UUID from current user and attach it
            if (Auth::check() && empty($model->organisation_uuid)) {
                $model->organisation_uuid = Auth::user()->organisation?->uuid;
            }
        });
    }

    public function wards()
    {
        return $this->belongsToMany(Ward::class, 'collection_route_ward')
            ->withPivot('collection_order', 'collection_route_uuid', 'ward_uuid')
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
