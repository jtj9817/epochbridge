<?php

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\Frontend\SavedConversionResource;
use App\Models\SavedConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedConversionNoteController extends Controller
{
    public function update(Request $request, SavedConversion $savedConversion): JsonResponse
    {
        if ($savedConversion->user_id !== $request->user()->id) {
            abort(403, 'You do not own this conversion.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2048'],
        ]);

        $savedConversion->update([
            'note' => $validated['note'] ?? '',
        ]);

        return response()->json(
            new SavedConversionResource($savedConversion->fresh())
        );
    }
}
