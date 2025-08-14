<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Ramsey\Uuid\Uuid;

class CollectionRouteWard extends Pivot
{
    protected $table = 'collection_route_ward';

    protected $fillable = [
        'uuid',
        'collection_route_id',
        'collection_route_uuid',
        'ward_id',
        'ward_uuid',
        'collection_order',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }
}
