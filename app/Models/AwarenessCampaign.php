<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class AwarenessCampaign extends Model
{
    use HasFactory, Cachable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'location',
        'date_conducted',
        'participants_count',
        'organisation_id',
        'organisation_uuid',
        'user_id',
        'user_uuid',
    ];

    protected $casts = [
        'date_conducted' => 'date',
        'user_id' => 'integer',
        'organisation_id' => 'integer',
        'participants_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        // 🔹 Before saving, backfill IDs and UUIDs
        static::saving(function ($model) {
            // Fill organisation_uuid from organisation_id
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = \App\Models\Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }

            // Fill organisation_id from organisation_uuid
            if (!empty($model->organisation_uuid) && (!$model->organisation_id || $model->organisation_id == 0)) {
                $org = \App\Models\Organisation::withTrashed()
                    ->where('uuid', $model->organisation_uuid)
                    ->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                }
            }

            // Fill user_uuid from user_id
            if ($model->user_id && empty($model->user_uuid)) {
                $user = \App\Models\User::find($model->user_id);
                if ($user) {
                    $model->user_uuid = $user->uuid;
                }
            }

            // Fill user_id from user_uuid
            if (!empty($model->user_uuid) && (!$model->user_id || $model->user_id == 0)) {
                $user = \App\Models\User::withTrashed()
                    ->where('uuid', $model->user_uuid)
                    ->first();
                if ($user) {
                    $model->user_id = $user->id;
                }
            }
        });

        // 🔹 Before creating, ensure uuid + organisation_id are set
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }

            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    // Relationships
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
