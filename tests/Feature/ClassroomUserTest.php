<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClassroomUserTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
        $this->student = User::factory()->create();
        $course = Course::create(['name' => 'Sistemas']);
        $this->classroom = Classroom::create(['name' => 'Turma A', 'vacancies' => 30, 'course_id' => $course->id]);
    }

    private function endpoint(): string
    {
        return '/classrooms/'.$this->classroom->id.'/users';
    }

    public function test_it_attaches_a_user_and_returns_users_without_sensitive_fields(): void
    {
        $this->postJson($this->endpoint(), ['user_id' => $this->student->id, 'action' => 'attach'])
            ->assertOk()->assertJsonCount(1, 'data.users')->assertJsonPath('data.users.0.id', $this->student->id)
            ->assertJsonMissingPath('data.users.0.password')->assertJsonMissingPath('data.users.0.remember_token');

        $this->assertDatabaseHas('classroom_user', ['classroom_id' => $this->classroom->id, 'user_id' => $this->student->id]);
        $this->assertTrue($this->student->classrooms->contains($this->classroom));
        $this->assertTrue($this->classroom->users->contains($this->student));
        $this->assertNotNull(DB::table('classroom_user')->value('created_at'));
    }

    public function test_attaching_twice_does_not_duplicate_the_enrollment(): void
    {
        $payload = ['user_id' => $this->student->id, 'action' => 'attach'];

        $this->postJson($this->endpoint(), $payload)->assertOk();
        $this->postJson($this->endpoint(), $payload)->assertOk()->assertJsonCount(1, 'data.users');
        $this->assertDatabaseCount('classroom_user', 1);
    }

    public function test_it_detaches_only_the_selected_user_and_can_repeat_the_operation(): void
    {
        $other = User::factory()->create();
        $this->classroom->users()->attach([$this->student->id, $other->id]);
        $payload = ['user_id' => $this->student->id, 'action' => 'detach'];

        $this->postJson($this->endpoint(), $payload)->assertOk()
            ->assertJsonCount(1, 'data.users')->assertJsonPath('data.users.0.id', $other->id);
        $this->postJson($this->endpoint(), $payload)->assertOk();
        $this->assertDatabaseMissing('classroom_user', ['classroom_id' => $this->classroom->id, 'user_id' => $this->student->id]);
        $this->assertDatabaseHas('classroom_user', ['classroom_id' => $this->classroom->id, 'user_id' => $other->id]);
    }

    public function test_multiple_users_can_join_multiple_classrooms_independently(): void
    {
        $other = User::factory()->create();
        $second = Classroom::create(['name' => 'Turma B', 'vacancies' => 30, 'course_id' => $this->classroom->course_id]);

        $this->postJson($this->endpoint(), ['user_id' => $this->student->id, 'action' => 'attach'])->assertOk();
        $this->postJson($this->endpoint(), ['user_id' => $other->id, 'action' => 'attach'])->assertOk();
        $this->postJson('/classrooms/'.$second->id.'/users', ['user_id' => $this->student->id, 'action' => 'attach'])->assertOk();
        $this->postJson($this->endpoint(), ['user_id' => $this->student->id, 'action' => 'detach'])->assertOk();

        $this->assertDatabaseCount('classroom_user', 2);
        $this->assertDatabaseHas('classroom_user', ['classroom_id' => $second->id, 'user_id' => $this->student->id]);
    }

    public function test_invalid_membership_parameters_are_rejected_without_changes(): void
    {
        foreach ([
            [],
            ['user_id' => 999999, 'action' => 'attach'],
            ['user_id' => 'invalid', 'action' => 'attach'],
            ['user_id' => $this->student->id, 'action' => 'invalid'],
            ['user_id' => $this->student->id, 'action' => ['attach']],
            ['user_id' => $this->student->id],
        ] as $payload) {
            $this->postJson($this->endpoint(), $payload)->assertUnprocessable();
        }

        $this->assertDatabaseCount('classroom_user', 0);
    }

    public function test_a_missing_classroom_returns_not_found(): void
    {
        $this->postJson('/classrooms/999999/users', ['user_id' => $this->student->id, 'action' => 'attach'])->assertNotFound();
    }

    public function test_deleting_a_classroom_cleans_up_the_pivot_but_keeps_users_and_course(): void
    {
        $this->classroom->users()->attach($this->student);

        $this->deleteJson('/classrooms/'.$this->classroom->id)->assertNoContent();
        $this->assertDatabaseCount('classroom_user', 0);
        $this->assertDatabaseHas('users', ['id' => $this->student->id]);
        $this->assertDatabaseHas('courses', ['id' => $this->classroom->course_id]);
    }

    public function test_deleting_a_user_cleans_up_the_pivot_but_keeps_the_classroom(): void
    {
        $this->classroom->users()->attach($this->student);

        $this->deleteJson('/users/'.$this->student->id)->assertOk();
        $this->assertDatabaseCount('classroom_user', 0);
        $this->assertDatabaseHas('classrooms', ['id' => $this->classroom->id]);
    }

    public function test_the_database_itself_rejects_duplicate_enrollments(): void
    {
        $this->classroom->users()->attach($this->student);

        $this->expectException(QueryException::class);
        $this->classroom->users()->attach($this->student);
    }
}
