<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class Ward extends Model
{
    use SoftDeletes, Cachable;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'population',
        'area_sq_km',
        'description',
        'latitude',
        'longitude',
        'organisation_id'
    ];

    protected $casts = [
        'population'   => 'integer',
        'area_sq_km'   => 'float',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Assign UUID
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set organisation_id from authenticated user if not already set
            if (empty($model->organisation_id) && Auth::check()) {
                $model->organisation_id = Auth::user()->organisation_id;
            }

            // Ensure code is unique by auto-incrementing suffix if needed
            if (!empty($model->code)) {
                $model->code = self::generateUniqueCode($model->code);
            }
        });

        // When updating, also make sure updated_at is refreshed
        static::updating(function ($model) {
            $model->updated_at = now();
        });
    }

    /**
     * Ensure the ward code is unique, adjusting sequence number if necessary.
     *
     * @param string $initialCode e.g. "FP-WD-100"
     * @return string
     */
    protected static function generateUniqueCode(string $initialCode): string
    {
        // Split into prefix + number
        if (!preg_match('/^(.*-)(\d+)$/', $initialCode, $matches)) {
            // If pattern not matched, just return initialCode or append -1
            return $initialCode;
        }

        $prefix = $matches[1];   // e.g. "FP-WD-"
        $number = (int) $matches[2]; // e.g. 100

        $latest = self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
            ->value('code');

        if ($latest) {
            if (preg_match('/^(.*-)(\d+)$/', $latest, $lastMatch)) {
                $number = ((int) $lastMatch[2]) + 1;
            }
        }

        return $prefix . $number;
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
