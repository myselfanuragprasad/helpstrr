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
use App\Http\Controllers\Api\Customer\TaskController;

use App\Http\Controllers\Api\v1\SP\SPDetailController;
use App\Http\Controllers\API\V1\CustomerProfileController;
use App\Http\Controllers\Api\Customer\ChefBookingController;
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

    // === Customer Routes ===
    // Route::prefix('customer')->middleware('auth:sanctum')->group(function () {

    //     // Task Management
    //     Route::prefix('tasks')->group(function () {
    //         Route::get('/', [TaskController::class, 'index'])->name('customer.tasks.index');
    //         Route::post('/', [TaskController::class, 'store'])->name('customer.tasks.store');
    //         Route::get('/{id}', [TaskController::class, 'show'])->name('customer.tasks.show');
    //         Route::post('/{id}/cancel', [TaskController::class, 'cancel'])->name('customer.tasks.cancel');
    //         Route::post('/{id}/rate', [TaskController::class, 'rate'])->name('customer.tasks.rate');
    //         Route::post('/pricing-preview', [TaskController::class, 'pricingPreview'])->name('customer.tasks.pricing-preview');
    //         Route::get('/statistics', [TaskController::class, 'statistics'])->name('customer.tasks.statistics');
    //     });

    //     // Chef Booking Flow
    //     Route::prefix('chef')->group(function () {
    //         Route::get('/services', [ChefBookingController::class, 'getServices'])->name('customer.chef.services');
    //         Route::get('/cuisines', [ChefBookingController::class, 'getCuisines'])->name('customer.chef.cuisines');
    //         Route::get('/dietary-preferences', [ChefBookingController::class, 'getDietaryPreferences'])->name('customer.chef.dietary-preferences');
    //         Route::get('/optional-flags', [ChefBookingController::class, 'getOptionalFlags'])->name('customer.chef.optional-flags');
    //         Route::get('/pax-time-options', [ChefBookingController::class, 'getPaxAndTimeOptions'])->name('customer.chef.pax-time-options');
    //         Route::get('/addresses', [ChefBookingController::class, 'getAddresses'])->name('customer.chef.addresses');
    //         Route::get('/complete-flow', [ChefBookingController::class, 'getCompleteFlow'])->name('customer.chef.complete-flow');
    //         Route::post('/validate-booking', [ChefBookingController::class, 'validateBooking'])->name('customer.chef.validate-booking');
    //         Route::post('/pricing-preview', [ChefBookingController::class, 'getPricingPreview'])->name('customer.chef.pricing-preview');
    //         Route::get('/booking-rules', [ChefBookingController::class, 'getBookingRules'])->name('customer.chef.booking-rules');
    //         Route::post('/check-availability', [ChefBookingController::class, 'checkAvailability'])->name('customer.chef.check-availability');
    //     });
    // });

    // === Customer Routes ===
    Route::prefix('customer')->group(function () {
        Route::post('send-otp', [CustomerProfileController::class, 'sendOtp']);
        Route::post('verify-otp', [CustomerProfileController::class, 'verifyOtp']);
        Route::post('profile', [CustomerProfileController::class, 'profileDetails']);

        Route::get('profile', [CustomerProfileController::class, 'getCustomerDetails'])->name('customer.getdetails');
    });
});

// === Mobile API Routes ===
Route::prefix('mobile')->group(function () {
    
    // === Customer Mobile APIs ===
    Route::prefix('customer')->group(function () {
        // Authentication
        Route::post('register', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'register']);
        Route::post('login', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'login']);
        Route::post('send-otp', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'sendOTP']);
        Route::post('verify-otp', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'verifyOTP']);
        Route::get('app-config', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'appConfig']);
        
        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('profile', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'profile']);
            Route::put('profile', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'updateProfile']);
            Route::post('logout', [\App\Http\Controllers\Api\Mobile\Customer\AuthController::class, 'logout']);
        });
        
        // Services
        Route::get('categories', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'getCategories']);
        Route::get('categories/{categoryId}/services', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'getServicesByCategory']);
        Route::get('services/{serviceId}', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'getServiceDetails']);
        Route::get('services/{serviceId}/providers', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'getServiceProviders']);
        Route::get('services/search', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'searchServices']);
        Route::get('services/popular', [\App\Http\Controllers\Api\Mobile\Customer\ServiceController::class, 'getPopularServices']);
    });
    
    // === Service Provider Mobile APIs ===
    Route::prefix('sp')->group(function () {
        // Authentication routes will be added here
        // Dashboard routes will be added here
        // Task management routes will be added here
    });
});
