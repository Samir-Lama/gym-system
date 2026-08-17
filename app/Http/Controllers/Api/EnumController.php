<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class EnumController extends Controller
{
    public function memberStatus(): JsonResponse
    {
        return response()->json(
            MemberStatus::options()
        );
    }
}
