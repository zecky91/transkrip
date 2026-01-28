<?php

use App\Livewire\StudentManager;
use App\Livewire\LegerImport;
use App\Livewire\ResultCalculator;
use App\Livewire\EligibleStudents;
use App\Livewire\Dashboard;
use App\Livewire\LoginPage;
use App\Livewire\StudentGrades;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public Routes
Route::get('/login', LoginPage::class)->name('login');
Route::get('/student-grades', StudentGrades::class)->name('student-grades');
Route::post('/logout', function () {
    Auth::logout();
    session()->forget(['student_nisn', 'student_nama']);
    return redirect('/login');
})->name('logout');

// Admin Protected Routes
Route::middleware(['admin'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/dashboard', Dashboard::class);
    Route::get('/students', StudentManager::class)->name('students');
    Route::get('/import', LegerImport::class)->name('import');
    Route::get('/results', ResultCalculator::class)->name('results');
    Route::get('/eligible', EligibleStudents::class)->name('eligible');
});
