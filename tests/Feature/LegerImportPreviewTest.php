<?php

namespace Tests\Feature;

use App\Livewire\LegerImport;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class LegerImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_parse_file_for_preview()
    {
        // Setup Data
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X RPL',
            'program' => 'RPL'
        ]);

        // Create Excel File
        $fileName = 'test_import.xlsx';
        $filePath = storage_path('app/' . $fileName);
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Matematika', 'Bahasa Indonesia']));
        $writer->addRow(Row::fromValues([1, '1234567890', 'Test Student', 85, 90])); // Valid
        $writer->addRow(Row::fromValues([2, '9999999999', 'Unknown Student', 80, 80])); // Error: Student not found
        $writer->addRow(Row::fromValues([3, 'invalid', 'Invalid NISN', 80, 80])); // Error: Invalid NISN
        $writer->close();

        // Use Fake UploadedFile and copy content
        $file = UploadedFile::fake()->create($fileName);
        file_put_contents($file->getPathname(), file_get_contents($filePath));

        Livewire::test(LegerImport::class)
            ->set('files.1_academic', $file)
            ->assertSet('showPreviewModal', true)
            ->assertSet('previewData.totalRows', 3)
            ->assertSet('previewData.rows.0.status', 'valid')
            ->assertSet('previewData.rows.1.status', 'error')
            ->assertSet('previewData.rows.2.status', 'error');

        unlink($filePath);
    }

    public function test_confirm_import_saves_data()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X RPL',
            'program' => 'RPL'
        ]);

        // Create Excel File
        $fileName = 'test_import_confirm.xlsx';
        $filePath = storage_path('app/' . $fileName);
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Matematika']));
        $writer->addRow(Row::fromValues([1, '1234567890', 'Test Student', 85]));
        $writer->close();

        $file = UploadedFile::fake()->create($fileName);
        file_put_contents($file->getPathname(), file_get_contents($filePath));

        Livewire::test(LegerImport::class)
            ->set('files.1_academic', $file)
            ->call('confirmImport');

        $this->assertDatabaseHas('grades', [
            'student_nisn' => '1234567890',
            'subject_name' => 'Matematika',
            'value' => 85
        ]);

        unlink($filePath);
    }

    public function test_empty_value_handling_zero()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X RPL',
            'program' => 'RPL'
        ]);

        // Create Excel File with empty value
        $fileName = 'test_import_empty.xlsx';
        $filePath = storage_path('app/' . $fileName);
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Matematika']));
        $writer->addRow(Row::fromValues([1, '1234567890', 'Test Student', ''])); // Empty Math
        $writer->close();

        $file = UploadedFile::fake()->create($fileName);
        file_put_contents($file->getPathname(), file_get_contents($filePath));

        Livewire::test(LegerImport::class)
            ->set('files.1_academic', $file)
            ->set('emptyValueAction', 'zero') // Set to treat as zero
            ->call('confirmImport');

        $this->assertDatabaseHas('grades', [
            'student_nisn' => '1234567890',
            'subject_name' => 'Matematika',
            'value' => 0
        ]);

        unlink($filePath);
    }

    public function test_empty_value_handling_ignore()
    {
        $student = Student::create([
            'nisn' => '1234567890',
            'nama' => 'Test Student',
            'kelas' => 'X RPL',
            'program' => 'RPL'
        ]);

        // Create Excel File with empty value
        $fileName = 'test_import_ignore.xlsx';
        $filePath = storage_path('app/' . $fileName);
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues(['No', 'NISN', 'Nama', 'Matematika']));
        $writer->addRow(Row::fromValues([1, '1234567890', 'Test Student', ''])); // Empty Math
        $writer->close();

        $file = UploadedFile::fake()->create($fileName);
        file_put_contents($file->getPathname(), file_get_contents($filePath));

        Livewire::test(LegerImport::class)
            ->set('files.1_academic', $file)
            ->set('emptyValueAction', 'ignore') // Set to ignore
            ->call('confirmImport');

        $this->assertDatabaseMissing('grades', [
            'student_nisn' => '1234567890',
            'subject_name' => 'Matematika'
        ]);

        unlink($filePath);
    }
}
