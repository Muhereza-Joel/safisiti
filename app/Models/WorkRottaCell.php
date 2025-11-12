<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkRottaCell extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_rotta_cells';

    protected $fillable = [
        'uuid',
        'work_rotta_uuid',
        'cell_uuid',
        'status',
        'organisation_id',
        'organisation_uuid',
    ];

    protected $casts = [
        'organisation_id' => 'integer',
    ];

    public function workRotta()
    {
        return $this->belongsTo(WorkRotta::class, 'work_rotta_uuid', 'uuid');
    }
}
