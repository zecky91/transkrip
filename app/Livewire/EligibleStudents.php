<?php

namespace App\Livewire;

use App\Models\Student;
use Livewire\Component;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class EligibleStudents extends Component
{
    public $programFilter = '';

    // Updated quotas per user specification
    const QUOTAS = [
        'Teknik Geospasial' => 14,
        'Teknik Jaringan Komputer dan Telekomunikasi' => 16,
        'Teknik Ketenagalistrikan' => 22,
        'Teknik Elektronika' => 30,
        'Teknik Pengelasan dan Fabrikasi Logam' => 7,
        'Teknik Otomotif' => 21,
        'Teknik Konstruksi dan Perumahan' => 17,
        'Desain Pemodelan dan Informasi Bangunan' => 20,
        'Teknik Mesin' => 19,
    ];

    public function export()
    {
        set_time_limit(300);
        $students = Student::with('grades')->get();

        // Calculate averages and group by program
        $grouped = $students->groupBy('program');

        return response()->streamDownload(function () use ($grouped) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $isFirst = true;
            foreach ($grouped as $program => $list) {
                // Sort by average desc
                $sorted = $list->sortByDesc(function ($s) {
                    return $s->calculateOverallAverage();
                });

                $quota = self::QUOTAS[$program] ?? 0;
                $rank = 1;

                // Create sheet for each program
                if (!$isFirst) {
                    $writer->addNewSheetAndMakeItCurrent();
                }
                $isFirst = false;

                $sheet = $writer->getCurrentSheet();
                $sheet->setName(substr($program ?? 'Unknown', 0, 31));

                // Header
                $writer->addRow(Row::fromValues([
                    'Ranking',
                    'NISN',
                    'Nama',
                    'Kelas',
                    'Program',
                    'Sem 1',
                    'Sem 2',
                    'Sem 3',
                    'Sem 4',
                    'Sem 5',
                    'Rata-rata',
                    'Status',
                    'Kuota'
                ]));

                foreach ($sorted as $s) {
                    $avg = $s->calculateOverallAverage();
                    $isEligible = $rank <= $quota;

                    // Determine Sem 5 value (PKL priority)
                    $sem5Akademik = $s->calculateAverage(5) ?? 0;
                    $sem5Pkl = $s->calculatePklAverage() ?? 0;
                    $sem5Final = $sem5Pkl > 0 ? $sem5Pkl : $sem5Akademik;

                    $writer->addRow(Row::fromValues([
                        $rank,
                        $s->nisn,
                        $s->nama,
                        $s->kelas,
                        $s->program,
                        number_format($s->calculateAverage(1) ?? 0, 2),
                        number_format($s->calculateAverage(2) ?? 0, 2),
                        number_format($s->calculateAverage(3) ?? 0, 2),
                        number_format($s->calculateAverage(4) ?? 0, 2),
                        number_format($sem5Final, 2),
                        number_format($avg, 2),
                        $isEligible ? 'Eligible' : 'Tidak Eligible',
                        $quota
                    ]));
                    $rank++;
                }
            }

            $writer->close();
        }, 'Daftar_Siswa_Eligible_' . date('Y-m-d') . '.xlsx');
    }

    public function render()
    {
        set_time_limit(300);
        $query = Student::query();
        if ($this->programFilter) {
            $query->where('program', $this->programFilter);
        }

        $students = $query->get();

        // Use stored averages directly
        $processedStudents = $students->map(function ($s) {
            $s->sem1 = $s->average_sem1 ?? 0;
            $s->sem2 = $s->average_sem2 ?? 0;
            $s->sem3 = $s->average_sem3 ?? 0;
            $s->sem4 = $s->average_sem4 ?? 0;
            $s->sem5 = $s->average_sem5 ?? 0;
            $s->average = $s->average_overall ?? 0;

            // We can't easily determine isPkl from stored average alone without extra column, 
            // but for display purposes the value is what matters.
            // If needed, we could store is_pkl_used in DB too, but for now let's assume valid.
            return $s;
        })->sortByDesc('average')->values();

        // Apply ranking and eligibility
        $quota = self::QUOTAS[$this->programFilter] ?? 0;

        // Get all programs from database for dropdown
        $allPrograms = Student::select('program')->distinct()->whereNotNull('program')->pluck('program');

        return view('livewire.eligible-students', [
            'students' => $processedStudents,
            'quota' => $quota,
            'allPrograms' => $allPrograms
        ]);
    }
}
