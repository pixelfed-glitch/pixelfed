<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppRegister;
use App\Mail\InAppRegisterEmailVerify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AppRegisterController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('auth.iar') == true, 404);
        // $open = (bool) config_cache('pixelfed.open_registration');
        // if(!$open || $request->user()) {
        if($request->user()) {
            return redirect('/');
        }
        return view('auth.iar');
    }

    public function store(Request $request)
    {
        abort_unless(config('auth.iar') == true, 404);

        $rules = [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|unique:app_registers,email',
        ];

        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
        }

        $this->validate($request, $rules);

        $email = $request->input('email');
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $exists = AppRegister::whereEmail($email)->where('created_at', '>', now()->subHours(24))->count();

        if($exists && $exists > 3) {
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => 'Too many attempts, please try again later.'
            ]);
            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        DB::beginTransaction();

        $registration = AppRegister::create([
            'email' => $email,
            'verify_code' => $code,
            'email_delivered_at' => now()
        ]);

        try {
            Mail::to($email)->send(new InAppRegisterEmailVerify($code));
        } catch (\Exception $e) {
            DB::rollBack();
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => 'Failed to send verification code'
            ]);
            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        DB::commit();

        $queryParams = http_build_query([
            'email' => $request->email,
            'expires_in' => 3600,
            'status' => 'success'
        ]);

        return redirect()->away("pixelfed://verifyEmail?{$queryParams}");
    }

    public function verifyCode(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email',
            'verify_code' => ['required', 'digits:6', 'numeric']
        ]);

        $email = $request->input('email');
        $code = $request->input('verify_code');

        $exists = AppRegister::whereEmail($email)
            ->whereVerifyCode($code)
            ->where('created_at', '>', now()->subMinutes(60))
            ->exists();

        return response()->json([
            'status' => $exists ? 'success' : 'error',
        ]);
    }
}
