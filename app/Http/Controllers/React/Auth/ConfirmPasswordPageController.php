<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmPasswordPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Auth/ConfirmPasswordPage');
    }
}
