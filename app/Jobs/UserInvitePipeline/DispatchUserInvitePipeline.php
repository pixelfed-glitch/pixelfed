<?php

namespace App\Jobs\UserInvitePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UserInvite;
use App\Mail\UserInviteMessage;
use Illuminate\Support\Facades\Mail;

class DispatchUserInvitePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invite;

    /**
     * Create a new job instance.
     */
    public function __construct(UserInvite $invite)
    {
        $this->invite = $invite;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invite = $this->invite;

        Mail::to($invite->email)->send(new UserInviteMessage($invite));
    }
}
