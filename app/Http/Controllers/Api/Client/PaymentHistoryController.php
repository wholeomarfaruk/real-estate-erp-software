<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\PaymentSchedule;
use App\Services\Client\ClientPaymentHistoryPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentHistoryController extends Controller
{
    /**
     * "Payment History" — every schedule the authenticated customer has made
     * at least one payment against (partial or fully paid), across all of
     * their properties, each with its own transaction log.
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->customer->id;

        $schedules = PaymentSchedule::whereIn('status', ['partial', 'paid'])
            ->whereHas('propertySale', fn ($query) => $query->where('customer_id', $customerId))
            ->with('propertySale.property')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ClientPaymentHistoryPayloadBuilder::build($schedules),
        ]);
    }
}
