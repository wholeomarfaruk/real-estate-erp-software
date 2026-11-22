<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every expense category gets a dynamic Journal Posting item.
 *
 *  - journal_posting_items.expense_category_id links an item to the category it replaces.
 *  - The Project / Office / Marketing items (previous migration) are linked to their
 *    locked categories; every other active category gets a new item with the same
 *    name, description and expense heads (Dr feature accounts / Cr cash & bank).
 *  - The hub hides a linked category and /expenses/new?category=… redirects to the
 *    item's entry form. Deleting the item brings the old category form back.
 */
return new class extends Migration
{
    /** Icons / colours the hub accepts (JournalPostingHub::ICONS / COLORS). */
    private const ICONS = ['folder', 'briefcase', 'building', 'megaphone', 'cog', 'tool', 'box', 'package', 'tag', 'layers'];

    private const LOCKED = ['project' => 'project-expense', 'office' => 'office-expense', 'marketing' => 'marketing-expense'];

    public function up(): void
    {
        if (! Schema::hasColumn('journal_posting_items', 'expense_category_id')) {
            Schema::table('journal_posting_items', function (Blueprint $table) {
                $table->foreignId('expense_category_id')->nullable()->unique()->after('group_name')
                    ->constrained('expense_categories')->nullOnDelete();
            });
        }

        $now = now();
        $groupName = DB::table('journal_posting_groups')->where('key', 'expense')->value('name');
        $features = DB::table('features')->pluck('key')->all();
        $colors = $this->allowedColors();
        $sort = (int) DB::table('journal_posting_items')->where('group_key', 'expense')->max('sort_order');

        $categories = DB::table('expense_categories')->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get();

        foreach ($categories as $category) {
            if (DB::table('journal_posting_items')->where('expense_category_id', $category->id)->exists()) {
                continue;
            }

            // Locked categories: link the item created by the previous migration.
            if (isset(self::LOCKED[$category->slug])) {
                $updated = DB::table('journal_posting_items')
                    ->where('slug', self::LOCKED[$category->slug])
                    ->whereNull('expense_category_id')
                    ->update(['expense_category_id' => $category->id, 'updated_at' => $now]);
                if ($updated) {
                    continue;
                }
            }

            $feature = $category->feature_type && in_array($category->feature_type, $features, true)
                ? $category->feature_type : null;

            DB::table('journal_posting_items')->insert([
                'group_key'           => 'expense',
                'group_name'          => $groupName,
                'expense_category_id' => $category->id,
                'slug'                => $this->uniqueSlug($category->slug),
                'label'               => $category->name,
                'description'         => $category->description ? mb_substr($category->description, 0, 500) : null,
                'icon'                => in_array($category->icon, self::ICONS, true) ? $category->icon : 'folder',
                'color'               => in_array($category->color, $colors, true) ? $category->color : 'bg-gray-50 border-gray-200 text-gray-700',
                'feature_type'        => $feature,
                'debit_source'        => $feature ? 'feature:' . $feature : 'all',
                'credit_source'       => 'cashbank',
                'transaction_type'    => $category->slug === 'office' ? 'office_expense' : 'expense',
                'url'                 => null,
                'field_settings'      => json_encode([
                    'profile' => 'optional', 'party_name' => 'optional', 'party_phone' => 'optional',
                    'reference_no' => 'optional', 'voucher_no' => 'optional', 'notes' => 'optional',
                    'project' => $category->slug === 'project' ? 'required' : 'hidden',
                ]),
                'behaviour'           => json_encode(['requires_approval' => true, 'default_method' => 'cash']),
                'custom_fields'       => json_encode([]),
                'sort_order'          => ++$sort,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('journal_posting_items', 'expense_category_id')) {
            // Items created here (not the three from the previous migration) go too.
            DB::table('journal_posting_items')
                ->whereNotNull('expense_category_id')
                ->whereNotIn('slug', array_values(self::LOCKED))
                ->delete();

            Schema::table('journal_posting_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('expense_category_id');
            });
        }
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        foreach ([$base, $base . '-expense'] as $candidate) {
            if (! DB::table('journal_posting_items')->where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }
        while (DB::table('journal_posting_items')->where('slug', $slug = $base . '-expense-' . $i)->exists()) {
            $i++;
        }

        return $slug;
    }

    private function allowedColors(): array
    {
        return [
            'bg-blue-50 border-blue-200 text-blue-700', 'bg-purple-50 border-purple-200 text-purple-700',
            'bg-orange-50 border-orange-200 text-orange-700', 'bg-green-50 border-green-200 text-green-700',
            'bg-red-50 border-red-200 text-red-700', 'bg-pink-50 border-pink-200 text-pink-700',
            'bg-indigo-50 border-indigo-200 text-indigo-700', 'bg-yellow-50 border-yellow-200 text-yellow-700',
            'bg-gray-50 border-gray-200 text-gray-700',
        ];
    }
};
