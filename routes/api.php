<?php

use App\Http\Controllers\App\ContactController;
use App\Http\Controllers\App\EventController;
use App\Http\Controllers\App\EventMemberController;
use App\Http\Controllers\App\ExpenseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\Auth\TokenValidationController;

// auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // reset password
    Route::post('/change-password', [AuthController::class, 'change_password'])->middleware('auth:sanctum');

    // verify email
    Route::get('/verify-email', [AuthController::class, 'send_verification_email'])->middleware('auth:sanctum');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify_email']);
});


// validate token route
Route::middleware('auth:sanctum')->get('/validate-token', TokenValidationController::class);


// profile routes
Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProfileController::class, 'index']);
    Route::put('/', [ProfileController::class, 'update']);

    Route::prefix('settings')->group(function () {
        Route::get('/', [ProfileController::class, 'get_settings']);
        Route::put('/', [ProfileController::class, 'update_settings']);
    });
});

// events routes
Route::controller(EventController::class)->middleware('auth:sanctum')->group(function () {

    Route::prefix('events')->group(function () {
        Route::get('/', 'index');
        Route::get('/trashed', 'trashed_events');
        Route::post('/', 'create');

        Route::put('/restore/items', 'restore_items');
        Route::delete('/trash/items', 'trash_items');
        Route::delete('/delete/items', 'delete_items');
    });

    Route::prefix('event')->group(function () {
        Route::get('/{event:slug}', 'get_event');
        Route::put('/{id}', 'update');
        Route::put('/{id}/status', 'updateStatus');
        Route::put('/{id}/restore', 'restore');
        Route::delete('/{id}', 'trash');
        Route::delete('/{id}/delete', 'delete');
    });
});

// event's expenses routes
Route::controller(ExpenseController::class)->middleware(['auth:sanctum', 'check.event.ownership'])->group(function () {

    Route::prefix('event/{event_id}/expenses')->group(function () {
        Route::get('/', 'get_expenses');
        Route::post('/', 'create_expense');
        Route::delete('/delete/items', 'delete_items');
    });

    Route::prefix('event/{event_id}/expense/{expense_id}')->group(function () {
        Route::get('/', 'get_expense');
        Route::put('/', 'update_expense');
        Route::delete('/', 'destroy_expense');
    });
});

// event's members routes
Route::controller(EventMemberController::class)->middleware(['auth:sanctum', 'check.event.ownership'])->group(function () {

    Route::prefix('event/{event_id}/members')->group(function () {
        Route::get('/', 'get_members');
        Route::post('/', 'create_member');
    });

    Route::get('/event/{event_id}/non-members', 'get_non_members');


    Route::prefix('event/{event_id}/member/{member_id}')->group(function () {
        Route::get('/', 'get_member');
        Route::put('/', 'update_member');
        Route::delete('/', 'destroy_member');
    });
});

// contact routes
Route::controller(ContactController::class)->middleware('auth:sanctum')->group(function () {

    Route::prefix('contacts')->group(function () {
        Route::get('/', 'index');
        Route::get('/trashed', 'trashed_contacts');
        Route::post('/', 'create');

        Route::put('/restore/items', 'restore_items');
        Route::delete('/trash/items', 'trash_items');
        Route::delete('/delete/items', 'delete_items');
    });

    Route::prefix('contact/{id}')->group(function () {
        Route::get('/', 'get_contact');
        Route::put('/', 'update');
        Route::put('/restore', 'restore');
        Route::delete('/', 'trash');
        Route::delete('/delete', 'delete');
    });
});
