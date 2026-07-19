<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        $credentials = $request->only('email', 'password');

        // 嘗試登入
        // 第一個參數：登入憑證（email、password）
        // 第二個參數：是否記住登入（Remember Me）
        //
        // Auth::attempt() 會：
        // 1. 根據 email 找使用者
        // 2. 使用 Hash::check() 驗證密碼
        // 3. 成功後將使用者資訊存入 Session
        // 4. 若 remember=true，額外建立 Remember Me Cookie

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw  ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // 登入成功後重新產生 Session ID
        //
        // 目的：
        // 避免 Session Fixation Attack（Session 固定攻擊）
        // 使用者登入前後會換一組新的 Session ID，
        // 即使舊 Session 被竊取也無法繼續使用。

        $request->session()->regenerate();

        $user = Auth::user();

        return response()->json([
            'message' => 'Success',
            'user' => new UserResource(Auth::user())

        ]);
    }

    public function logout(Request $request)
    {
        // 登出使用者
        Auth::logout();

        // 清除 Session 資料
        $request->session()->invalidate();

        // 重新產生 CSRF Token
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Success'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource(Auth::user())

        ]);
    }
}
