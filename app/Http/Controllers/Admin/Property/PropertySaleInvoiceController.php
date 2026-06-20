<?php

namespace App\Http\Controllers\Admin\Property;

use App\Http\Controllers\Controller;
use App\Models\PropertySale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PropertySaleInvoiceController extends Controller
{
    /**
     * Invoice for a property sale (supports multiple units).
     *
     *   ?download=1  → stream a generated PDF file (download)
     *   default      → render the HTML invoice in the browser (view, with a
     *                  "Download PDF" button)
     */
    public function show(Request $request, PropertySale $sale)
    {
        abort_unless($request->user()?->can('property_sale.view'), 403);

        $sale->load([
            'customer',
            'property',
            'propertyUnit.property',
            'saleUnits.propertyUnit.property',
            'saleUnits.propertyUnit.floor',
            'paymentSchedules',
        ]);

        $data = $this->buildData($sale);

        if ($request->boolean('download')) {
            // dompdf rendering is memory-heavy; give it headroom for long schedules.
            @ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('pdf.property-sale.invoice', $data + ['pdfMode' => true])
                ->setPaper('a4', 'portrait');

            return $pdf->download($sale->sale_number . '.pdf');
        }

        return view('pdf.property-sale.invoice', $data + ['pdfMode' => false]);
    }

    /**
     * Detailed payment schedule for a property sale, as its own document.
     *
     *   ?download=1  → stream a generated PDF file
     *   default      → render the HTML schedule in the browser
     */
    public function schedule(Request $request, PropertySale $sale)
    {
        abort_unless($request->user()?->can('property_sale.view'), 403);

        $sale->load([
            'customer',
            'property',
            'propertyUnit.property',
            'saleUnits.propertyUnit.property',
            'saleUnits.propertyUnit.floor',
            'paymentSchedules',
        ]);

        $data = $this->buildData($sale);

        if ($request->boolean('download')) {
            @ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('pdf.property-sale.schedule', $data + ['pdfMode' => true])
                ->setPaper('a4', 'portrait');

            return $pdf->download($sale->sale_number . '-schedule.pdf');
        }

        return view('pdf.property-sale.schedule', $data + ['pdfMode' => false]);
    }

    /**
     * Shape the sale into the arrays the invoice template renders.
     *
     * @return array<string, mixed>
     */
    protected function buildData(PropertySale $sale): array
    {
        // Per-unit rows — fall back to the primary unit for legacy single-unit sales.
        $units = $sale->saleUnits;
        if ($units->isEmpty() && $sale->propertyUnit) {
            $units = collect([(object) [
                'propertyUnit'    => $sale->propertyUnit,
                'sale_amount'     => $sale->sale_amount,
                'discount_amount' => $sale->discount_amount,
                'tax_amount'      => $sale->tax_amount,
                'net_amount'      => $sale->net_amount,
                'service_charge'  => $sale->propertyUnit->service_charge ?? 0,
                'utility_charge'  => $sale->propertyUnit->utility_charge ?? 0,
            ]]);
        }

        $unitRows = $units->map(function ($u) {
            $pu = $u->propertyUnit;

            return [
                'code'     => $pu?->effective_code ?? '—',
                'property' => $pu?->property?->name ?? '—',
                'type'     => $pu?->effective_type ? ucfirst($pu->effective_type) : '—',
                'floor'    => $pu?->floor?->label,
                'area'     => $pu?->effective_area ? number_format((float) $pu->effective_area, 0) . ' sft' : null,
                'sale'     => (float) $u->sale_amount,
                'discount' => (float) $u->discount_amount,
                'tax'      => (float) $u->tax_amount,
                'service'  => (float) $u->service_charge,
                'utility'  => (float) ($u->utility_charge ?? 0),
                'net'      => (float) $u->net_amount,
            ];
        })->all();

        $serviceTotal = (float) $units->sum('service_charge');
        $utilityTotal = (float) $units->sum('utility_charge');
        $finalAmount  = (float) $sale->net_amount + $serviceTotal + $utilityTotal;

        $schedules = $sale->paymentSchedules->map(fn ($s) => [
            'label'    => $s->label(),
            'due_date' => $s->due_date?->format('d M Y') ?? '—',
            'amount'   => (float) $s->amount,
            'paid'     => (float) $s->paid_amount,
            'due'      => (float) $s->due_amount,
            'status'   => $s->status === 'pending' && $s->due_date?->isPast() ? 'overdue' : $s->status,
        ])->all();

        return [
            'sale' => [
                'number'        => $sale->sale_number,
                'type'          => $sale->sale_type,
                'type_label'    => $sale->sale_type === 'rent' ? 'Rent Agreement' : 'Sale',
                'sale_date'     => $sale->sale_date?->format('d M Y'),
                'contract_date' => $sale->contract_date?->format('d M Y'),
                'created'       => $sale->created_at?->format('d M Y'),
                'payment_status'=> ucfirst($sale->payment_status),
                'status'        => ucwords(str_replace('_', ' ', $sale->status)),
                // combined financials
                'sale_amount'   => (float) $sale->sale_amount,
                'discount'      => (float) $sale->discount_amount,
                'tax'           => (float) $sale->tax_amount,
                'net'           => (float) $sale->net_amount,
                'service'       => $serviceTotal,
                'utility'       => $utilityTotal,
                'final'         => $finalAmount,
                'down_payment'  => (float) $sale->down_payment_amount,
                'down_pct'      => $sale->down_payment_percentage ? (float) $sale->down_payment_percentage : null,
                'final_words'   => $this->amountInWords($finalAmount),
            ],
            'units'      => $unitRows,
            'unit_count' => count($unitRows),
            'schedules'  => $schedules,
            'sched_totals' => [
                'scheduled' => array_sum(array_column($schedules, 'amount')),
                'paid'      => array_sum(array_column($schedules, 'paid')),
                'due'       => array_sum(array_column($schedules, 'due')),
            ],
            'customer' => [
                'name'    => $sale->customer?->name ?? '—',
                'id'      => $sale->customer?->customer_id,
                'phone'   => $sale->customer?->phone,
                'email'   => $sale->customer?->email,
                'address' => $sale->customer?->address,
            ],
            'company' => [
                'name'         => config('company.name', 'Star Unity Development Ltd.'),
                'tag'          => config('company.tagline'),
                'address'      => config('company.address'),
                'phone'        => config('company.phone'),
                'email'        => config('company.email'),
                'website'      => config('company.website'),
                'logo'         => config('company.logo'),
                'logo_initial' => config('company.logo_initial', 'SU'),
            ],
            'generated_at'  => now()->format('d M Y, H:i'),
            // Invoice URLs
            'download_url'  => route('admin.properties.sales.invoice', ['sale' => $sale->id, 'download' => 1]),
            'invoice_url'   => route('admin.properties.sales.invoice', $sale->id),
            // Payment-schedule document URLs (separate template)
            'schedule_url'          => route('admin.properties.sales.schedule', $sale->id),
            'schedule_download_url' => route('admin.properties.sales.schedule', ['sale' => $sale->id, 'download' => 1]),
        ];
    }

    protected function amountInWords(float $amount): string
    {
        return ucwords(strtolower(
            (new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($amount)
        )) . ' Taka Only';
    }
}
