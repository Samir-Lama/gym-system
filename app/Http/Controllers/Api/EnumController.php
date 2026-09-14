<?php

namespace App\Http\Controllers\Api;

use App\Enums\MembershipPlanStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class EnumController extends Controller
{
    public function paymentMethods(): JsonResponse
    {
        return response()->json(PaymentMethod::options());
    }

    public function paymentStatuses(): JsonResponse
    {
        return response()->json(PaymentStatus::options());
    }

    public function memberStatus(): JsonResponse
    {
        return response()->json(
            MemberStatus::options()
        );
    }

    public function membershipPlanStatus(): array
    {
        return MembershipPlanStatus::options();
    }
}
