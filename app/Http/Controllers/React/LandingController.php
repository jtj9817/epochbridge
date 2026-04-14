<?php

declare(strict_types=1);

namespace App\Http\Controllers\React;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('LandingPage', [
            'todayDate' => Carbon::now()->format('Y-m-d'),
        ]);
    }
}
