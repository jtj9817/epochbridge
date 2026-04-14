<?php

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ConvertDateRequest;
use App\Http\Resources\Frontend\ConversionResultResource;
use App\Services\DateTimeConverterService;
use Illuminate\Http\JsonResponse;

class ConversionController extends Controller
{
    public function __construct(
        private DateTimeConverterService $converter
    ) {}

    public function store(ConvertDateRequest $request): JsonResponse
    {
        $date = $request->validated('date');
        $inputType = $request->validated('input_type');

        try {
            $results = $this->converter->getFullConversionResults($date, $inputType);

            return response()->json(
                new ConversionResultResource($results, $date, $inputType)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'An error occurred during conversion.',
            ], 500);
        }
    }
}
