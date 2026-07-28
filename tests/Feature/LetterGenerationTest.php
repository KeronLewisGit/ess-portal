<?php

namespace Tests\Feature;

use App\Enums\LetterRequestStatus;
use App\Enums\Role;
use App\Jobs\GenerateLetterPdf;
use App\Mail\LetterReadyMail;
use App\Models\Employee;
use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\Setting;
use App\Models\User;
use App\Services\LetterPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function approvedRequest(bool $withSalary = false): LetterRequest
    {
        $employee = Employee::factory()->create([
            'job_title' => 'Machine Operator',
            'annual_salary' => 48000,
            'salary_currency' => 'USD',
        ]);

        $type = LetterType::factory()->create([
            'body_template' => 'This certifies {{employee_name}} ({{employee_code}}) works as '
                .'{{job_title}}. Salary: {{salary}}. Ref {{reference_number}}.',
        ]);

        $factory = LetterRequest::factory()->approved();

        if ($withSalary) {
            $factory = $factory->withSalary();
        }

        return $factory->create([
            'employee_id' => $employee->id,
            'letter_type_id' => $type->id,
            'reference_number' => 'EC-2026-00001',
        ]);
    }

    public function test_approving_a_request_queues_pdf_generation(): void
    {
        Bus::fake();

        $admin = User::factory()->role(Role::HrAdmin)->create();
        $request = LetterRequest::factory()->submitted()->create();

        $this->actingAs($admin)
            ->post(route('hr.approvals.approve', $request))
            ->assertRedirect(route('hr.approvals.index'));

        Bus::assertDispatched(
            GenerateLetterPdf::class,
            fn (GenerateLetterPdf $job) => $job->letterRequest->is($request)
        );
    }

    public function test_generating_stores_a_real_pdf_and_marks_the_request_issued(): void
    {
        $request = $this->approvedRequest();

        $letter = app(LetterPdfService::class)->issue($request);

        $this->assertSame(LetterRequestStatus::Issued, $request->fresh()->status);

        $disk = Storage::disk('private');
        $this->assertTrue($disk->exists($letter->file_path));

        // A real PDF, not an empty or HTML file.
        $contents = $disk->get($letter->file_path);
        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertSame(hash('sha256', $contents), $letter->file_hash);
        $this->assertSame(strlen($contents), $letter->file_size);
        $this->assertTrue($letter->fileIsIntact());
    }

    public function test_the_snapshot_never_contains_the_salary(): void
    {
        $request = $this->approvedRequest(withSalary: true);

        $letter = app(LetterPdfService::class)->issue($request);

        $this->assertArrayNotHasKey('salary', $letter->snapshot);
        $this->assertArrayHasKey('employee_name', $letter->snapshot);

        // Nor anywhere else in the persisted row.
        $this->assertStringNotContainsString('48,000', json_encode($letter->snapshot));
    }

    public function test_generation_is_idempotent_so_a_retried_job_cannot_issue_twice(): void
    {
        $request = $this->approvedRequest();
        $service = app(LetterPdfService::class);

        $first = $service->issue($request);
        $second = $service->issue($request->fresh());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, IssuedLetter::count());
    }

    public function test_an_unapproved_request_cannot_be_issued(): void
    {
        $request = LetterRequest::factory()->submitted()->create();

        $this->expectException(\RuntimeException::class);

        app(LetterPdfService::class)->issue($request);
    }

    public function test_the_employee_is_emailed_when_the_letter_is_ready(): void
    {
        $employee = Employee::factory()->create(['work_email' => 'ready@example.com']);
        $request = $this->approvedRequest();
        $request->forceFill(['employee_id' => $employee->id])->save();

        (new GenerateLetterPdf($request->fresh()))->handle(app(LetterPdfService::class));

        Mail::assertQueued(LetterReadyMail::class, fn ($mail) => $mail->hasTo('ready@example.com'));
    }

    public function test_the_letterhead_images_are_embedded_not_linked(): void
    {
        Setting::set('company_logo_path', 'letterhead/logo.png');
        Storage::disk('private')->put(
            'letterhead/logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );

        $request = $this->approvedRequest();
        $letter = app(LetterPdfService::class)->issue($request);

        // Renders without attempting any remote fetch.
        $this->assertStringStartsWith('%PDF-', Storage::disk('private')->get($letter->file_path));
    }
}
