<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'collection_batch_number',
        'vehicle_id',
        'vehicle_uuid',
        'status',
        'organisation_id',
        'owner_user_uuid',
        'dumpsite_uuid',
        'actual_tonnage'
    ];

    protected $casts = [
        'uuid' => 'string',
        'vehicle_uuid' => 'string',
        'collection_batch_number' => 'string',
        'vehicle_id' => 'integer',
        'status' => 'string',
        'organisation_id' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            // Fill vehicle_uuid from vehicle_id if missing
            if ($model->vehicle_id && empty($model->vehicle_uuid)) {
                $vehicle = \App\Models\Vehicle::find($model->vehicle_id);
                if ($vehicle) {
                    $model->vehicle_uuid = $vehicle->uuid;
                }
            }

            // Fill vehicle_id from vehicle_uuid if missing or 0
            if (!empty($model->vehicle_uuid) && (!$model->vehicle_id || $model->vehicle_id == 0)) {
                $vehicle = \App\Models\Vehicle::withTrashed()
                    ->where('uuid', $model->vehicle_uuid)
                    ->first();
                if ($vehicle) {
                    $model->vehicle_id = $vehicle->id;
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            }

            if (empty($model->organisation_id) && auth()->check()) {
                $model->organisation_id = auth()->user()->organisation_id;
            }

            if (empty($model->collection_batch_number)) {
                $organisation = $model->organisation ?? \App\Models\Organisation::find($model->organisation_id);
                $orgCode = strtoupper(substr(preg_replace('/\s+/', '', $organisation->name), 0, 4));
                $prefix = $orgCode . '-' . now()->format('Ym') . '-';

                $lastBatch = static::where('organisation_id', $model->organisation_id)
                    ->where('collection_batch_number', 'like', $prefix . '%')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastBatch
                    ? (int) str_replace($prefix, '', $lastBatch->collection_batch_number) + 1
                    : 1;

                $model->collection_batch_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }



    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
