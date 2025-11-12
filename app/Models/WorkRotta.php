<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkRotta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_rotta';

    protected $fillable = [
        'uuid',
        'route_uuid',
        'date',
        'start_time',
        'end_time',
        'shift_type',
        'status',
        'attendance_status',
        'check_in_time',
        'check_out_time',
        'performance_rating',
        'notes',
        'inspector_user_uuid',
        'service_provider_user_uuid',
        'assigned_to',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
        'performance_rating' => 'integer',
    ];

    public function cells()
    {
        return $this->hasMany(WorkRottaCell::class, 'work_rotta_uuid', 'uuid');
    }
}
