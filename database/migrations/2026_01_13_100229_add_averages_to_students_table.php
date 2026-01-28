<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->float('average_sem1')->nullable()->after('program');
            $table->float('average_sem2')->nullable()->after('average_sem1');
            $table->float('average_sem3')->nullable()->after('average_sem2');
            $table->float('average_sem4')->nullable()->after('average_sem3');
            $table->float('average_sem5')->nullable()->after('average_sem4');
            $table->float('average_overall')->nullable()->after('average_sem5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'average_sem1',
                'average_sem2',
                'average_sem3',
                'average_sem4',
                'average_sem5',
                'average_overall'
            ]);
        });
    }
};
