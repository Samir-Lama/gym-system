<?php

use App\Http\Controllers\Api\EnumController;
use Illuminate\Support\Facades\Route;

Route::prefix('enums')->group(function () {
    Route::get('member-statuses', [EnumController::class, 'memberStatus']);
});