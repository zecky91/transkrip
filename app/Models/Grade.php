<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['student_nisn', 'semester_id', 'subject_name', 'value'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_nisn', 'nisn');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
