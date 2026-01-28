<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = ['semester_number', 'type', 'is_locked'];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
