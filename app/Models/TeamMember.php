<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\Uuid;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'team_members';

    protected $fillable = [
        'uuid',
        'provider_user_uuid',
        'name',
        'phone',
        'designation',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $dates = [
        'deleted_at',
    ];

    protected static function booted()
    {
        // This event runs BEFORE saving (on create and update)
        static::saving(function ($model) {
            // Use relationship properties instead of new queries.
            // This is "query-free" if the relationship is already set.

            // Example:
            // $point->ward = Ward::find(1);
            // $point->save(); // This event will now be efficient.

            // Sync Ward ID/UUID
            if ($model->isDirty('ward_id') && !$model->isDirty('ward_uuid')) {
                // ward_id changed, update uuid from the loaded relationship
                $model->ward_uuid = $model->ward?->uuid;
            } elseif ($model->isDirty('ward_uuid') && !$model->isDirty('ward_id')) {
                // ward_uuid changed, update id from the loaded relationship
                $model->ward_id = $model->ward?->id;
            }

            // Sync Cell ID/UUID
            if ($model->isDirty('cell_id') && !$model->isDirty('cell_uuid')) {
                $model->cell_uuid = $model->cell?->uuid;
            } elseif ($model->isDirty('cell_uuid') && !$model->isDirty('cell_id')) {
                $model->cell_id = $model->cell?->id;
            }

            // Sync Organisation ID/UUID
            if ($model->isDirty('organisation_id') && !$model->isDirty('organisation_uuid')) {
                $model->organisation_uuid = $model->organisation?->uuid;
            } elseif ($model->isDirty('organisation_uuid') && !$model->isDirty('organisation_id')) {
                $model->organisation_id = $model->organisation?->id;
            }
        });

        // This event runs ONLY when creating a new model
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Auto-fill from authenticated user
            if (Auth::check()) {
                if (empty($model->organisation_id)) {
                    $model->organisation_id = Auth::user()->organisation_id;
                }
                if (empty($model->organisation_uuid)) {
                    // Try to get from user's loaded org, fall back to a query if needed
                    $model->organisation_uuid = Auth::user()->organisation?->uuid
                        ?? Organisation::find(Auth::user()->organisation_id)?->uuid;
                }
            }
        });
    }

    // Relationships -----------------------------------

    public function providerUser()
    {
        return $this->belongsTo(User::class, 'provider_user_uuid', 'uuid');
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }
}
