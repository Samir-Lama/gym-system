<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\MemberAccessCredentialController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberMembershipController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\MobileCheckInController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:Admin|Staff'])->group(function () {
    Route::get('/check-ins', [CheckInController::class, 'index'])
        ->name('checkins.index');
    Route::post('/check-ins', [CheckInController::class, 'store'])
        ->name('checkins.store');
    Route::post('/check-ins/credential', [CheckInController::class, 'credentialCheckIn'])
        ->name('checkins.credential');
    Route::get('/check-ins/member-search', [CheckInController::class, 'searchMembers'])
        ->name('checkins.member-search');
    Route::patch('/check-ins/{checkIn}/checkout', [CheckInController::class, 'checkout'])
        ->name('checkins.checkout');
    Route::get('/check-ins/display', [MobileCheckInController::class, 'display'])
        ->name('checkins.display')
        ->middleware('cache.headers:no_store');
    Route::get('/check-ins/challenge', [MobileCheckInController::class, 'challenge'])
        ->name('checkins.challenge')
        ->middleware('cache.headers:no_store');

    Route::post('/members/{member}/qr-credential', [MemberAccessCredentialController::class, 'issueQr'])
        ->name('members.qr.issue')
        ->block();
    Route::get('/members/{member}/qr-credential', [MemberAccessCredentialController::class, 'showQr'])
        ->name('members.qr.show')
        ->middleware('cache.headers:no_store')
        ->block();
    Route::post('/members/{member}/rfid-credential', [MemberAccessCredentialController::class, 'registerRfid'])
        ->name('members.rfid.register')
        ->block();
    Route::patch('/members/{member}/account', [MemberController::class, 'linkAccount'])
        ->name('members.account.link');

    Route::post('/members/bulk-status', [MemberController::class, 'bulkStatus'])
        ->name('members.bulk-status');
    Route::resource('members', MemberController::class)->except('destroy');

    Route::resource('membership-plans', MembershipPlanController::class)
        ->only(['index', 'show']);

    Route::post(
        '/member-memberships',
        [MemberMembershipController::class, 'store']
    )->name('member-memberships.store');

    Route::patch(
        '/member-memberships/{memberMembership}/pause',
        [MemberMembershipController::class, 'pause']
    )->name('member-memberships.pause');

    Route::patch(
        '/member-memberships/{memberMembership}/cancel',
        [MemberMembershipController::class, 'cancel']
    )->name('member-memberships.cancel');

    Route::post(
        '/member-memberships/{memberMembership}/renew',
        [MemberMembershipController::class, 'renew']
    )->name('member-memberships.renew');

    Route::patch(
        '/member-memberships/{memberMembership}/resume',
        [MemberMembershipController::class, 'resume']
    )->name('member-memberships.resume');

    Route::get('/billing', [PaymentController::class, 'index'])
        ->name('billing.index');

    Route::get('/api/members/search', [PaymentController::class, 'searchMembers'])
        ->name('members.search');

    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->name('payments.show');

    Route::post('/payments', [PaymentController::class, 'store'])
        ->name('payments.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/check-in/mobile/success', [MobileCheckInController::class, 'success'])
        ->name('mobile-checkin.success');
    Route::get('/check-in/mobile/{token}', [MobileCheckInController::class, 'show'])
        ->name('mobile-checkin.show')
        ->middleware('cache.headers:no_store');
    Route::post('/check-in/mobile', [MobileCheckInController::class, 'store'])
        ->name('mobile-checkin.store');
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::delete('/members/bulk-delete', [MemberController::class, 'bulkDelete'])
        ->name('members.bulk-delete');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])
        ->name('members.destroy');

    Route::resource('membership-plans', MembershipPlanController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
