<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RegisterPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Auth/RegisterPage');
    }
}
