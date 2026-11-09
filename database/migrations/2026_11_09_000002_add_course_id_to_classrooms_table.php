<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->constrained()->restrictOnDelete();
        });

        // Preserva turmas cadastradas antes da existência do módulo de cursos.
        if (DB::table('classrooms')->exists()) {
            $courseId = DB::table('courses')->insertGetId([
                'name' => 'Curso legado',
                'description' => 'Curso de transição para turmas anteriores ao módulo de cursos.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('classrooms')->update(['course_id' => $courseId]);
        }

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
