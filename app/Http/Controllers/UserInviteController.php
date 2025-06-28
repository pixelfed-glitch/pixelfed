<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Purify;
use Auth;
use App\Profile;
use App\User;
use App\Models\UserInvite;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use App\Services\EmailService;
use App\Http\Controllers\Auth\RegisterController;
use App\Jobs\UserInvitePipeline\DispatchUserInvitePipeline;

class UserInviteController extends Controller
{
    public function authPreflight($request, $maxUserCheck = false, $authCheck = true)
    {
        if($authCheck) {
            abort_unless($request->user(), 404);
        }
        abort_unless(config('pixelfed.user_invites.enabled'), 404);
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
        $invite->valid_until = now()->addDays(30);
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
        $invite = UserInvite::whereUserId($request->user()->id)
            ->findOrFail($request->input('id'));

        $invite->delete();

		return redirect(route('settings.invites'));
    }

	public function index(Request $request, $code)
	{
		if($request->user()) {
			return redirect('/');
		}
		return view('invite.user_invite', compact('code'));
	}

	public function apiVerifyCheck(Request $request)
	{
		$this->validate($request, [
			'token' => 'required',
		]);

		$invite = UserInvite::whereToken($request->input('token'))->first();
		abort_if(!$invite, 404);
		abort_if($invite->valid_until && $invite->valid_until->lt(now()), 400, 'Invite has expired.');
		abort_if($invite->used_at != null, 400, 'Invite already used.');
		$res = [
			'message' => $invite->message,
			'max_uses' => $invite->max_uses,
			'sev' => $invite->skip_email_verification
		];
		return response()->json($res);
	}

	public function apiUsernameCheck(Request $request)
	{
		$this->validate($request, [
			'token' => 'required',
			'username' => 'required'
		]);

		$invite = UserInvite::whereToken($request->input('token'))->first();
		abort_if(!$invite, 404);
		abort_if($invite->valid_until && $invite->valid_until->lt(now()), 400, 'Invite has expired.');
		abort_if($invite->used_at != null, 400, 'Invite already used.');

		$usernameRules = [
			'required',
			'min:2',
			'max:30',
			'unique:users',
			function ($attribute, $value, $fail) {
				$dash = substr_count($value, '-');
				$underscore = substr_count($value, '_');
				$period = substr_count($value, '.');

				if(ends_with($value, ['.php', '.js', '.css'])) {
					return $fail('Username is invalid.');
				}

				if(($dash + $underscore + $period) > 1) {
					return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
				}

				if (!ctype_alnum($value[0])) {
					return $fail('Username is invalid. Must start with a letter or number.');
				}

				if (!ctype_alnum($value[strlen($value) - 1])) {
					return $fail('Username is invalid. Must end with a letter or number.');
				}

				$val = str_replace(['_', '.', '-'], '', $value);
				if(!ctype_alnum($val)) {
					return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
				}

				$restricted = RestrictedNames::get();
				if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
					return $fail('Username cannot be used.');
				}
			},
		];

		$rules = ['username' => $usernameRules];
		$validator = Validator::make($request->all(), $rules);

		if($validator->fails()) {
			return response()->json($validator->errors(), 400);
		}

		return response()->json([]);
	}

	public function apiEmailCheck(Request $request)
	{
		$this->validate($request, [
			'token' => 'required',
			'email' => 'required'
		]);

		$invite = UserInvite::whereToken($request->input('token'))->first();
		abort_if(!$invite, 404);
		abort_if($invite->valid_until && $invite->valid_until->lt(now()), 400, 'Invite has expired.');
		abort_if($invite->used_at != null, 400, 'Invite already used.');

		$emailRules = [
			'required',
			'string',
			'email',
			'max:255',
			'unique:users',
			function ($attribute, $value, $fail) {
				$banned = EmailService::isBanned($value);
				if($banned) {
					return $fail('Email is invalid.');
				}
			},
		];

		$rules = ['email' => $emailRules];
		$validator = Validator::make($request->all(), $rules);

		if($validator->fails()) {
			return response()->json($validator->errors(), 400);
		}

		return response()->json([]);
	}

	public function apiRegister(Request $request)
	{
		$this->validate($request, [
			'token' => 'required',
			'username' => [
				'required',
				'min:2',
				'max:30',
				'unique:users',
				function ($attribute, $value, $fail) {
					$dash = substr_count($value, '-');
					$underscore = substr_count($value, '_');
					$period = substr_count($value, '.');

					if(ends_with($value, ['.php', '.js', '.css'])) {
						return $fail('Username is invalid.');
					}

					if(($dash + $underscore + $period) > 1) {
						return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
					}

					if (!ctype_alnum($value[0])) {
						return $fail('Username is invalid. Must start with a letter or number.');
					}

					if (!ctype_alnum($value[strlen($value) - 1])) {
						return $fail('Username is invalid. Must end with a letter or number.');
					}

					$val = str_replace(['_', '.', '-'], '', $value);
					if(!ctype_alnum($val)) {
						return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
					}

					$restricted = RestrictedNames::get();
					if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
						return $fail('Username cannot be used.');
					}
				},
			],
			'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
			'email' => [
				'required',
				'string',
				'email',
				'max:255',
				'unique:users',
				function ($attribute, $value, $fail) {
					$banned = EmailService::isBanned($value);
					if($banned) {
						return $fail('Email is invalid.');
					}
				},
			],
			'password' => 'required',
			'password_confirm' => 'required'
		]);

		$invite = UserInvite::whereToken($request->input('token'))->firstOrFail();
		abort_if($invite->valid_until && $invite->valid_until->lt(now()), 400, 'Invite expired');
		abort_if($invite->used_at != null, 400, 'Invite already used.');

		$invite->used_at = now();
		$invite->save();

		event(new Registered($user = User::create([
			'name'     => Purify::clean($request->input('name')) ?? $request->input('username'),
			'username' => $request->input('username'),
			'email'    => $request->input('email'),
			'password' => Hash::make($request->input('password')),
		])));

		sleep(5);

        if ($request->input('email') === $invite->email) {
		    $user->email_verified_at = now();
        }
		$user->save();

		if(Auth::attempt([
			'email' => $request->input('email'),
			'password' => $request->input('password')
		])) {
			$request->session()->regenerate();
			return redirect()->intended('/');
		} else {
			return response()->json([], 400);
		}
	}
}
