<?php

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\MigrateGuestConversionsRequest;
use App\Http\Resources\Frontend\SavedConversionResource;
use App\Models\SavedConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function migrate(MigrateGuestConversionsRequest $request): JsonResponse
    {
        $items = $request->validated('items');
        $user = $request->user();

        DB::beginTransaction();

        try {
            $migrated = 0;

            foreach ($items as $item) {
                $originalInput = $item['original_input'];

                $exists = SavedConversion::where('user_id', $user->id)
                    ->where('original_input', $originalInput)
                    ->exists();

                if ($exists) {
                    continue;
                }

                SavedConversion::create([
                    'user_id' => $user->id,
                    'original_input' => $originalInput,
                    'conversion_data' => $item['conversion_data'],
                    'note' => $item['note'] ?? '',
                    'gregorian_date' => data_get($item['conversion_data'], 'gregorian.full_date'),
                    'jalali_date' => data_get($item['conversion_data'], 'jalali.full_date'),
                    'unix_timestamp' => data_get($item['conversion_data'], 'unix.value')
                        ?? data_get($item['conversion_data'], 'unix'),
                    'iso8601_string' => data_get($item['conversion_data'], 'iso8601.value')
                        ?? data_get($item['conversion_data'], 'iso8601'),
                    'created_at' => $item['created_at'] ?? now(),
                ]);

                $migrated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'migrated_count' => $migrated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Guest conversion migration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to migrate guest conversions.',
            ], 500);
        }
    }
}
