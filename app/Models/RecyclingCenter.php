<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Caching;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class RecyclingCenter extends Model
{
    use HasFactory, SoftDeletes, Caching;

    protected $fillable = [
        'uuid',
        'name',
        'location',
        'latitude',
        'longitude',
        'description',
        'organisation_id',
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

    // public function collections()
    // {
    //     return $this->hasMany(Collection::class);
    // }
}
