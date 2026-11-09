<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_it_creates_a_course(): void
    {
        $this->postJson('/courses', [
            'name' => 'Sistemas de Informação',
            'description' => 'Curso de graduação.',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Sistemas de Informação')
            ->assertJsonPath('data.description', 'Curso de graduação.');

        $this->assertDatabaseHas('courses', ['name' => 'Sistemas de Informação']);
    }

    public function test_description_is_optional(): void
    {
        $this->postJson('/courses', ['name' => 'Engenharia'])
            ->assertCreated()->assertJsonPath('data.description', null);
    }

    public function test_it_lists_and_filters_courses_with_their_classrooms(): void
    {
        $course = Course::create(['name' => 'Sistemas de Informação']);
        Course::create(['name' => 'Administração']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id]);

        $this->getJson('/courses')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/courses?name=Sistemas')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $course->id)
            ->assertJsonPath('data.0.classrooms.0.id', $classroom->id);
    }

    public function test_it_shows_a_course_with_its_classrooms(): void
    {
        $course = Course::create(['name' => 'Sistemas']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id]);

        $this->getJson('/courses/'.$course->id)->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.classrooms.0.id', $classroom->id);
    }

    public function test_it_updates_a_course_and_supports_partial_updates(): void
    {
        $course = Course::create(['name' => 'Sistemas', 'description' => 'Descrição antiga']);

        $this->putJson('/courses/'.$course->id, ['name' => 'Computação', 'description' => 'Nova descrição'])
            ->assertOk()->assertJsonPath('data.name', 'Computação');

        $this->patchJson('/courses/'.$course->id, ['description' => null])
            ->assertOk()->assertJsonPath('data.name', 'Computação')->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Computação', 'description' => null]);
    }

    public function test_it_deletes_a_course_without_classrooms(): void
    {
        $course = Course::create(['name' => 'Sistemas']);

        $this->deleteJson('/courses/'.$course->id)->assertNoContent();
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_a_course_with_classrooms_cannot_be_deleted(): void
    {
        $course = Course::create(['name' => 'Sistemas']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id]);

        $this->deleteJson('/courses/'.$course->id)->assertStatus(409);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
    }

    public function test_missing_courses_return_not_found(): void
    {
        $this->getJson('/courses/999999')->assertNotFound();
        $this->putJson('/courses/999999', ['name' => 'Sistemas'])->assertNotFound();
        $this->deleteJson('/courses/999999')->assertNotFound();
    }

    #[DataProvider('invalidCourses')]
    public function test_invalid_course_input_is_rejected(array $payload, string $field): void
    {
        $this->postJson('/courses', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('courses', 0);
    }

    public static function invalidCourses(): array
    {
        return [
            'missing name' => [[], 'name'],
            'empty name' => [['name' => '   '], 'name'],
            'short name' => [['name' => 'A'], 'name'],
            'long name' => [['name' => str_repeat('a', 256)], 'name'],
            'non-text name' => [['name' => ['invalid']], 'name'],
            'non-text description' => [['name' => 'Sistemas', 'description' => ['invalid']], 'description'],
            'long description' => [['name' => 'Sistemas', 'description' => str_repeat('a', 5001)], 'description'],
        ];
    }

    public function test_updates_validate_input_and_do_not_modify_the_course_on_failure(): void
    {
        $course = Course::create(['name' => 'Sistemas']);

        $this->patchJson('/courses/'.$course->id, ['name' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame('Sistemas', $course->fresh()->name);
    }
}
