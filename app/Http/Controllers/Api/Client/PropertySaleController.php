<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\PropertySale;
use App\Models\PropertySaleUnit;
use App\Services\Client\ClientPropertySalePayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertySaleController extends Controller
{
    /**
     * "My properties" — every property unit the authenticated customer has
     * purchased or rented (one row per sale invoice).
     */
    public function index(Request $request): JsonResponse
    {
        $sales = $this->customerSales($request)
            ->orderByDesc('sale_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $sales->map(fn (PropertySale $sale) => ClientPropertySalePayloadBuilder::listItem($sale))->all(),
        ]);
    }

    /**
     * "My properties" unit-wise — one row per purchased/rented unit (a sale
     * invoice can cover multiple units), each with its own pricing details.
     */
    public function unitsIndex(Request $request): JsonResponse
    {
        $sales = $this->customerSales($request)
            ->orderByDesc('sale_date')
            ->get();

        $units = $sales->flatMap(
            fn (PropertySale $sale) => $sale->saleUnits->map(
                fn (PropertySaleUnit $saleUnit) => ClientPropertySalePayloadBuilder::unitWiseItem($sale, $saleUnit)
            )
        )->values();

        return response()->json([
            'success' => true,
            'data'    => $units->all(),
        ]);
    }

    public function show(Request $request, int $sale): JsonResponse
    {
        $sale = $this->findOwnedSale($request, $sale);

        return response()->json([
            'success' => true,
            'data'    => ClientPropertySalePayloadBuilder::details($sale),
        ]);
    }

    /**
     * Single purchased/rented unit — overview, gallery, and documents
     * (the unit-wise counterpart to `show()`, keyed by sale + sale-unit row).
     */
    public function unitShow(Request $request, int $sale, int $saleUnit): JsonResponse
    {
        $saleUnit = $this->findOwnedSaleUnit($request, $sale, $saleUnit);

        return response()->json([
            'success' => true,
            'data'    => ClientPropertySalePayloadBuilder::unitDetails($saleUnit),
        ]);
    }

    public function documents(Request $request, int $sale): JsonResponse
    {
        $sale = $this->findOwnedSale($request, $sale, ['property']);

        return response()->json([
            'success' => true,
            'data'    => ClientPropertySalePayloadBuilder::documents($sale),
        ]);
    }

    public function paymentSchedules(Request $request, int $sale): JsonResponse
    {
        $sale = $this->findOwnedSale($request, $sale, ['paymentSchedules']);

        return response()->json([
            'success' => true,
            'data'    => ClientPropertySalePayloadBuilder::paymentSchedules($sale),
        ]);
    }

    /** Base query scoped to the authenticated customer's own sales. */
    private function customerSales(Request $request)
    {
        return $request->user()->customer
            ->propertySales()
            ->with(['property', 'propertyUnit.floor', 'saleUnits.property', 'saleUnits.propertyUnit.floor', 'paymentSchedules']);
    }

    /** Fetch a single sale, 404-ing if it doesn't belong to this customer. */
    private function findOwnedSale(Request $request, int $saleId, array $with = []): PropertySale
    {
        return $request->user()->customer
            ->propertySales()
            ->with(array_merge(['property', 'propertyUnit.floor', 'saleUnits.propertyUnit.floor'], $with))
            ->findOrFail($saleId);
    }

    /** Fetch a single sale unit scoped to its parent sale, 404-ing if either doesn't belong to this customer. */
    private function findOwnedSaleUnit(Request $request, int $saleId, int $saleUnitId): PropertySaleUnit
    {
        $customerId = $request->user()->customer->id;

        return PropertySaleUnit::where('property_sale_id', $saleId)
            ->whereHas('propertySale', fn ($query) => $query->where('customer_id', $customerId))
            ->with(['propertySale.paymentSchedules', 'property', 'propertyUnit.floor'])
            ->findOrFail($saleUnitId);
    }
}
