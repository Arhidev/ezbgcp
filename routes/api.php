<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokenController;

use App\Http\Controllers\Api;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'docs'], function () {
  Route::get('/privacy-policy', [Api\SettingController::class, 'getPrivacy']);
  Route::get('/terms', [Api\SettingController::class, 'getTerms']);
});

Route::group(['prefix' => 'dashboard'], function () {
  Route::get('/all', [Api\DashboardController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'coupons'], function () {
    Route::get('/all', [Api\CouponController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
    Route::post('/create-edit', [Api\CouponController::class, 'createEdit'])->middleware(['auth:sanctum', 'admin']);
    Route::delete('/{coupon}', [Api\CouponController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
    // Route::get('/{id}', [Api\CouponController::class, 'getCoupon']);
    Route::post('/apply-coupon', [Api\CouponController::class, 'applyCoupon'])->middleware(['auth:sanctum']);
    //notify
    Route::post('/notify', [Api\CouponController::class, 'notify'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'users'], function () {
  Route::get('/user/{id}', [Api\UserController::class, 'getUser'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/edit', [Api\UserController::class, 'Edit'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/suspend-activate', [Api\UserController::class, 'suspendActivate'])->middleware(['auth:sanctum', 'admin']);
  Route::post('upload-avatar', [Api\UserController::class, 'upload_user_photo'])->middleware(['auth:sanctum']);
  Route::post('/update-password', [Api\UserController::class, 'changePassword'])->middleware(['auth:sanctum', 'admin']);
  //get all redemptions
  Route::get('/redemptions', [Api\UserController::class, 'getRedemptions'])->middleware(['auth:sanctum', 'admin']);
  //get all upcoming payments for drivers
  Route::get('/upcoming-payments', [Api\UserController::class, 'getUpcomingPayments'])->middleware(['auth:sanctum', 'admin']);
  //get the details of a payment
  Route::get('/payment-details', [Api\UserController::class, 'getPaymentDetails'])->middleware(['auth:sanctum', 'admin']);
  //redeem
  Route::post('/redeem', [Api\UserController::class, 'redeem'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/all', [Api\UserController::class, 'index'])->middleware(['auth:sanctum', 'admin']);

  //reservations
  Route::get('/reservations', [Api\UserController::class, 'getReservations'])->middleware(['auth:sanctum']);
  //payments
  Route::get('/wallet-charges', [Api\UserController::class, 'getWalletCharges'])->middleware(['auth:sanctum']);
  //get nonce token (Braintree)
  Route::post('/nonce', [Api\UserController::class, 'payNonce'])->middleware(['auth:sanctum']);
  //capture Razorpay payment
  Route::post('capture-razorpay-payment', [Api\UserController::class, 'captureRazorpayPayment'])->middleware(['auth:sanctum']);
  //verify transaction ID
  Route::post('verify-transaction', [Api\UserController::class, 'verifyTransaction'])->middleware(['auth:sanctum']);
  // capture Paytabs payment
  Route::post('capture-paytabs-payment', [Api\UserController::class, 'capturePaytabsPayment'])->middleware(['auth:sanctum']);

  Route::get('/devices', [Api\UserController::class, 'getDevices'])->middleware(['auth:sanctum']);

  //updateProfile
  Route::post('/update-profile', [Api\UserController::class, 'updateProfile'])->middleware(['auth:sanctum']);

  //revokeToken
  Route::delete('/revoke-token', [Api\UserController::class, 'revokeToken'])->middleware(['auth:sanctum']);

    //request-delete-customer
    Route::post('/request-delete-customer', [Api\UserController::class, 'requestDeleteAccount'])->middleware(['auth:sanctum']);

    //request-delete-driver
    Route::post('/request-delete-driver', [Api\UserController::class, 'requestDeleteAccount'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'places'], function () {
  Route::get('/favorite-places', [Api\PlaceController::class, 'getFavoritePlaces'])->middleware(['auth:sanctum']);
  Route::get('/recent-places', [Api\PlaceController::class, 'getRecentPlaces'])->middleware(['auth:sanctum']);
  Route::post('/add-edit-place', [Api\PlaceController::class, 'createEdit'])->middleware(['auth:sanctum']);

  Route::get('/saved-places', [Api\PlaceController::class, 'getSavedPlaces'])->middleware(['auth:sanctum']);
  //delete-place
  Route::delete('/delete-place', [Api\PlaceController::class, 'deletePlace'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'routes'], function () {
  Route::post('/create-edit', [Api\RouteController::class, 'createEdit'])->middleware(['auth:sanctum', 'admin']);
  Route::delete('/{route}', [Api\RouteController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/all', [Api\RouteController::class, 'index'])->middleware(['auth:sanctum']);
  Route::get('/{id}', [Api\RouteController::class, 'getRoute']);
});

Route::group(['prefix' => 'stops'], function () {
  Route::post('/create-edit', [Api\StopController::class, 'createEdit'])->middleware(['auth:sanctum', 'admin']);
  Route::delete('/{stop}', [Api\StopController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/all', [Api\StopController::class, 'index'])->middleware(['auth:sanctum']);
  Route::get('/{id}', [Api\StopController::class, 'getStop']);
});


Route::group(['prefix' => 'trips'], function () {
  Route::post('/create-edit', [Api\TripController::class, 'createEdit'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/trash-restore', [Api\TripController::class, 'trashRestore'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/suspend', [Api\TripController::class, 'suspend'])->middleware(['auth:sanctum', 'admin']);
  Route::delete('/remove-suspension/{suspension_id}', [Api\TripController::class, 'removeSuspension'])->middleware(['auth:sanctum', 'admin']);
  //assign driver to trip
  Route::post('/assign-driver', [Api\TripController::class, 'assignDriver'])->middleware(['auth:sanctum', 'admin']);

  Route::get('/all', [Api\TripController::class, 'index'])->middleware(['auth:sanctum']);
  Route::get('/period', [Api\TripController::class, 'getTripsInPeriod'])->middleware(['auth:sanctum']);
  Route::get('/suspensions', [Api\TripController::class, 'getTripSuspensions'])->middleware(['auth:sanctum']);
  Route::get('/trip/{id}', [Api\TripController::class, 'getTrip'])->middleware(['auth:sanctum']);
  //Search for trips by location
  Route::get('/search-by-guest', [Api\TripController::class, 'searchByGuest']);
  Route::get('/search-by-customer', [Api\TripController::class, 'searchByCustomer'])->middleware(['auth:sanctum']);
  //pay
  Route::post('/pay', [Api\TripController::class, 'pay'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'planned-trips'], function () {
  //all
  Route::get('/all', [Api\TripController::class, 'getPlannedTrips'])->middleware(['auth:sanctum', 'admin']);

  Route::get('/{id}', [Api\TripController::class, 'getPlannedTripDetails'])->middleware(['auth:sanctum']);
  //start or stop a planned trip
  Route::post('/start-stop', [Api\TripController::class, 'startStopPlannedTrip'])->middleware(['auth:sanctum']);

  //set last position of the trip
  Route::post('/set-last-position', [Api\TripController::class, 'setLastPosition'])->middleware(['auth:sanctum']);

  //drop-off a passenger
  Route::post('/drop-off', [Api\TripController::class, 'dropOff'])->middleware(['auth:sanctum']);

  //pick-up a passenger
  Route::post('/pick-up', [Api\TripController::class, 'pickUp'])->middleware(['auth:sanctum']);

  //notify
  Route::post('/notify', [Api\TripController::class, 'notify'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'reservations'], function () {
  Route::get('/all', [Api\ReservationController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
  //cancel
  Route::post('/cancel', [Api\ReservationController::class, 'cancel'])->middleware(['auth:sanctum', 'admin']);
  //getReservationDetails
  Route::get('/reservation/{id}', [Api\ReservationController::class, 'getReservationDetails'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'complaints'], function () {
  //all
  Route::get('/all', [Api\ComplaintController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
  //create
  Route::post('/create', [Api\ComplaintController::class, 'create'])->middleware(['auth:sanctum']);
  //take action
  Route::post('/take-action', [Api\ComplaintController::class, 'takeAction'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'settings'], function () {
  Route::get('/all', [Api\SettingController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/update', [Api\SettingController::class, 'update'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/privacy-policy', [Api\SettingController::class, 'getPrivacyPolicy'])->middleware(['auth:sanctum', 'admin']);

  Route::post('/update-privacy-policy', [Api\SettingController::class, 'updatePrivacyPolicy'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/terms', [Api\SettingController::class, 'getTerms'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/update-terms', [Api\SettingController::class, 'updateTerms'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'currencies'], function () {
  Route::get('/all', [Api\CurrencyController::class, 'index'])->middleware(['auth:sanctum', 'admin']);
});

Route::group(['prefix' => 'notifications'], function () {
  Route::get('/all', [Api\NotificationController::class, 'index'])->middleware(['auth:sanctum', 'admin']);

  //seen
    Route::get('/list-all', [Api\NotificationController::class, 'listAll'])->middleware(['auth:sanctum']);

    //markAllAsSeen
    Route::post('/mark-all-as-seen', [Api\NotificationController::class, 'markAllAsSeen'])->middleware(['auth:sanctum']);

    //mark as seen
    Route::post('/mark-as-seen', [Api\NotificationController::class, 'markAsSeen'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'drivers'], function() {
  //assign-bus
  Route::post('/assign-bus', [Api\DriverController::class, 'assignBus'])->middleware(['auth:sanctum', 'admin']);
  //un-assign bus
  Route::post('/unassign-bus', [Api\DriverController::class, 'unAssignBus'])->middleware(['auth:sanctum', 'admin']);
  //take_action_on_driver
  Route::post('/take-action', [Api\DriverController::class, 'takeAction'])->middleware(['auth:sanctum', 'admin']);
  //driver conflicts
  Route::get('/conflicts', [Api\DriverController::class, 'getDriverConflicts'])->middleware(['auth:sanctum', 'admin']);

  //save driver information
  Route::post('/save-driver-info', [Api\DriverController::class, 'saveDriverInfo'])->middleware(['auth:sanctum']);
  //get driver information
  Route::get('/get-driver-info', [Api\DriverController::class, 'getDriverInfo'])->middleware(['auth:sanctum']);
  //get driver trips
  Route::get('/get-driver-trips', [Api\DriverController::class, 'getDriverTrips'])->middleware(['auth:sanctum']);
  //wallet payments
  Route::get('/wallet-payments', [Api\DriverController::class, 'getWalletPayments'])->middleware(['auth:sanctum']);
  Route::get('/available', [Api\DriverController::class, 'getAvailableDrivers']);
  //load available-buses
  Route::get('/available-buses', [Api\DriverController::class, 'getAvailableBuses']);

  //updatePreferredPaymentMethod
  Route::post('/update-preferred-payment-method', [Api\DriverController::class, 'updatePreferredPaymentMethod'])->middleware(['auth:sanctum']);

  //get PreferredPaymentMethod
  Route::get('/get-preferred-payment-method', [Api\DriverController::class, 'getPreferredPaymentMethod'])->middleware(['auth:sanctum']);
});

Route::group(['prefix' => 'buses'], function() {
  Route::post('/create-edit', [Api\BusController::class, 'createEdit'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/unassign-driver', [Api\BusController::class, 'unassignDriver'])->middleware(['auth:sanctum', 'admin']);
  Route::delete('/{bus}', [Api\BusController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/assign-driver', [Api\BusController::class, 'assignDriver'])->middleware(['auth:sanctum', 'admin']);
  Route::get('/all', [Api\BusController::class, 'index']);
  //Route::get('/{id}', [Api\BusController::class, 'getBus']);
  Route::get('/available-drivers', [Api\BusController::class, 'getAvailableDrivers']);
});

Route::group(['prefix' => 'auth'], function () {
  Route::post('/loginViaToken', [Api\AuthController::class, 'loginViaToken']);
  //reset password
  Route::post('/reset-password', [Api\AuthController::class, 'resetPassword']);
  Route::post('/createCustomer', [Api\AuthController::class, 'createCustomer']);
  Route::post('/createDriver', [Api\AuthController::class, 'createDriver']);

  //verifyUser
  Route::post('/verify-user', [Api\AuthController::class, 'verifyUser'])->middleware(['auth:sanctum']);
});


Route::group(['prefix' => 'activation'], function () {
  Route::get('/get-activation-code', [Api\ActivationController::class, 'load'])->middleware(['auth:sanctum', 'admin']);
  Route::post('/activate', [Api\ActivationController::class, 'activate'])->middleware(['auth:sanctum', 'admin']);
});

//Todo: the following routes should be removed. Please update the frontend to use the new routes
// Route::group(['prefix' => 'notes'], function() {
//   Route::get('/terms', [Api\NotesController::class, 'getTerms']);
//   Route::get('/faq', [Api\NotesController::class, 'getFAQ'])->middleware(['auth:sanctum']);

//   Route::post('/updateFAQ', [Api\NotesController::class, 'updateFAQ'])->middleware(['auth:sanctum', 'admin']);
//   Route::post('/updateTerms', [Api\NotesController::class, 'updateTerms'])->middleware(['auth:sanctum', 'admin']);
// });

