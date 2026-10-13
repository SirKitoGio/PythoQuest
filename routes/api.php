<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\IdentificationController;
use App\Http\Controllers\Api\V1\MapController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PlantController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SightingConfirmationController;
use App\Http\Controllers\Api\V1\SightingController;
use App\Http\Controllers\Api\V1\VocabularyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:api-token')->name('auth.token.store');

    // Public endpoints; an optional bearer token or session personalises the response.
    Route::middleware('auth.optional')->group(function () {
        Route::get('/plants', [PlantController::class, 'index'])->name('plants.index');
        Route::get('/plants/{plant}', [PlantController::class, 'show'])->name('plants.show');
        Route::get('/map/sightings', MapController::class)->name('map.sightings');
        Route::get('/vocabulary', VocabularyController::class)->name('vocabulary');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthTokenController::class, 'show'])->name('me');
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::apiResource('sightings', SightingController::class);
        Route::post('/sightings/{sighting}/confirmation', [SightingConfirmationController::class, 'store'])->name('sightings.confirmation.store');
        Route::delete('/sightings/{sighting}/confirmation', [SightingConfirmationController::class, 'destroy'])->name('sightings.confirmation.destroy');

        Route::get('/identifications', [IdentificationController::class, 'index'])->name('identifications.index');
        Route::post('/identifications', [IdentificationController::class, 'store'])->middleware('throttle:identifications')->name('identifications.store');
        Route::get('/identifications/{identification}', [IdentificationController::class, 'show'])->name('identifications.show');
        Route::post('/identifications/{identification}/escalation', [IdentificationController::class, 'escalate'])->name('identifications.escalation.store');

        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::put('/favorites/{plant}', [FavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('/favorites/{plant}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications', [NotificationController::class, 'update'])->name('notifications.update');

        Route::middleware('can:review-records')->group(function () {
            Route::get('/review/sightings', [ReviewController::class, 'index'])->name('review.sightings.index');
            Route::post('/review/sightings/{sighting}', [ReviewController::class, 'store'])->name('review.sightings.store');
        });
    });
});
