<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'video_id',
        'violation_type',
        'license_number',
        'image_path',
    ];
    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
