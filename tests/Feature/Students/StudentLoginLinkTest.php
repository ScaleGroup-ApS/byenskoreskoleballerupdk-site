<?php

use App\Actions\Students\CreateStudent;
use App\Mail\StudentMagicLoginMail;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('sends magic link on student creation and allows login', function () {
    Mail::fake();

    $action = app(CreateStudent::class);
    
    $studentData = [
        'name' => 'Magic Link User',
        'email' => 'magic@example.com',
        'phone' => '12345678',
        'cpr' => '010100-1234',
        'start_date' => now()->toDateString(),
    ];

    $student = $action->handle($studentData, app(\App\Actions\Students\SendStudentLoginLink::class));

    $magicLink = '';
    Mail::assertSent(StudentMagicLoginMail::class, function ($mail) use (&$magicLink, $student) {
        if ($mail->hasTo($student->user->email)) {
            $magicLink = $mail->url;
            return true;
        }
        return false;
    });

    expect($magicLink)->not->toBeEmpty();

    assertGuest();

    $response = get($magicLink);

    assertAuthenticatedAs($student->user);
    
    $response->assertRedirect(route('student.dashboard'));
});
