<?php

namespace App\Livewire;

use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class LegerImport extends Component
{
    use WithFileUploads;

    public $files = []; // Array to hold files for each semester
    public $semesters = []; // Cache semester data

    // Modal View Data
    public $viewSemester = null;
    public $viewData = [];
    public $showViewModal = false;

    // Modal Missing Students
    public $missingStudents = [];
    public $showMissingModal = false;

    public $uploadStatus = []; // Store status messages per semester key

    public $skippedData = []; // Store skipped data per semester key
    public $viewSkippedData = [];
    public $showSkippedModal = false;

    // Preview Mode
    public $previewMode = false;
    public $previewData = [];
    public $previewKey = null;
    public $showPreviewModal = false;
    public $emptyValueAction = 'ignore'; // 'ignore' or 'zero'

    public function mount()
    {
        $this->refreshSemesters();
    }

    public function refreshSemesters()
    {
        $this->semesters = Semester::all()->keyBy(function ($item) {
            return $item->semester_number . '_' . $item->type;
        })->toArray();
    }

    public function updatedFiles($value, $key)
    {
        set_time_limit(300); // Increase execution time to 5 minutes

        // $key format: semesterNumber_type (e.g., 1_academic, 5_pkl)
        $parts = explode('_', $key);
        $semesterNum = $parts[0];
        $type = $parts[1] ?? 'academic';

        $this->parseForPreview($semesterNum, $type);
    }

    public function parseForPreview($semesterNumber, $type)
    {
        $key = $semesterNumber . '_' . $type;

        if (!isset($this->files[$key]))
            return;

        $file = $this->files[$key];

        $this->validate([
            "files.$key" => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $filePath = $file->getRealPath();
        $reader = new Reader();
        $reader->open($filePath);

        $isHeader = true;
        $header = [];
        $subjects = [];
        $parsedRows = [];
        $previewLimit = 50; // Limit rows for preview to avoid memory issues
        $rowCount = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $data = $row->toArray();

                if ($isHeader) {
                    $header = $data;
                    $nonSubjectCols = ['no', 'nama', 'nisn', 'nomor', 'name', 'kelas', 'program'];

                    foreach ($header as $index => $colName) {
                        if (!in_array(strtolower($colName), $nonSubjectCols) && !empty($colName)) {
                            $subjects[$index] = $colName;
                        }
                    }
                    $isHeader = false;
                    continue;
                }

                // Find NISN index
                $nisnIndex = -1;
                $namaIndex = -1;

                foreach ($header as $idx => $h) {
                    if (strtolower($h) === 'nisn')
                        $nisnIndex = $idx;
                    if (strtolower($h) === 'nama')
                        $namaIndex = $idx;
                }

                if ($nisnIndex === -1)
                    continue;

                $nisn = $data[$nisnIndex] ?? null;
                $nama = $namaIndex !== -1 ? ($data[$namaIndex] ?? '') : '';

                if (empty($nisn))
                    continue;

                // Basic validation for preview
                $status = 'valid';
                $notes = [];

                if (!ctype_digit((string) $nisn)) {
                    $status = 'error';
                    $notes[] = 'NISN bukan angka';
                }

                $student = Student::where('nisn', (string) $nisn)->first();
                if (!$student) {
                    $status = 'error';
                    $notes[] = 'Siswa tidak ditemukan';
                } elseif (!empty($nama) && strtolower(trim($nama)) !== strtolower(trim($student->nama))) {
                    $status = 'warning';
                    $notes[] = 'Nama tidak cocok';
                }

                // Extract grades for preview
                $grades = [];
                foreach ($subjects as $index => $subjectName) {
                    $val = $data[$index] ?? null;
                    if ($val !== null && $val !== '') {
                        if (!is_numeric($val) || $val < 0 || $val > 100) {
                            $status = ($status === 'error') ? 'error' : 'warning';
                            $notes[] = "Nilai $subjectName invalid";
                        }
                        $grades[$subjectName] = $val;
                    } else {
                        $grades[$subjectName] = null; // Empty value
                    }
                }

                $parsedRows[] = [
                    'nisn' => $nisn,
                    'nama' => $nama,
                    'grades' => $grades,
                    'status' => $status,
                    'notes' => implode(', ', $notes)
                ];

                $rowCount++;
                if ($rowCount >= $previewLimit)
                    break;
            }
            break;
        }

        $reader->close();

        $this->previewData = [
            'semesterNumber' => $semesterNumber,
            'type' => $type,
            'subjects' => array_values($subjects),
            'rows' => $parsedRows,
            'totalRows' => $rowCount
        ];
        $this->previewKey = $key;
        $this->showPreviewModal = true;
    }

    public function confirmImport()
    {
        set_time_limit(300); // Increase execution time to 5 minutes

        if (!$this->previewKey || empty($this->previewData))
            return;

        $semesterNumber = $this->previewData['semesterNumber'];
        $type = $this->previewData['type'];
        $key = $this->previewKey;

        // Find or create semester
        $semester = Semester::firstOrCreate(
            ['semester_number' => $semesterNumber, 'type' => $type],
            ['is_locked' => false]
        );

        if ($semester->is_locked) {
            session()->flash('error', "Semester $semesterNumber ($type) terkunci. Buka kunci terlebih dahulu.");
            $this->showPreviewModal = false;
            $this->reset("files.$key");
            return;
        }

        $file = $this->files[$key];
        $filePath = $file->getRealPath();
        $reader = new Reader();
        $reader->open($filePath);

        $isHeader = true;
        $header = [];
        $subjects = [];
        $processedNisns = [];

        // Counters
        $counts = [
            'success' => 0,
            'skipped' => 0,
            'duplicate' => 0,
            'mismatch' => 0,
            'invalid' => 0,
            'empty' => 0
        ];

        $this->skippedData[$key] = []; // This will now serve as the comprehensive log

        \DB::beginTransaction();

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $data = $row->toArray();

                    if ($isHeader) {
                        $header = $data;
                        $nonSubjectCols = ['no', 'nama', 'nisn', 'nomor', 'name', 'kelas', 'program'];
                        foreach ($header as $index => $colName) {
                            if (!in_array(strtolower($colName), $nonSubjectCols) && !empty($colName)) {
                                $subjects[$index] = $colName;
                            }
                        }
                        $isHeader = false;
                        continue;
                    }

                    // Find NISN index
                    $nisnIndex = -1;
                    $namaIndex = -1;
                    foreach ($header as $idx => $h) {
                        if (strtolower($h) === 'nisn')
                            $nisnIndex = $idx;
                        if (strtolower($h) === 'nama')
                            $namaIndex = $idx;
                    }

                    if ($nisnIndex === -1)
                        continue;
                    $nisn = $data[$nisnIndex] ?? null;
                    $nama = $namaIndex !== -1 ? ($data[$namaIndex] ?? '') : '';

                    if (empty($nisn))
                        continue;

                    $nisnString = (string) $nisn;

                    // 1. Check NISN Format
                    if (!ctype_digit($nisnString)) {
                        $counts['skipped']++;
                        $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'ERROR', 'reason' => 'NISN bukan angka'];
                        continue;
                    }

                    // 2. Check Duplicate in File
                    if (in_array($nisnString, $processedNisns)) {
                        $counts['duplicate']++;
                        $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'WARNING', 'reason' => 'Duplikat dalam file (diabaikan)'];
                        continue;
                    }
                    $processedNisns[] = $nisnString;

                    // 3. Check Student Exists
                    $student = Student::where('nisn', $nisnString)->first();
                    if (!$student) {
                        $counts['skipped']++;
                        $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'ERROR', 'reason' => 'Tidak ada di Data Master'];
                        continue;
                    }

                    // 4. Check Name Mismatch
                    if (!empty($nama) && strtolower(trim($nama)) !== strtolower(trim($student->nama))) {
                        $counts['mismatch']++;
                        $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'WARNING', 'reason' => "Nama beda dengan database ({$student->nama})"];
                    }

                    foreach ($subjects as $index => $subjectName) {
                        $value = $data[$index] ?? null;

                        // Handle Empty Values
                        if ($value === null || $value === '') {
                            if ($this->emptyValueAction === 'zero') {
                                $value = 0;
                                $counts['empty']++;
                                $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'INFO', 'reason' => "Nilai $subjectName kosong, diisi 0"];
                            } else {
                                continue; // Ignore/Skip
                            }
                        }

                        if (is_numeric($value)) {
                            $numValue = floatval($value);
                            if ($numValue < 0 || $numValue > 100) {
                                $counts['invalid']++;
                                $oldValue = $numValue;
                                $numValue = max(0, min(100, $numValue));
                                $this->skippedData[$key][] = ['nisn' => $nisn, 'nama' => $nama, 'type' => 'WARNING', 'reason' => "Nilai $subjectName ($oldValue) di-clamp jadi $numValue"];
                            }

                            Grade::updateOrCreate(
                                ['student_nisn' => $student->nisn, 'semester_id' => $semester->id, 'subject_name' => $subjectName],
                                ['value' => $numValue]
                            );
                        }
                    }

                    // Update averages for the student
                    $student->updateAverages();
                }
                break;
            }

            \DB::commit();

        } catch (\Exception $e) {
            \DB::rollBack();
            $reader->close();
            session()->flash('error', "Import gagal: " . $e->getMessage());
            return;
        }

        $reader->close();
        $this->refreshSemesters();
        $this->showPreviewModal = false;

        $studentCount = Grade::where('semester_id', $semester->id)->distinct('student_nisn')->count();
        $subjectCount = Grade::where('semester_id', $semester->id)->distinct('subject_name')->count();

        $statusMsg = "✅ $studentCount siswa tersimpan<br>📚 $subjectCount mapel terdeteksi";
        if ($counts['skipped'] > 0)
            $statusMsg .= "<br><span style='color: var(--danger)'>❌ {$counts['skipped']} baris error</span>";
        if ($counts['duplicate'] > 0)
            $statusMsg .= "<br><span style='color: var(--warning)'>⚠️ {$counts['duplicate']} duplikat</span>";
        if ($counts['mismatch'] > 0)
            $statusMsg .= "<br><span style='color: var(--warning)'>⚠️ {$counts['mismatch']} nama mismatch</span>";
        if ($counts['invalid'] > 0)
            $statusMsg .= "<br><span style='color: var(--warning)'>⚠️ {$counts['invalid']} nilai di-clamp</span>";
        if ($counts['empty'] > 0)
            $statusMsg .= "<br><span style='color: var(--accent)'>ℹ️ {$counts['empty']} nilai 0 auto-fill</span>";

        $this->uploadStatus[$key] = $statusMsg;
        session()->flash('message', "Import Semester $semesterNumber ($type) berhasil!");
    }

    public function cancelImport()
    {
        $this->showPreviewModal = false;
        $this->previewData = [];
        if ($this->previewKey) {
            $this->reset("files.{$this->previewKey}");
        }
        $this->previewKey = null;
    }

    public function downloadSkippedLog($semesterNumber, $type = 'academic')
    {
        $key = $semesterNumber . '_' . $type;

        if (empty($this->skippedData[$key])) {
            session()->flash('error', 'Tidak ada data log untuk semester ini.');
            return;
        }

        return response()->streamDownload(function () use ($key) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues(['Type', 'NISN', 'Nama', 'Keterangan']));

            // Data
            foreach ($this->skippedData[$key] as $data) {
                $writer->addRow(Row::fromValues([
                    $data['type'] ?? 'ERROR',
                    $data['nisn'],
                    $data['nama'],
                    $data['reason']
                ]));
            }

            $writer->close();
        }, "log_import_sem{$semesterNumber}_{$type}.xlsx");
    }

    public function viewSkippedLog($semesterNumber, $type = 'academic')
    {
        $key = $semesterNumber . '_' . $type;
        $this->viewSkippedData = $this->skippedData[$key] ?? [];
        $this->showSkippedModal = true;
        $this->dispatch('open-modal', name: 'modal-skipped');
    }

    public function toggleLock($semesterNumber, $type = 'academic')
    {
        $semester = Semester::firstOrCreate(
            ['semester_number' => $semesterNumber, 'type' => $type],
            ['is_locked' => false]
        );

        $semester->update(['is_locked' => !$semester->is_locked]);
        $this->refreshSemesters();
    }

    public function loadSemesterData($semesterNumber, $type = 'academic')
    {
        // Fetch with relations for processing
        $semester = Semester::where('semester_number', $semesterNumber)
            ->where('type', $type)
            ->with(['grades.student'])
            ->first();

        if (!$semester) {
            session()->flash('error', 'Belum ada data untuk semester ini.');
            return;
        }

        // Group grades by student
        $this->viewData = $semester->grades->groupBy('student_nisn')->map(function ($grades) {
            $firstGrade = $grades->first();
            // Safety check: ensure grade and student exist
            if (!$firstGrade || !$firstGrade->student) {
                return null;
            }

            $student = $firstGrade->student;
            return [
                'nisn' => $student->nisn,
                'nama' => $student->nama,
                'average' => $grades->avg('value'),
                'count' => $grades->count()
            ];
        })->filter()->values()->toArray();

        // Store lightweight semester object for view (without relations)
        $this->viewSemester = $semester;
        $this->viewSemester->setRelations([]);

        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'modal-view');
    }

    public function checkMissing($semesterNumber, $type = 'academic')
    {
        if ($semesterNumber == 5) {
            // Special handling for Semester 5: Check if student has EITHER Academic OR PKL with value > 0
            $semIds = Semester::where('semester_number', 5)
                ->whereIn('type', ['academic', 'pkl'])
                ->pluck('id');

            if ($semIds->isEmpty()) {
                $this->missingStudents = Student::all();
            } else {
                // Get NISNs that have at least one grade > 0 in either semester
                $existingNisns = Grade::whereIn('semester_id', $semIds)
                    ->where('value', '>', 0)
                    ->pluck('student_nisn')
                    ->unique();

                $this->missingStudents = Student::whereNotIn('nisn', $existingNisns)->get();
            }
        } else {
            // Standard handling for other semesters
            $semester = Semester::where('semester_number', $semesterNumber)
                ->where('type', $type)
                ->first();

            if (!$semester) {
                $this->missingStudents = Student::all();
            } else {
                // Get NISNs that have at least one grade > 0
                $existingNisns = Grade::where('semester_id', $semester->id)
                    ->where('value', '>', 0)
                    ->pluck('student_nisn')
                    ->unique();

                $this->missingStudents = Student::whereNotIn('nisn', $existingNisns)->get();
            }
        }

        $this->showMissingModal = true;
        $this->dispatch('open-modal', name: 'modal-missing');
    }

    public function downloadTemplate($semesterNumber, $type = 'academic')
    {
        return response()->streamDownload(function () use ($semesterNumber, $type) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Define subjects based on semester
            $subjects = [];
            if ($type === 'pkl') {
                $subjects = ['Nilai PKL'];
            } else {
                if ($semesterNumber <= 2) { // Kelas X
                    $subjects = ['Pendidikan Agama', 'Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah', 'Seni Budaya', 'PJOK', 'Informatika', 'IPAS', 'Dasar Kejuruan', 'Muatan Lokal'];
                } elseif ($semesterNumber <= 4) { // Kelas XI
                    $subjects = ['Pendidikan Agama', 'Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'Sejarah', 'PJOK', 'PKK', 'Konsentrasi Keahlian', 'Mapel Pilihan', 'Muatan Lokal'];
                } else { // Kelas XII
                    $subjects = ['Pendidikan Agama', 'Pancasila', 'Bahasa Indonesia', 'Matematika', 'Bahasa Inggris', 'PKK', 'Konsentrasi Keahlian', 'Mapel Pilihan', 'Muatan Lokal'];
                }
            }

            // Header
            $header = array_merge(['No', 'NISN', 'Nama'], $subjects);
            $writer->addRow(Row::fromValues($header));

            // Example Data
            $exampleRow = array_merge([1, '1234567890', 'Contoh Siswa'], array_fill(0, count($subjects), 85));
            $writer->addRow(Row::fromValues($exampleRow));

            $writer->close();
        }, "template_leger_sem{$semesterNumber}_{$type}.xlsx");
    }

    public function render()
    {
        return view('livewire.leger-import');
    }
}
