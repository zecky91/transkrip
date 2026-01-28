<?php

namespace App\Livewire;

use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;

class ResultCalculator extends Component
{
    use WithPagination;

    public $search = '';

    public function paginationView()
    {
        return 'livewire.custom-pagination';
    }

    public function render()
    {
        $students = Student::with('grades')
            ->where('nama', 'like', '%' . $this->search . '%')
            ->orWhere('nisn', 'like', '%' . $this->search . '%')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->paginate(10);

        return view('livewire.result-calculator', [
            'students' => $students
        ]);
    }
}
