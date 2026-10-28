<?php

use App\Jobs\CuratedOnboarding\CuratedOnboardingNotifyAdminUserResponsePipeline;
use App\Mail\CuratedRegisterNotifyAdminUserResponse;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Curated onboarding: notify admins when an applicant replies
|--------------------------------------------------------------------------
|
| GLITCH MODIFIED: INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_USER_RESPONSE emails the
| admins when an applicant answers a request for more details.
|
*/

function curatedReplyActivity(): CuratedRegisterActivity
{
    $cr = new CuratedRegister;
    $cr->username = 'applicant'.Str::random(6);
    $cr->email = Str::random(8).'@example.com';
    $cr->save();

    $request = CuratedRegisterActivity::create([
        'register_id' => $cr->id,
        'type' => 'request_details',
        'message' => 'Tell us more',
        'from_admin' => true,
    ]);

    return CuratedRegisterActivity::create([
        'register_id' => $cr->id,
        'reply_to_id' => $request->id,
        'type' => 'user_response',
        'message' => 'Here is more about me',
        'from_user' => true,
        'action_required' => true,
    ]);
}

it('emails admins and cc addresses about an applicant reply', function () {
    config([
        'instance.curated_registration.notify.admin.on_user_response' => true,
        'instance.curated_registration.notify.admin.on_verify_email.cc_addresses' => 'mods@example.com',
    ]);
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create();
    $activity = curatedReplyActivity();

    (new CuratedOnboardingNotifyAdminUserResponsePipeline($activity))->handle();

    Mail::assertSent(CuratedRegisterNotifyAdminUserResponse::class, 2);
    Mail::assertSent(CuratedRegisterNotifyAdminUserResponse::class, fn ($m) => $m->hasTo($admin->email));
    Mail::assertSent(CuratedRegisterNotifyAdminUserResponse::class, fn ($m) => $m->hasTo('mods@example.com'));
    expect($activity->fresh()->admin_notified_at)->not->toBeNull();
});

it('only emails the configured admin usernames', function () {
    config([
        'instance.curated_registration.notify.admin.on_user_response' => true,
        'instance.curated_registration.notify.admin.on_verify_email.to_usernames' => 'chosenadmin',
    ]);
    Mail::fake();

    $chosen = User::factory()->create(['is_admin' => true, 'username' => 'chosenadmin']);
    User::factory()->create(['is_admin' => true]);

    (new CuratedOnboardingNotifyAdminUserResponsePipeline(curatedReplyActivity()))->handle();

    Mail::assertSent(CuratedRegisterNotifyAdminUserResponse::class, 1);
    Mail::assertSent(CuratedRegisterNotifyAdminUserResponse::class, fn ($m) => $m->hasTo($chosen->email));
});

it('sends nothing when the setting is off or the admins were already notified', function () {
    Mail::fake();
    User::factory()->create(['is_admin' => true]);

    config(['instance.curated_registration.notify.admin.on_user_response' => false]);
    (new CuratedOnboardingNotifyAdminUserResponsePipeline(curatedReplyActivity()))->handle();

    config(['instance.curated_registration.notify.admin.on_user_response' => true]);
    $notified = curatedReplyActivity();
    $notified->update(['admin_notified_at' => now()]);
    (new CuratedOnboardingNotifyAdminUserResponsePipeline($notified))->handle();

    Mail::assertNothingSent();
});

it('dispatches the job when an applicant submits a reply', function () {
    config(['instance.curated_registration.notify.admin.on_user_response' => true]);
    Queue::fake();

    $reply = curatedReplyActivity();
    $crid = $reply->register_id;
    $acid = $reply->reply_to_id;
    $reply->delete();

    $this->withSession([
        'cur-reg-con.email-confirmed' => true,
        'cur-reg-con.cr-id' => $crid,
        'cur-reg-con.ac-id' => $acid,
    ])->post('/auth/sign_up/concierge/form', [
        'response' => 'Here is more about me',
        'crid' => (string) $crid,
        'acid' => (string) $acid,
    ])->assertOk();

    Queue::assertPushed(CuratedOnboardingNotifyAdminUserResponsePipeline::class);
});
