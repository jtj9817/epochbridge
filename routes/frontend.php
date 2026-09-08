<?php

use App\Http\Controllers\FrontendApi\ConversionController;
use App\Http\Controllers\FrontendApi\OnThisDayController;
use App\Http\Controllers\FrontendApi\QuickActionController;
use App\Http\Controllers\FrontendApi\SavedConversionController;
use App\Http\Controllers\FrontendApi\SavedConversionNoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend API Routes
|--------------------------------------------------------------------------
|
| These routes provide JSON endpoints for the React frontend to consume.
| They serve the React frontend and use the web
| middleware stack for session/CSRF handling.
|
*/

Route::prefix('frontend')->group(function () {
    // Public endpoints
    Route::post('conversions', [ConversionController::class, 'store'])
        ->middleware('throttle:frontend-public')
        ->name('frontend.conversions.store');
    Route::get('on-this-day', [OnThisDayController::class, 'show'])
        ->middleware('throttle:frontend-public')
        ->name('frontend.on-this-day.show');
    Route::get('quick-action', [QuickActionController::class, 'show'])
        ->middleware('throttle:frontend-public')
        ->name('frontend.quick-action.show');

    // Authenticated endpoints
    Route::middleware('auth')->group(function () {
        Route::get('saved-conversions', [SavedConversionController::class, 'index'])->name('frontend.saved-conversions.index');
        Route::post('saved-conversions', [SavedConversionController::class, 'store'])->name('frontend.saved-conversions.store');
        Route::post('saved-conversions/migrate', [SavedConversionController::class, 'migrate'])->name('frontend.saved-conversions.migrate');
        Route::patch('saved-conversions/{savedConversion}/note', [SavedConversionNoteController::class, 'update'])->name('frontend.saved-conversions.note.update');
        Route::delete('saved-conversions/{savedConversion}', [SavedConversionController::class, 'destroy'])->name('frontend.saved-conversions.destroy');
    });
});
