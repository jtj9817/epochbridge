<?php

declare(strict_types=1);

namespace App\Http\Controllers\React\Dashboard;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('DashboardPage');
    }
}
