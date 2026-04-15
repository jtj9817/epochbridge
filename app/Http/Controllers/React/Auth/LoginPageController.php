<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Auth/LoginPage', [
            'canResetPassword' => app('router')->getRoutes()->hasNamedRoute('password.request'),
            'canRegister' => app('router')->getRoutes()->hasNamedRoute('register'),
            'status' => session('status'),
            'oauthError' => $request->session()->get('errors')?->first('oauth'),
        ]);
    }
}
