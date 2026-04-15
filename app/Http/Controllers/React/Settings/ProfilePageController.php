<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfilePageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/ProfilePage', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'has_verified_email' => ! is_null($user->email_verified_at),
            ],
            'status' => session('status'),
        ]);
    }
}
