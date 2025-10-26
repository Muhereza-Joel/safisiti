<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Section extends Model
{
    use HasFactory, SoftDeletes, Cachable;

    protected $table = 'sections';

    protected $fillable = [
        'uuid',
        'name',
        'group_uuid',
        'head_name',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'notes',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'deleted_at' => 'datetime',
    ];

    /**
     * Model boot method for UUIDs and backfilling
     */
    protected static function booted()
    {
        // Auto-fill and backfill logic
        static::creating(function ($section) {
            // Generate UUID if missing
            if (empty($section->uuid)) {
                $section->uuid = Uuid::uuid4()->toString();
            }

            // Backfill organisation_id if organisation_uuid exists
            if ($section->organisation_uuid && !$section->organisation_id) {
                $org = \App\Models\Organisation::where('uuid', $section->organisation_uuid)->first();
                if ($org) {
                    $section->organisation_id = $org->id;
                }
            }

            // Backfill group (CollectionPoint) if group_uuid exists
            if (!empty($section->group_uuid)) {
                $group = \App\Models\CollectionPoint::where('uuid', $section->group_uuid)->first();

                // Optionally auto-create placeholder group if not found
                if (!$group) {
                    $group = \App\Models\CollectionPoint::create([
                        'uuid' => $section->group_uuid,
                        'name' => 'Unnamed Group (Auto-created)',
                        'custom_name' => 'Backfilled',
                    ]);
                }
            }
        });

        static::saving(function ($section) {
            // Keep organisation_id and organisation_uuid in sync
            if ($section->organisation_uuid && !$section->organisation_id) {
                $org = \App\Models\Organisation::where('uuid', $section->organisation_uuid)->first();
                if ($org) {
                    $section->organisation_id = $org->id;
                }
            }

            if ($section->organisation_id && empty($section->organisation_uuid)) {
                $org = \App\Models\Organisation::find($section->organisation_id);
                if ($org) {
                    $section->organisation_uuid = $org->uuid;
                }
            }
        });
    }

    /**
     * Relationships
     */

    // Belongs to a parent collection point (group)
    public function group()
    {
        return $this->belongsTo(CollectionPoint::class, 'group_uuid', 'uuid');
    }

    // Has many collection points under this section
    public function collectionPoints()
    {
        return $this->hasMany(CollectionPoint::class, 'section_uuid', 'uuid');
    }

    // Belongs to an organisation
    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Route model binding key
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
