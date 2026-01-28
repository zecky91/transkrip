<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'nisn';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nisn',
        'nama',
        'kelas',
        'program',
        'average_sem1',
        'average_sem2',
        'average_sem3',
        'average_sem4',
        'average_sem5',
        'average_overall'
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_nisn', 'nisn');
    }

    public function calculateAverage($semesterNumber)
    {
        return $this->grades()
            ->whereHas('semester', function ($query) use ($semesterNumber) {
                $query->where('semester_number', $semesterNumber)
                    ->where('type', 'academic'); // Ensure we only get academic grades for main table
            })
            ->avg('value');
    }

    public function calculatePklAverage()
    {
        return $this->grades()
            ->whereHas('semester', function ($query) {
                $query->where('type', 'pkl');
            })
            ->avg('value');
    }

    public function calculateOverallAverage()
    {
        // Use stored value if available
        if ($this->average_overall) {
            return $this->average_overall;
        }

        // Fallback to calculation
        return $this->performCalculation();
    }

    public function performCalculation()
    {
        // Calculate average of averages per semester (1-5)
        $totalAvg = 0;
        $count = 0;

        // Semesters 1-4
        for ($i = 1; $i <= 4; $i++) {
            $avg = $this->calculateAverage($i);
            if ($avg > 0) {
                $totalAvg += $avg;
                $count++;
            }
        }

        // Semester 5 (PKL Priority)
        $sem5Akademik = $this->calculateAverage(5);
        $sem5Pkl = $this->calculatePklAverage();
        $sem5 = $sem5Pkl > 0 ? $sem5Pkl : $sem5Akademik;

        if ($sem5 > 0) {
            $totalAvg += $sem5;
            $count++;
        }

        return $count > 0 ? $totalAvg / 5 : 0;
    }

    public function updateAverages()
    {
        $this->average_sem1 = $this->calculateAverage(1) ?? 0;
        $this->average_sem2 = $this->calculateAverage(2) ?? 0;
        $this->average_sem3 = $this->calculateAverage(3) ?? 0;
        $this->average_sem4 = $this->calculateAverage(4) ?? 0;

        $sem5Akademik = $this->calculateAverage(5) ?? 0;
        $sem5Pkl = $this->calculatePklAverage() ?? 0;
        $this->average_sem5 = $sem5Pkl > 0 ? $sem5Pkl : $sem5Akademik;

        $this->average_overall = $this->performCalculation();
        $this->save();
    }
}
