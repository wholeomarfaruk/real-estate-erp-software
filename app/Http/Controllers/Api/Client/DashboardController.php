<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientDashboardService;
use App\Services\Client\ClientPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ClientDashboardService $dashboard)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(array_merge([
            'success'  => true,
            'user'     => ClientPayloadBuilder::user($user),
            'customer' => ClientPayloadBuilder::customer($user->customer),
        ], $this->dashboard->build($user->customer)));
    }
}
