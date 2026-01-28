<?php

namespace App\Livewire;

use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $selectedStudents = [];
    public $selectAll = false;

    // Form Properties
    public $nisn, $nama, $kelas, $program;
    public $isEditMode = false;
    public $showFormModal = false;

    // Import Property
    public $file;

    protected $queryString = ['search'];

    protected $rules = [
        'nisn' => 'required|numeric|digits:10|unique:students,nisn',
        'nama' => 'required|string|max:255',
        'kelas' => 'required|string',
        'program' => 'required|string',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedStudents = Student::where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('nisn', 'like', '%' . $this->search . '%')
                ->orWhere('kelas', 'like', '%' . $this->search . '%')
                ->pluck('nisn')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function create()
    {
        $this->reset(['nisn', 'nama', 'kelas', 'program']);
        $this->isEditMode = false;
        $this->showFormModal = true;
        $this->dispatch('open-modal', name: 'modal-form');
    }

    public function store()
    {
        $this->validate();

        Student::create([
            'nisn' => $this->nisn,
            'nama' => $this->nama,
            'kelas' => $this->kelas,
            'program' => $this->program,
        ]);

        $this->reset(['nisn', 'nama', 'kelas', 'program', 'showFormModal']);
        $this->dispatch('close-modal', name: 'modal-form');
        session()->flash('message', 'Data siswa berhasil ditambahkan!');
    }

    public function edit($nisn)
    {
        $student = Student::findOrFail($nisn);
        $this->nisn = $student->nisn;
        $this->nama = $student->nama;
        $this->kelas = $student->kelas;
        $this->program = $student->program;

        $this->isEditMode = true;
        $this->showFormModal = true;
        $this->dispatch('open-modal', name: 'modal-form');
    }

    public function update()
    {
        $this->validate([
            'nisn' => 'required|numeric|digits:10|exists:students,nisn',
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string',
            'program' => 'required|string',
        ]);

        $student = Student::findOrFail($this->nisn);
        $student->update([
            'nama' => $this->nama,
            'kelas' => $this->kelas,
            'program' => $this->program,
        ]);

        $this->reset(['nisn', 'nama', 'kelas', 'program', 'showFormModal', 'isEditMode']);
        $this->dispatch('close-modal', name: 'modal-form');
        session()->flash('message', 'Data siswa berhasil diperbarui!');
    }

    // Delete confirmation properties
    public $showDeleteModal = false;
    public $deleteNisn = null;
    public $deleteName = '';
    public $showDeleteSelectedModal = false;

    public function confirmDelete($nisn, $nama)
    {
        $this->deleteNisn = $nisn;
        $this->deleteName = $nama;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->deleteNisn) {
            Student::where('nisn', $this->deleteNisn)->delete();
            session()->flash('message', 'Siswa berhasil dihapus.');
        }
        $this->reset(['showDeleteModal', 'deleteNisn', 'deleteName']);
    }

    public function confirmDeleteSelected()
    {
        if (count($this->selectedStudents) > 0) {
            $this->showDeleteSelectedModal = true;
        }
    }

    public function deleteSelected()
    {
        Student::whereIn('nisn', $this->selectedStudents)->delete();
        $this->selectedStudents = [];
        $this->selectAll = false;
        $this->showDeleteSelectedModal = false;
        session()->flash('message', 'Siswa terpilih berhasil dihapus.');
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $filePath = $this->file->getRealPath();
        $reader = new Reader();
        $reader->open($filePath);

        $isHeader = true;
        $header = [];
        $nisnIdx = -1;
        $namaIdx = -1;
        $kelasIdx = -1;
        $programIdx = -1;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if ($isHeader) {
                    foreach ($cells as $idx => $cell) {
                        $val = strtolower($cell);
                        if ($val === 'nisn')
                            $nisnIdx = $idx;
                        if ($val === 'nama' || $val === 'nama lengkap')
                            $namaIdx = $idx;
                        if ($val === 'kelas')
                            $kelasIdx = $idx;
                        if ($val === 'program' || $val === 'program keahlian' || $val === 'jurusan')
                            $programIdx = $idx;
                    }
                    $isHeader = false;
                    continue;
                }

                if ($nisnIdx === -1 || $namaIdx === -1)
                    continue;

                $nisn = $cells[$nisnIdx] ?? null;
                $nama = $cells[$namaIdx] ?? null;
                $kelas = $kelasIdx !== -1 ? ($cells[$kelasIdx] ?? '') : '';
                $program = $programIdx !== -1 ? ($cells[$programIdx] ?? '') : '';

                if ($nisn && $nama) {
                    Student::updateOrCreate(
                        ['nisn' => (string) $nisn],
                        [
                            'nama' => $nama,
                            'kelas' => $kelas,
                            'program' => $program
                        ]
                    );
                }
            }
            break; // Only first sheet
        }

        $reader->close();
        $this->reset('file');
        session()->flash('message', 'Import data siswa berhasil!');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Kelas', 'Program Keahlian']));

            // Data
            $students = Student::all();
            foreach ($students as $index => $student) {
                $writer->addRow(Row::fromValues([
                    $index + 1,
                    $student->nisn,
                    $student->nama,
                    $student->kelas,
                    $student->program
                ]));
            }

            $writer->close();
        }, 'data_siswa.xlsx');
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Header
            $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Kelas', 'Program Keahlian']));

            // Example Data
            $writer->addRow(Row::fromValues([1, '1234567890', 'Contoh Siswa', 'X', 'RPL']));

            $writer->close();
        }, 'template_siswa.xlsx');
    }

    public function paginationView()
    {
        return 'livewire.custom-pagination';
    }

    public function render()
    {
        $students = Student::where('nama', 'like', '%' . $this->search . '%')
            ->orWhere('nisn', 'like', '%' . $this->search . '%')
            ->orWhere('kelas', 'like', '%' . $this->search . '%')
            ->paginate(10);

        return view('livewire.student-manager', [
            'students' => $students
        ]);
    }
}
