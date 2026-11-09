<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClassroomRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_it_creates_a_classroom_with_a_course(): void
    {
        $course = Course::create(['name' => 'Sistemas']);

        $response = $this->postJson('/classrooms', ['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id])
            ->assertCreated()->assertJsonPath('data.course.id', $course->id)->assertJsonPath('data.course_id', $course->id);

        $classroom = Classroom::findOrFail($response->json('data.id'));
        $this->assertTrue($classroom->course->is($course));
        $this->assertTrue($course->classrooms->contains($classroom));
    }

    public function test_list_and_show_include_the_course_and_can_filter_by_course(): void
    {
        $first = Course::create(['name' => 'Sistemas']);
        $second = Course::create(['name' => 'Administração']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $first->id]);
        Classroom::create(['name' => 'Turma B', 'vacancies' => 20, 'course_id' => $second->id]);

        $this->getJson('/classrooms')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.course.id', $first->id);
        $this->getJson('/classrooms?course_id='.$second->id)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.course.id', $second->id);
        $this->getJson('/classrooms/'.$classroom->id)->assertOk()->assertJsonPath('data.course.name', 'Sistemas');
        $this->getJson('/classrooms?name=Turma%20A')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_can_transfer_a_classroom_to_another_course(): void
    {
        $first = Course::create(['name' => 'Sistemas']);
        $second = Course::create(['name' => 'Administração']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $first->id]);

        $this->putJson('/classrooms/'.$classroom->id, ['name' => 'Turma B', 'vacancies' => 20, 'course_id' => $second->id])
            ->assertOk()->assertJsonPath('data.course.id', $second->id);
        $this->assertSame($second->id, $classroom->fresh()->course_id);
        $this->deleteJson('/courses/'.$first->id)->assertNoContent();
    }

    public function test_the_course_is_required_and_must_exist_on_create_and_update(): void
    {
        $course = Course::create(['name' => 'Sistemas']);
        $classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id]);

        foreach ([null, 999999, 'invalid'] as $courseId) {
            $payload = ['name' => 'Turma B', 'vacancies' => 30, 'course_id' => $courseId];
            $this->postJson('/classrooms', $payload)->assertUnprocessable()->assertJsonValidationErrors('course_id');
            $this->putJson('/classrooms/'.$classroom->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('course_id');
        }

        $this->assertDatabaseCount('classrooms', 1);
        $this->assertSame($course->id, $classroom->fresh()->course_id);
    }

    public function test_fractional_vacancies_are_rejected(): void
    {
        $course = Course::create(['name' => 'Sistemas']);

        $this->postJson('/classrooms', ['name' => 'Turma A', 'vacancies' => 2.5, 'course_id' => $course->id])
            ->assertUnprocessable()->assertJsonValidationErrors('vacancies');
    }
}
