<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Auth/ForgotPasswordPage', [
            'status' => session('status'),
        ]);
    }
}
