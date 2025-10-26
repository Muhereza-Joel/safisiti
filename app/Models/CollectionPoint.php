<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CollectionPoint extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'category',
        'head_name',
        'phone',
        'email',
        'ward_id',
        'ward_uuid',
        'cell_id',
        'cell_uuid',
        'address',
        'latitude',
        'longitude',
        'structure_type',
        'household_size',
        'collection_frequency',
        'bin_count',
        'bin_type',
        'last_collection_date',
        'notes',
        'organisation_id',
        'organisation_uuid',
        'parent_uuid',
        'custom_name',
        'caretaker_name',
        'caretaker_phone',
        'alternate_contact_name',
        'alternate_contact_phone',
    ];

    protected $casts = [
        'uuid' => 'string',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'household_size' => 'integer',
        'bin_count' => 'integer',
        'last_collection_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        // Keep IDs and UUIDs in sync
        static::saving(function ($model) {
            // Ward
            if ($model->ward_id && empty($model->ward_uuid)) {
                $ward = \App\Models\Ward::find($model->ward_id);
                if ($ward) {
                    $model->ward_uuid = $ward->uuid;
                }
            }
            if (!empty($model->ward_uuid) && (!$model->ward_id || $model->ward_id == 0)) {
                $ward = \App\Models\Ward::withTrashed()->where('uuid', $model->ward_uuid)->first();
                if ($ward) {
                    $model->ward_id = $ward->id;
                }
            }

            // Cell
            if ($model->cell_id && empty($model->cell_uuid)) {
                $cell = \App\Models\Cell::find($model->cell_id);
                if ($cell) {
                    $model->cell_uuid = $cell->uuid;
                }
            }
            if (!empty($model->cell_uuid) && (!$model->cell_id || $model->cell_id == 0)) {
                $cell = \App\Models\Cell::withTrashed()->where('uuid', $model->cell_uuid)->first();
                if ($cell) {
                    $model->cell_id = $cell->id;
                }
            }

            // Organisation
            if ($model->organisation_id && empty($model->organisation_uuid)) {
                $org = \App\Models\Organisation::find($model->organisation_id);
                if ($org) {
                    $model->organisation_uuid = $org->uuid;
                }
            }
            if (!empty($model->organisation_uuid) && (!$model->organisation_id || $model->organisation_id == 0)) {
                $org = \App\Models\Organisation::withTrashed()->where('uuid', $model->organisation_uuid)->first();
                if ($org) {
                    $model->organisation_id = $org->id;
                }
            }
        });

        // Auto-fill UUID + organisation_id
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }

            // Sync UUIDs for ward + cell if IDs exist
            if (!empty($model->ward_id)) {
                $model->ward_uuid = \App\Models\Ward::find($model->ward_id)?->uuid;
            }

            if (!empty($model->cell_id)) {
                $model->cell_uuid = \App\Models\Cell::find($model->cell_id)?->uuid;
            }

            // Get organisation UUID from current user if missing
            if (Auth::check() && empty($model->organisation_uuid)) {
                $model->organisation_uuid = Auth::user()->organisation?->uuid;
            }
        });
    }


    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
