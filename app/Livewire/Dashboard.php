<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Semester;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        // Total students
        $totalStudents = Student::count();

        // Students per program
        $studentsPerProgram = Student::selectRaw('program, COUNT(*) as count')
            ->groupBy('program')
            ->orderByDesc('count')
            ->get();

        // Students per class
        $studentsPerClass = Student::selectRaw('kelas, COUNT(*) as count')
            ->groupBy('kelas')
            ->orderBy('kelas')
            ->get();

        // Grades count per semester
        $gradesPerSemester = Semester::withCount('grades')
            ->orderBy('semester_number')
            ->orderBy('type')
            ->get();

        // Overall average (top 10 students)
        $topStudents = Student::with('grades')
            ->get()
            ->map(function ($s) {
                $s->average = $s->calculateOverallAverage();
                return $s;
            })
            ->sortByDesc('average')
            ->take(10)
            ->values();

        // Average grade distribution
        $gradeDistribution = [
            'A (90-100)' => 0,
            'B (80-89)' => 0,
            'C (70-79)' => 0,
            'D (60-69)' => 0,
            'E (<60)' => 0,
        ];

        $allGrades = Grade::pluck('value');
        foreach ($allGrades as $value) {
            if ($value >= 90)
                $gradeDistribution['A (90-100)']++;
            elseif ($value >= 80)
                $gradeDistribution['B (80-89)']++;
            elseif ($value >= 70)
                $gradeDistribution['C (70-79)']++;
            elseif ($value >= 60)
                $gradeDistribution['D (60-69)']++;
            else
                $gradeDistribution['E (<60)']++;
        }

        // Total grades
        $totalGrades = Grade::count();

        // Average of all grades
        $overallAverage = Grade::avg('value') ?? 0;

        return view('livewire.dashboard', [
            'totalStudents' => $totalStudents,
            'studentsPerProgram' => $studentsPerProgram,
            'studentsPerClass' => $studentsPerClass,
            'gradesPerSemester' => $gradesPerSemester,
            'topStudents' => $topStudents,
            'gradeDistribution' => $gradeDistribution,
            'totalGrades' => $totalGrades,
            'overallAverage' => $overallAverage,
        ]);
    }

    public function exportBackup()
    {
        return response()->streamDownload(function () {
            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile('php://output');

            // Sheet 1: Students
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Data Siswa');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'NISN',
                'Nama',
                'Kelas',
                'Program'
            ]));

            $students = Student::all();
            foreach ($students as $student) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $student->nisn,
                    $student->nama,
                    $student->kelas,
                    $student->program
                ]));
            }

            // Sheet 2: Grades
            $writer->addNewSheetAndMakeItCurrent();
            $sheet2 = $writer->getCurrentSheet();
            $sheet2->setName('Data Nilai');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'NISN',
                'Nama Siswa',
                'Semester',
                'Tipe',
                'Mata Pelajaran',
                'Nilai'
            ]));

            $grades = Grade::with(['student', 'semester'])->get();
            foreach ($grades as $grade) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $grade->student_nisn,
                    $grade->student->nama ?? '-',
                    $grade->semester->semester_number ?? '-',
                    $grade->semester->type ?? '-',
                    $grade->subject_name,
                    $grade->value
                ]));
            }

            $writer->close();
        }, 'backup_leger_' . date('Y-m-d_His') . '.xlsx');
    }
}
