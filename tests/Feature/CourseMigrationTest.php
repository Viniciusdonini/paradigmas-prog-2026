<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseMigrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            // As migrações deste teste recriam o banco fora do RefreshDatabase.
            RefreshDatabaseState::$inMemoryConnections = [];
            RefreshDatabaseState::$migrated = false;
        }
    }

    public function test_existing_classrooms_are_preserved_and_assigned_a_legacy_course(): void
    {
        // Volta apenas as três migrações novas, simulando uma instalação da base.
        $this->artisan('migrate:rollback', ['--step' => 3])->assertExitCode(0);
        $this->assertFalse(Schema::hasColumn('classrooms', 'course_id'));

        $classroomId = DB::table('classrooms')->insertGetId([
            'name' => 'Turma existente',
            'vacancies' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate')->assertExitCode(0);

        $courseId = DB::table('classrooms')->where('id', $classroomId)->value('course_id');
        $this->assertNotNull($courseId);
        $this->assertDatabaseHas('classrooms', ['id' => $classroomId, 'name' => 'Turma existente', 'vacancies' => 25]);
        $this->assertDatabaseHas('courses', ['id' => $courseId, 'name' => 'Curso legado']);
        $this->assertTrue(Schema::hasTable('classroom_user'));
    }

    public function test_a_fresh_database_has_no_placeholder_course(): void
    {
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_the_database_requires_classrooms_to_have_a_course(): void
    {
        $this->expectException(QueryException::class);

        DB::table('classrooms')->insert(['name' => 'Sem curso', 'vacancies' => 25]);
    }

    public function test_the_database_rejects_a_nonexistent_course(): void
    {
        $this->expectException(QueryException::class);

        DB::table('classrooms')->insert(['name' => 'Curso inválido', 'vacancies' => 25, 'course_id' => 999999]);
    }

    public function test_the_database_prevents_deletion_of_a_course_with_classrooms(): void
    {
        $courseId = DB::table('courses')->insertGetId(['name' => 'Sistemas']);
        DB::table('classrooms')->insert(['name' => 'Turma A', 'vacancies' => 25, 'course_id' => $courseId]);

        $this->expectException(QueryException::class);

        DB::table('courses')->where('id', $courseId)->delete();
    }
}
