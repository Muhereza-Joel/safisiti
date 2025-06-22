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
    use HasFactory, SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'collection_batch_number',
        'vehicle_id',
        'status',
        'organisation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
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
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }

            if (empty($model->collection_batch_number)) {
                // Load the organization (assuming it's already loaded in some cases)
                $organisation = $model->organisation ?? Organisation::find($model->organisation_id);

                // Get first 4 letters of organization name, uppercase, and remove spaces
                $orgCode = strtoupper(substr(preg_replace('/\s+/', '', $organisation->name), 0, 4));

                $prefix = $orgCode . '-' . now()->format('Ym') . '-';

                $lastBatch = static::where('organisation_id', $model->organisation_id)
                    ->where('collection_batch_number', 'like', $prefix . '%')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastBatch ?
                    (int) str_replace($prefix, '', $lastBatch->collection_batch_number) + 1 : 1;
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
