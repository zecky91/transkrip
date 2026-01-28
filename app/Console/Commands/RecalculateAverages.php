<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RecalculateAverages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-averages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate averages for all students and store in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $students = \App\Models\Student::all();
        $bar = $this->output->createProgressBar(count($students));

        $bar->start();

        foreach ($students as $student) {
            $student->updateAverages();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All student averages have been recalculated.');
    }
}
