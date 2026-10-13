<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\IdentificationController;
use App\Http\Controllers\IdentificationEscalationController;
use App\Http\Controllers\IdentificationReviewController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SightingConfirmationController;
use App\Http\Controllers\SightingController;
use App\Http\Controllers\SightingReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/map', [MapController::class, 'index'])->name('map');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');

    Route::resource('plants', PlantController::class)->except(['index', 'show']);
    Route::post('/plants/{plant}/favorite', [FavoriteController::class, 'store'])->name('plants.favorite.store');
    Route::delete('/plants/{plant}/favorite', [FavoriteController::class, 'destroy'])->name('plants.favorite.destroy');

    Route::resource('sightings', SightingController::class);
    Route::post('/sightings/{sighting}/confirmation', [SightingConfirmationController::class, 'store'])->name('sightings.confirmation.store');
    Route::delete('/sightings/{sighting}/confirmation', [SightingConfirmationController::class, 'destroy'])->name('sightings.confirmation.destroy');
    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');

    Route::resource('identifications', IdentificationController::class)->only(['index', 'create', 'show']);
    Route::post('/identifications', [IdentificationController::class, 'store'])->middleware('throttle:identifications')->name('identifications.store');
    Route::post('/identifications/{identification}/escalation', [IdentificationEscalationController::class, 'store'])->name('identifications.escalation.store');

    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::delete('/history/searches', [HistoryController::class, 'destroy'])->name('history.searches.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications', [NotificationController::class, 'update'])->name('notifications.update');

    Route::middleware('can:review-records')->group(function () {
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::post('/review/sightings/{sighting}', [SightingReviewController::class, 'store'])->name('review.sightings.store');
        Route::post('/review/identifications/{identification}', [IdentificationReviewController::class, 'store'])->name('review.identifications.store');
    });

    Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::resource('plants', PlantController::class)->only(['index', 'show']);

require __DIR__.'/auth.php';
