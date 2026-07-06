<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientNotificationCounts;
use App\Services\Client\ClientPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    /**
     * Lightweight payload for the app shell (sidebar/header): identity +
     * unread notification badge. Cheap enough to call on every screen load.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success'              => true,
            'user'                 => ClientPayloadBuilder::user($user),
            'customer'             => ClientPayloadBuilder::customer($user->customer),
            'unread_notifications' => ClientNotificationCounts::unread()['unread_notifications'],
        ]);
    }
}
