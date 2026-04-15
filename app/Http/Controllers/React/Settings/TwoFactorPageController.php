<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TwoFactorPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(Features::canManageTwoFactorAuthentication(), SymfonyResponse::HTTP_FORBIDDEN);

        $user = $request->user();

        if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
            app(DisableTwoFactorAuthentication::class)($user);
        }

        return Inertia::render('Settings/TwoFactorPage', [
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
