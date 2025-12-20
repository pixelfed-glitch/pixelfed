<?php

namespace App\Http\Controllers\Auth;

use App\AccountLog;
use App\Http\Controllers\Controller;
use App\Services\BouncerService;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/i/web';

    protected $maxAttempts = 5;

    protected $decayMinutes = 60;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp(request()->ip()), 404);
        }

        return view('auth.login');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function validateLogin($request)
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $rules = [
            $this->username() => 'required|email',
            'password' => 'required|string|min:6',
        ];
        $messages = [];

        if (
            (bool) config_cache('captcha.enabled') &&
            (bool) config_cache('captcha.active.login') ||
            (
                (bool) config_cache('captcha.triggers.login.enabled') &&
                request()->session()->has('login_attempts') &&
                request()->session()->get('login_attempts') >= config('captcha.triggers.login.attempts')
            )
        ) {
            $rules['h-captcha-response'] = 'required|filled|captcha|min:5';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $request->validate($rules, $messages);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated($request, $user)
    {
        if ($user->status == 'deleted') {
            return;
        }

        if ($user->email_verified_at == null) {
            $request->session()->invalidate();
            $request->session()->regenerate();
            $request->session()->put('login_session_id', $user->id);
            $request->session()->put('login_error', 'email_not_verified');

            return redirect('/login')->withErrors('Unverified email, please verify your email before proceeding.');
        }

        if ($user->{'2fa_enabled'}) {
            $request->session()->invalidate();
            $request->session()->regenerate();
            $request->session()->put('login_session_id', $user->id);
            $request->session()->put('login_2fa', now());

            return view('auth.two-factor');
        }

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = 'App\User';
        $log->action = 'auth.login';
        $log->message = 'Account Login';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();
    }

    /**
     * Get the failed login response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        if (config('captcha.triggers.login.enabled')) {
            if ($request->session()->has('login_attempts')) {
                $ct = $request->session()->get('login_attempts');
                $request->session()->put('login_attempts', $ct + 1);
            } else {
                $request->session()->put('login_attempts', 1);
            }
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();

            // Check for 2FA and email verification
            if ($user->two_factor_enabled && ! $user->two_factor_verified) {
                // Store necessary data in session
                session(['login_credentials' => $request->only('email', 'password')]);
                session(['two_factor_required' => true]);

                // Redirect to 2FA verification
                return redirect()->route('two_factor.verify');
            }

            if (! $user->email_verified_at) {
                $request->session()->invalidate();
                session(['login_uid' => $user->id]);
                session(['login_credentials' => $request->only('email', 'password')]);
                session(['verify_email' => true]);

                return redirect()->route('custom-auth:verify-email');
            }

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
