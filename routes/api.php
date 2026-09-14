<?php

use App\Http\Controllers\Api\EnumController;
use Illuminate\Support\Facades\Route;

Route::prefix('enums')->group(function () {
    Route::get('payment-methods', [EnumController::class, 'paymentMethods']);
    Route::get('payment-statuses', [EnumController::class, 'paymentStatuses']);
    Route::get('member-statuses', [EnumController::class, 'memberStatus']);
    Route::get(
        'membership-plan-statuses',
        [EnumController::class, 'membershipPlanStatus']
    );
});
