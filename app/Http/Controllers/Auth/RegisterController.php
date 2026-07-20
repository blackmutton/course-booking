<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Jobs\SendVerifyEmailJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($request) {

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'email_verified_token' => Str::uuid()->toString(),
                'email_token_expiration' => now()->addMinutes(30),
            ]);

            $user->student()->create();

            return $user;
        });
        // 確定所有資料都已經寫入資料庫後，才派送驗證電子郵件工作
        SendVerifyEmailJob::dispatch($user)
            ->afterCommit()
            ->onQueue('emails');


        return response()->json([
            'message' => 'Success',
            'user' => new UserResource($user),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('email_verified_token', $request->token)->whereNull('email_verified_at')->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['Invalid verification token.'],
            ]);
        }

        if ($user->email_token_expiration->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['Verification token has expired.'],
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verified_token' => null,
            'email_token_expiration' => null,
        ])->save();

        return response()->json([
            'message' => 'Success',
            'user' => new UserResource($user),
        ]);
    }
}
