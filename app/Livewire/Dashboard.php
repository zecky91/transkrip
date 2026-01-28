<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Semester;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;

class Dashboard extends Component
{
    use WithFileUploads;

    public $backupFile;
    public $showRestoreConfirm = false;

    public function updatedBackupFile()
    {
        // File upload completed, show confirmation
        $this->showRestoreConfirm = true;
    }

    public function confirmRestore()
    {
        $this->showRestoreConfirm = false;
        $this->importRestore();
    }

    public function cancelRestore()
    {
        $this->showRestoreConfirm = false;
        $this->reset('backupFile');
    }

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
        // Run Spatie backup command
        Artisan::call('backup:run', ['--only-db' => true]);

        // Find the latest backup file
        $backupDisk = Storage::disk('local');
        $backupPath = config('backup.backup.destination.disks')[0] ?? 'local';
        $files = $backupDisk->files('Laravel');

        if (empty($files)) {
            $this->js("alert('Backup failed: No backup file found.')");
            return;
        }

        // Get the most recent backup
        usort($files, function ($a, $b) use ($backupDisk) {
            return $backupDisk->lastModified($b) - $backupDisk->lastModified($a);
        });

        $latestBackup = $files[0];
        $backupFullPath = $backupDisk->path($latestBackup);

        return response()->download($backupFullPath, 'backup_' . date('Y-m-d_His') . '.zip');
    }

    public function importRestore()
    {
        $this->validate([
            'backupFile' => 'required|file|mimes:zip',
        ]);

        $zipPath = $this->backupFile->getRealPath();
        $extractPath = storage_path('app/temp_restore');

        // Clean up any previous extraction
        if (File::isDirectory($extractPath)) {
            File::deleteDirectory($extractPath);
        }
        File::makeDirectory($extractPath, 0755, true);

        // DB::beginTransaction(); // Removed to avoid nested transaction error

        try {
            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // Find SQL dump file in extracted contents
            $sqlFile = null;
            $files = File::allFiles($extractPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sql') {
                    $sqlFile = $file->getPathname();
                    break;
                }
            }

            if (!$sqlFile) {
                throw new \Exception('No SQL file found in backup ZIP');
            }

            // Read and execute SQL dump
            // DB::statement('PRAGMA foreign_keys = OFF;'); // Handled by dump

            // Wipe database to ensure clean state and prevent unique constraint errors
            Artisan::call('db:wipe', ['--force' => true]);

            $sql = File::get($sqlFile);
            DB::unprepared($sql);

            // DB::statement('PRAGMA foreign_keys = ON;'); // Handled by dump
            // DB::commit(); // Removed to avoid nested transaction error

            // Cleanup
            File::deleteDirectory($extractPath);

            $this->js("alert('Restore berhasil! Data telah dipulihkan.')");
            $this->reset('backupFile');

        } catch (\Exception $e) {
            // DB::rollBack(); // Removed to avoid nested transaction error
            // DB::statement('PRAGMA foreign_keys = ON;');

            // Cleanup on error
            if (File::isDirectory($extractPath)) {
                File::deleteDirectory($extractPath);
            }

            $this->addError('backupFile', 'Restore gagal: ' . $e->getMessage());
        }
    }
}
