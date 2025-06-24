<?php

use App\Models\FileAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FileAccessHelper
{
    public static function generateFileUrl(string $path)
    {
        $user = Auth::user();
        if (!$user) {
            return response('Unauthorized', 401);
        }

        $token = ""

        $tokenRecord = FileAccessToken::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($tokenRecord) {
            $token = $tokenRecord->token;
        } else {
            $token = Str::random(60);
            $expiresAt = now()->addMinutes(1440);
            FileAccessToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);
        }

        return url("/storage/{$path}?token={$token}");
    }
}
