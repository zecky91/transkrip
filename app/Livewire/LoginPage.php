<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class LoginPage extends Component
{
    public $loginType = 'admin'; // 'admin' or 'student'

    // Admin fields
    public $email = '';
    public $password = '';

    // Student field
    public $nisn = '';

    public $errorMessage = '';

    public function switchType($type)
    {
        $this->loginType = $type;
        $this->reset(['email', 'password', 'nisn', 'errorMessage']);
    }

    public function loginAdmin()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        $this->errorMessage = 'Email atau password salah.';
    }

    public function loginStudent()
    {
        $this->validate([
            'nisn' => 'required|numeric|digits:10',
        ]);

        $student = Student::where('nisn', $this->nisn)->first();

        if (!$student) {
            $this->errorMessage = 'NISN tidak ditemukan. Pastikan NISN sudah terdaftar di Data Master.';
            return;
        }

        session(['student_nisn' => $student->nisn, 'student_nama' => $student->nama]);
        return redirect('/student-grades');
    }

    public function render()
    {
        return view('livewire.login-page')->layout('components.layouts.guest');
    }
}
