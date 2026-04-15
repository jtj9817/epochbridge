<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PasswordPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Settings/PasswordPage');
    }
}
