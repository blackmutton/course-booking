<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // 為了正確使用 Laravel 的授權功能，需要在 Controller 中使用 AuthorizesRequests trait。
    // 這個 trait 提供了 authorize() 方法，讓我們可以在 Controller 中方便地進行授權檢查。
    use AuthorizesRequests;
}