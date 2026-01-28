<?php

namespace App\Livewire;

use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use Livewire\Component;

class StudentGrades extends Component
{
    public $student;
    public $grades = [];
    public $semesters = [];

    public function mount()
    {
        $nisn = session('student_nisn');

        if (!$nisn) {
            return redirect('/login');
        }

        $this->student = Student::where('nisn', $nisn)->first();

        if (!$this->student) {
            session()->forget(['student_nisn', 'student_nama']);
            return redirect('/login');
        }

        // Get all semesters
        $this->semesters = Semester::orderBy('semester_number')
            ->orderBy('type')
            ->get();

        // Get grades grouped by semester
        $this->grades = [];
        foreach ($this->semesters as $semester) {
            $semGrades = Grade::where('student_nisn', $nisn)
                ->where('semester_id', $semester->id)
                ->get();

            if ($semGrades->count() > 0) {
                $this->grades[$semester->id] = [
                    'semester' => $semester,
                    'grades' => $semGrades,
                    'average' => $semGrades->avg('value')
                ];
            }
        }
    }

    public function logout()
    {
        session()->forget(['student_nisn', 'student_nama']);
        return redirect('/login');
    }

    public function render()
    {
        return view('livewire.student-grades')->layout('components.layouts.student');
    }
}
