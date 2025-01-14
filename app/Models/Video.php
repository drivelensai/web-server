<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'source_path',
        'status',
        'started_at',
    ];
    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    public function getLocalPathAttribute()
    {
        return storage_path('app/public/' . $this->source_path);
    }
}
