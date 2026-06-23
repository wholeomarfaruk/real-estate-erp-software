<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class TransactionReferenceFormatter
{
    private static array $config = [];

    public static function initialize(): void
    {
        self::$config = config('account_references', []);
    }

    /**
     * Resolve reference to model and return formatted display data
     */
    public static function resolve(?string $type, ?int $id, ?string $refNo): ?array
    {
        // If no type but has refNo, show it
        if (!$type && $refNo) {
            return ['label' => $refNo, 'details' => null];
        }

        // If no type and id, return null (nothing to show)
        if (!$type || !$id) {
            return null;
        }

        self::initialize();

        // Extract key from full qualified class name if needed
        $configKey = self::getConfigKeyForType($type);

        // Type not in config - return generic format
        if (!isset(self::$config[$configKey])) {
            $displayType = self::getDisplayName($type);
            return [
                'label' => $displayType,
                'details' => "#{$id}",
                'icon' => '🔗',
            ];
        }

        $config = self::$config[$configKey];
        $modelClass = $config['model'];

        try {
            $model = $modelClass::find($id);

            if (!$model) {
                $displayType = $config['label'] ?? self::getDisplayName($type);
                return [
                    'label' => $displayType,
                    'details' => "#{$id} (not found)",
                    'icon' => '⚠️',
                ];
            }

            return self::formatModel($model, $configKey, $config);
        } catch (\Exception $e) {
            $displayType = $config['label'] ?? self::getDisplayName($type);
            return [
                'label' => $displayType,
                'details' => "#{$id} (error loading)",
                'icon' => '❌',
            ];
        }
    }

    /**
     * Convert full qualified class name to config key
     * App\Models\Project => project
     */
    private static function getConfigKeyForType(string $type): string
    {
        // If it's a full class name, extract the model name and convert it to the
        // snake_case config key (App\Models\PaymentSchedule => payment_schedule).
        if (str_contains($type, '\\')) {
            return \Illuminate\Support\Str::snake(class_basename($type));
        }
        return $type;
    }

    /**
     * Get human readable display name for type
     */
    private static function getDisplayName(string $type): string
    {
        $key = self::getConfigKeyForType($type);
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Format model based on its type
     */
    private static function formatModel(Model $model, string $type, array $config): array
    {
        $label = $config['label'] ?? $type;

        return match ($type) {
            'project' => self::formatProject($model, $label),
            'property' => self::formatProperty($model, $label),
            'purchase_order' => self::formatPurchaseOrder($model, $label),
            'stock_receive' => self::formatStockReceive($model, $label),
            'supplier' => self::formatSupplier($model, $label),
            'transaction' => self::formatTransaction($model, $label),
            'payroll' => self::formatPayroll($model, $label),
            'advance_salary' => self::formatAdvanceSalary($model, $label),
            'payment_schedule' => self::formatPaymentSchedule($model, $label),
            default => [
                'label' => "{$label} #{$model->id}",
                'details' => self::getPrimaryAttribute($model),
            ],
        };
    }

    private static function formatProject($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'name' => $model->name ?? '—',
                'code' => $model->code ?? null,
            ],
            'icon' => '📐',
        ];
    }

    private static function formatProperty($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'name' => $model->title ?? $model->name ?? '—',
                'code' => $model->code ?? null,
            ],
            'icon' => '🏠',
        ];
    }

    private static function formatPurchaseOrder($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'po_no' => $model->po_no ?? "PO#{$model->id}",
                'supplier' => $model->supplier?->name ?? '—',
                'amount' => $model->approved_amount ? "৳ " . number_format((float)$model->approved_amount, 2) : null,
            ],
            'icon' => '📦',
        ];
    }

    private static function formatStockReceive($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'reference' => $model->reference_no ?? "SR#{$model->id}",
                'supplier' => $model->supplier?->name ?? '—',
            ],
            'icon' => '📥',
        ];
    }

    private static function formatSupplier($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'name' => $model->name ?? '—',
                'code' => $model->code ?? null,
            ],
            'icon' => '🏢',
        ];
    }

    private static function formatTransaction($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'id' => "TXN#{$model->id}",
                'category' => $model->transactionCategory?->name ?? '—',
            ],
            'icon' => '💳',
        ];
    }

    private static function formatPayroll($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'employee' => $model->employee?->name ?? '—',
                'period' => $model->period ?? '—',
            ],
            'icon' => '💰',
        ];
    }

    private static function formatAdvanceSalary($model, string $label): array
    {
        return [
            'label' => "{$label}",
            'details' => [
                'employee' => $model->employee?->name ?? '—',
                'amount' => "৳ " . number_format((float)($model->amount ?? 0), 2),
                'remaining' => $model->remaining_amount ? "৳ " . number_format((float)$model->remaining_amount, 2) : null,
            ],
            'icon' => '💸',
        ];
    }

    private static function formatPaymentSchedule($model, string $label): array
    {
        $sale     = $model->propertySale;
        $customer = $sale?->customer;
        $property = $sale?->property;

        $details = [
            'schedule' => method_exists($model, 'label') ? $model->label() : ('#' . $model->id),
            'sale'     => $sale?->sale_number ?? ($sale ? ('Sale #' . $sale->id) : null),
            'customer' => $customer?->name ?? null,
            'property' => $property?->title ?? $property?->name ?? null,
            'amount'   => isset($model->amount) ? '৳ ' . number_format((float) $model->amount, 2) : null,
        ];

        // Link straight to the property sale (opened in a new tab by the card).
        $url = null;
        if ($sale && \Illuminate\Support\Facades\Route::has('admin.properties.sales.show')) {
            $url = route('admin.properties.sales.show', $sale->id);
        }

        return [
            'label'   => $label,
            'details' => $details,
            'icon'    => '🏠',
            'url'     => $url,
            'url_label' => 'Open sale',
        ];
    }

    private static function getPrimaryAttribute($model): ?string
    {
        $attrs = ['name', 'title', 'code', 'display_name', 'reference_no'];
        foreach ($attrs as $attr) {
            if (isset($model->$attr) && $model->$attr) {
                return (string) $model->$attr;
            }
        }
        return null;
    }
}
