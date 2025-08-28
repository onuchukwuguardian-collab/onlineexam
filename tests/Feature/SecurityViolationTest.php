<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\ClassModel;

class SecurityViolationTest extends TestCase
{
    use RefreshDatabase;

    private $student;
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a class
        $class = ClassModel::factory()->create();

        // Create a student and a subject for the tests
        $this->student = User::factory()->create(['role' => 'student', 'class_id' => $class->id]);
        $this->subject = Subject::factory()->create();
    }

    private function reportViolation(string $violationType = 'tab_switch')
    {
        return $this->postJson(route('api.security.violation'), [
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'violation_type' => $violationType,
            'description' => 'Test violation: ' . $violationType,
        ]);
    }

    /** @test */
    public function a_student_can_be_banned_for_the_first_time_for_a_tab_switch()
    {
        $this->actingAs($this->student);

        $response = $this->reportViolation();

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'banned' => true,
        ]);

        $this->assertDatabaseHas('exam_bans', [
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'is_active' => true,
            'ban_count' => 1,
        ]);

        $this->assertEquals(1, ExamBan::count());
    }

    /** @test */
    public function reporting_a_violation_when_already_actively_banned_does_not_create_a_new_ban()
    {
        $this->actingAs($this->student);

        // First violation and ban
        $this->reportViolation();
        $this->assertEquals(1, ExamBan::count());
        $this->assertDatabaseHas('exam_bans', [
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'ban_count' => 1,
        ]);

        // Second violation while still banned
        $response = $this->reportViolation();

        $response->assertStatus(200);
        $response->assertJson(['banned' => true]);

        // Assert that no new ban was created
        $this->assertEquals(1, ExamBan::count());
    }

    /** @test */
    public function a_reactivated_student_can_be_banned_again_for_the_same_subject()
    {
        $this->actingAs($this->student);

        // First ban
        $this->reportViolation();
        $this->assertEquals(1, ExamBan::count());

        // Deactivate the ban (simulate admin reactivation)
        $ban = ExamBan::first();
        $ban->update(['is_active' => false, 'reactivation_reason' => 'Test reactivation']);

        $this->assertDatabaseHas('exam_bans', [
            'id' => $ban->id,
            'is_active' => false,
        ]);

        // Report a new violation for the same subject
        $response = $this->reportViolation();

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'banned' => true,
        ]);

        // Assert that no new ban record was created
        $this->assertEquals(1, ExamBan::count());

        // Assert that the existing ban record was updated (reactivated)
        $this->assertDatabaseHas('exam_bans', [
            'id' => $ban->id,
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'is_active' => true,
            'ban_count' => 2, // Ban count should be incremented
        ]);
    }
}
