<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_be_created()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X',
            'program' => 'RPL'
        ]);

        $this->assertDatabaseHas('students', [
            'nisn' => '1234567890',
            'nama' => 'Test Student'
        ]);
    }

    public function test_deleting_student_cascades_to_grades()
    {
        // Create student
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X',
            'program' => 'RPL'
        ]);

        // Create semester
        $semester = Semester::create([
            'semester_number' => 1,
            'type' => 'academic',
            'is_locked' => false
        ]);

        // Create grade
        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $semester->id,
            'subject_name' => 'Matematika',
            'value' => 85
        ]);

        $this->assertDatabaseHas('grades', ['student_nisn' => '1234567890']);

        // Delete student
        $student->delete();

        // Grade should be deleted too (cascade)
        $this->assertDatabaseMissing('grades', ['student_nisn' => '1234567890']);
    }

    public function test_calculate_average_returns_correct_value()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X',
            'program' => 'RPL'
        ]);

        $semester = Semester::create([
            'semester_number' => 1,
            'type' => 'academic',
            'is_locked' => false
        ]);

        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $semester->id,
            'subject_name' => 'Matematika',
            'value' => 80
        ]);

        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $semester->id,
            'subject_name' => 'Bahasa Indonesia',
            'value' => 90
        ]);

        $average = $student->calculateAverage(1);
        $this->assertEquals(85, $average);
    }

    public function test_calculate_pkl_average()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'XII',
            'program' => 'RPL'
        ]);

        $semester = Semester::create([
            'semester_number' => 5,
            'type' => 'pkl',
            'is_locked' => false
        ]);

        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $semester->id,
            'subject_name' => 'Nilai PKL',
            'value' => 95
        ]);

        $pklAvg = $student->calculatePklAverage();
        $this->assertEquals(95, $pklAvg);
    }

    public function test_calculate_overall_average_strict_division()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'XII',
            'program' => 'RPL'
        ]);

        // Academic semester 1
        $sem1 = Semester::create([
            'semester_number' => 1,
            'type' => 'academic',
            'is_locked' => false
        ]);

        // PKL semester 5
        $sem5 = Semester::create([
            'semester_number' => 5,
            'type' => 'pkl',
            'is_locked' => false
        ]);

        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $sem1->id,
            'subject_name' => 'Matematika',
            'value' => 80
        ]);

        Grade::create([
            'student_nisn' => $student->nisn,
            'semester_id' => $sem5->id,
            'subject_name' => 'Nilai PKL',
            'value' => 100
        ]);

        // Sem 1 Avg: 80
        // Sem 5 Avg: 100
        // Total: 180
        // Count: 2 semesters present
        // Formula: Total / 5 = 180 / 5 = 36
        // This confirms the strict division by 5 logic requested by user.

        $this->assertEquals(36, $student->calculateOverallAverage());
    }
}
