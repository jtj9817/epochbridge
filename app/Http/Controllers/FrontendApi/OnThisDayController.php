<?php

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Services\Wikimedia\OnThisDayCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnThisDayController extends Controller
{
    public function __construct(
        private OnThisDayCacheService $cacheService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'language' => ['sometimes', 'string', 'max:5'],
            'type' => ['sometimes', 'string', 'in:all,events,births,deaths,holidays'],
        ]);

        $date = $request->input('date');
        $language = $request->input('language', 'en');
        $type = $request->input('type', 'all');

        $result = $this->cacheService->getForDate($date, $language, $type);

        return response()->json([
            'date' => $date,
            'data' => $result['data'],
            'cached' => $result['cached'],
            'error' => $result['error'],
        ]);
    }
}
