<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Notifications\Notifiable;
use App\Notifications\BrowserNotification;
use NotificationChannels\WebPush\WebPushChannel;
use App\Http\Controllers\Api\v1\GeneralController;
use App\Http\Controllers\Api\v1\UTMDataController;
use NotificationChannels\WebPush\PushSubscription;
use App\Http\Controllers\Api\v1\ReferralController;
use App\Http\Controllers\Api\v1\SP\SPAuthController;
use App\Http\Controllers\Api\v1\UserTokenController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Api\v1\SP\SPDetailController;
use App\Http\Controllers\Api\v1\Customer\CustomerAuthController;
use App\Http\Controllers\Api\v1\Customer\CustomerDetailController;
use App\Http\Controllers\Api\v1\Customer\CustomerController;
use NotificationChannels\WebPush\PushSubscription as WebPushSubscription;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::post('/login', [AuthController::class, 'login']);
Route::prefix('v1')->group(function () {

    // === Service Provider Routes ===
    Route::prefix('sp')->group(function () {
        Route::post('signup', [SPAuthController::class, 'signup'])->name('sp.register');
        Route::post('verify-otp', [SPAuthController::class, 'verifyOtp'])->name('sp.verifyOtp');
        Route::get('profile', [SPDetailController::class, 'getSPDetails'])->name('sp.getdetails');
        Route::post('profile', [SPDetailController::class, 'saveSPDetails'])->name('sp.savedetails');
    });

    // === Customer Routes ===
    Route::prefix('customer')->group(function () {
        // Authentication routes
        Route::post('signup', [CustomerAuthController::class, 'signup'])->name('customer.register');
        
        // Profile management routes (existing)
        Route::get('profile', [CustomerDetailController::class, 'getCustomerDetails'])->name('customer.getdetails');
        Route::post('profile', [CustomerDetailController::class, 'saveCustomerDetails'])->name('customer.savedetails');
        
        // CRUD routes for customer management
        Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/{id}', [CustomerController::class, 'update'])->name('customers.patch');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        
        // Additional customer management routes
        Route::patch('/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::get('/statistics/overview', [CustomerController::class, 'statistics'])->name('customers.statistics');
        Route::post('/bulk-action', [CustomerController::class, 'bulkAction'])->name('customers.bulk-action');
    });


    // === Location Routes ===
    Route::prefix('general')->group(function () {
        Route::get('countries', [GeneralController::class, 'countryDetails'])->name('general.countries');
        Route::get('states', [GeneralController::class, 'statesDetails'])->name('general.states');
        Route::get('cities', [GeneralController::class, 'citiesDetails'])->name('general.cities');

        Route::get('states/{country_id}', [GeneralController::class, 'countryWiseStates'])->name('country.states');

        Route::get('cities/{state_id}', [GeneralController::class, 'stateWiseCities'])->name('state.cities');

        Route::get('data/{type}', [GeneralController::class, 'typeWiseData'])->name('helpstrr.data');
    });

    Route::prefix('referral')->group(function () {
        Route::post('sp/invite', [ReferralController::class, 'inviteDetailsSubmit'])->name('sp.invite');
    });

    //user token api

    Route::post('/user-token', [UserTokenController::class, 'store']);

    Route::get('job-roles', [SPDetailController::class, 'getJobRoles'])->name('sortkar.jobrole');

    Route::post('/subscribe', [PushSubscriptionController::class, 'store']);

    Route::post('/test-notify', [PushSubscriptionController::class, 'send']);

    Route::post('/utm-track', [UTMDataController::class, 'store']);
});
