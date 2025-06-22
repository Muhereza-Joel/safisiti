<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class WasteCollection extends Model
{
    protected $fillable = [
        'uuid',
        'amount',
        'units',
        'notes',
        'collection_batch_id',
        'collection_point_id',
        'waste_type_id',
        'user_id',
        'organisation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'collection_batch_id' => 'integer',
        'amount' => 'integer',
        'units' => 'string',
        'notes' => 'string',
        'collection_point_id' => 'integer',
        'waste_type_id' => 'integer',
        'user_id' => 'integer',
        'organisation_id' => 'integer',
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


    public function wasteCollections()
    {
        return $this->belongsTo(WasteCollection::class);
    }

    public function collectionPoint()
    {
        return $this->belongsTo(CollectionPoint::class);
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
