<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class Cell extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'ward_id',
        'organisation_id'
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

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
