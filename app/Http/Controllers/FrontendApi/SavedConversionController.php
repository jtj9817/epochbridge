<?php

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\SavedConversionResource;
use App\Models\SavedConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavedConversionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $conversions = SavedConversion::forUser($request->user()->id)
            ->withConversionData()
            ->paginate(20);

        return SavedConversionResource::collection($conversions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'original_input' => ['required', 'string', 'max:255'],
            'conversion_data' => ['required', 'array'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $conversion = SavedConversion::create([
            'user_id' => $request->user()->id,
            'original_input' => $validated['original_input'],
            'conversion_data' => $validated['conversion_data'],
            'note' => $validated['note'] ?? null,
            'gregorian_date' => data_get($validated['conversion_data'], 'gregorian.full_date'),
            'jalali_date' => data_get($validated['conversion_data'], 'jalali.full_date'),
            'unix_timestamp' => data_get($validated['conversion_data'], 'unix.value')
                ?? data_get($validated['conversion_data'], 'unix'),
            'iso8601_string' => data_get($validated['conversion_data'], 'iso8601.value')
                ?? data_get($validated['conversion_data'], 'iso8601'),
        ]);

        return response()->json(
            new SavedConversionResource($conversion),
            201
        );
    }

    public function destroy(Request $request, SavedConversion $savedConversion): JsonResponse
    {
        if ($savedConversion->user_id !== $request->user()->id) {
            abort(403, 'You do not own this conversion.');
        }

        $savedConversion->delete();

        return response()->json(['success' => true]);
    }
}
