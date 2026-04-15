<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordPageController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPasswordPage', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
            'status' => session('status'),
        ]);
    }
}
