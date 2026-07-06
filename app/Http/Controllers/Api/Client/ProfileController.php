<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * The authenticated client's account (login identity) and linked
     * customer (CRM profile) — same shape returned by verify-otp.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success'  => true,
            'user'     => ClientPayloadBuilder::user($user),
            'customer' => ClientPayloadBuilder::customer($user->customer),
        ]);
    }
}
