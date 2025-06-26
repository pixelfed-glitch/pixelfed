<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\UserInvite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Jobs\UserInvitePipeline\DispatchUserInvitePipeline;

class UserInviteController extends Controller
{
    public function authPreflight($request, $maxUserCheck = false, $authCheck = true)
    {
        if($authCheck) {
            abort_unless($request->user(), 404);
        }
        abort_unless(config('pixelfed.user_invites.enabled'), 404);
        }
        if($maxUserCheck == true) {
            $hasLimit = config('pixelfed.enforce_max_users');
            if($hasLimit) {
                $count = User::where(function($q){ return $q->whereNull('status')->orWhereNotIn('status', ['deleted','delete']); })->count();
                $limit = (int) config('pixelfed.max_users');

                abort_if($limit && $limit <= $count, 404);
            }
        }
    }
	public function create(Request $request)
	{
        $this->authPreflight($request);
		return view('settings.invites.create');
	}

	public function show(Request $request)
	{
        $this->authPreflight($request);
		$invites = UserInvite::whereUserId(Auth::id())->paginate(10);
		$limit = config('pixelfed.user_invites.limit.total');
		$used = UserInvite::whereUserId(Auth::id())->count();
		return view('settings.invites.home', compact('invites', 'limit', 'used'));
	}

	public function store(Request $request)
	{
        $this->authPreflight($request);
		$this->validate($request, [
			'email' => 'required|email|unique:users|unique:user_invites',
			'message' => 'nullable|string|max:500',
			'tos'	=> 'required|accepted'
		]);

		$invite = new UserInvite;
		$invite->user_id = Auth::id();
		$invite->profile_id = Auth::user()->profile->id;
		$invite->email = $request->input('email');
		$invite->message = $request->input('message');
		$invite->key = (string) Str::uuid();
		$invite->token = str_random(32);
		$invite->save();

        DispatchUserInvitePipeline::dispatch($invite);

		return redirect(route('settings.invites'));
	}

    public function delete(Request $request)
    {
        $this->authPreflight($request);
        $this->validate($request, [
            'id' => 'required',
        ]);
        Log::info('Delete request: ', [
            'request' => $request,
        ]);
        $invite = UserInvite::whereParentId($request->user()->id)
            ->findOrFail($request->input('id'));

        $invite->delete();

		return redirect(route('settings.invites'));
    }
}
