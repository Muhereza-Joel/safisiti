<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class WorkRottaCell extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_rotta_cells';

    protected $fillable = [
        'uuid',
        'work_rotta_id',
        'work_rotta_uuid',
        'cell_id',
        'cell_uuid',
        'status',
        'organisation_id',
        'organisation_uuid',
        'is_active',
    ];

    protected $casts = [
        'organisation_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function workRotta()
    {
        return $this->belongsTo(WorkRotta::class, 'work_rotta_uuid', 'uuid');
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class, 'cell_uuid', 'uuid');
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_uuid', 'uuid');
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Logic (Sync UUIDs and IDs)
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        // Before save (create or update)
        static::saving(function ($model) {
            // Sync Work Rotta ID/UUID
            if ($model->isDirty('work_rotta_id') && !$model->isDirty('work_rotta_uuid')) {
                $model->work_rotta_uuid = $model->workRotta?->uuid;
            } elseif ($model->isDirty('work_rotta_uuid') && !$model->isDirty('work_rotta_id')) {
                $model->work_rotta_id = $model->workRotta?->id;
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

        // On create only
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Auto-fill organisation info from authenticated user
            if (Auth::check()) {
                $user = Auth::user();
                if (empty($model->organisation_id)) {
                    $model->organisation_id = $user->organisation_id;
                }
                if (empty($model->organisation_uuid)) {
                    $model->organisation_uuid = $user->organisation?->uuid
                        ?? \App\Models\Organisation::find($user->organisation_id)?->uuid;
                }
            }

            // Backfill missing UUIDs or IDs
            if (!empty($model->cell_id) && empty($model->cell_uuid)) {
                $model->cell_uuid = \App\Models\Cell::find($model->cell_id)?->uuid;
            }

            if (!empty($model->work_rotta_id) && empty($model->work_rotta_uuid)) {
                $model->work_rotta_uuid = \App\Models\WorkRotta::find($model->work_rotta_id)?->uuid;
            }

            // Sync back IDs if only UUIDs provided
            if (!empty($model->cell_uuid) && empty($model->cell_id)) {
                $model->cell_id = \App\Models\Cell::where('uuid', $model->cell_uuid)->value('id');
            }

            if (!empty($model->work_rotta_uuid) && empty($model->work_rotta_id)) {
                $model->work_rotta_id = \App\Models\WorkRotta::where('uuid', $model->work_rotta_uuid)->value('id');
            }

            if (!empty($model->organisation_uuid) && empty($model->organisation_id)) {
                $model->organisation_id = \App\Models\Organisation::where('uuid', $model->organisation_uuid)->value('id');
            }
        });
    }
}
