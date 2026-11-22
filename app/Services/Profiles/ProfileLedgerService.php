<?php

namespace App\Services\Profiles;

use App\Enums\Accounts\ReportGroup;
use App\Enums\Accounts\TransactionType;
use App\Models\MasterProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A master profile's money position, read straight from the ledger
 * (transactions tagged with master_profile_id).
 *
 *   Received = entries whose type is a receipt  (money in from this party)
 *   Paid     = entries whose type is a payment  (money out to this party)
 *   Net      = Received − Paid
 * Neutral entries (transfer, adjustment…) are listed but not counted.
 */
class ProfileLedgerService
{
    /** Amount of each transaction = its total debit. */
    private function baseQuery(int $profileId)
    {
        return DB::table('transactions as t')
            ->join('transaction_lines as l', 'l.transaction_id', '=', 't.id')
            ->where('t.master_profile_id', $profileId)
            ->groupBy('t.id', 't.datetime', 't.type', 't.voucher_no', 't.reference_no', 't.notes', 't.method', 't.reference_type', 't.reference_id')
            ->select('t.id', 't.datetime', 't.type', 't.voucher_no', 't.reference_no', 't.notes', 't.method', 't.reference_type', 't.reference_id', DB::raw('SUM(l.debit) as amount'));
    }

    public static function direction(?string $type): string
    {
        return match (TransactionType::tryFrom((string) $type)?->reportGroup()) {
            ReportGroup::RECEIPT => 'in',
            ReportGroup::PAYMENT => 'out',
            default              => 'neutral',
        };
    }

    /**
     * @return array{received: float, paid: float, net: float, count: int, last_at: ?string}
     */
    public function summary(MasterProfile $profile): array
    {
        $rows = DB::query()->fromSub($this->baseQuery($profile->id), 'x')->get(['type', 'amount', 'datetime']);

        $received = $paid = 0.0;
        foreach ($rows as $row) {
            match (self::direction($row->type)) {
                'in'    => $received += (float) $row->amount,
                'out'   => $paid += (float) $row->amount,
                default => null,
            };
        }

        return [
            'received' => round($received, 2),
            'paid'     => round($paid, 2),
            'net'      => round($received - $paid, 2),
            'count'    => $rows->count(),
            'last_at'  => $rows->max('datetime'),
        ];
    }

    /**
     * Received / paid / net per profile in one query — a subquery of
     * per-profile totals, ready to join onto master_profiles.
     *
     * Columns: master_profile_id, received, paid, net, entries, last_at
     */
    public function balancesQuery()
    {
        $receipts = TransactionType::receipts();
        $payments = TransactionType::payments();

        $in  = $receipts ? "CASE WHEN t.type IN ('" . implode("','", $receipts) . "') THEN l.debit ELSE 0 END" : '0';
        $out = $payments ? "CASE WHEN t.type IN ('" . implode("','", $payments) . "') THEN l.debit ELSE 0 END" : '0';

        return DB::table('transactions as t')
            ->join('transaction_lines as l', 'l.transaction_id', '=', 't.id')
            ->whereNotNull('t.master_profile_id')
            ->groupBy('t.master_profile_id')
            ->select([
                't.master_profile_id',
                DB::raw("ROUND(SUM($in), 2) as received"),
                DB::raw("ROUND(SUM($out), 2) as paid"),
                DB::raw("ROUND(SUM($in) - SUM($out), 2) as net"),
                DB::raw('COUNT(DISTINCT t.id) as entries'),
                DB::raw('MAX(t.datetime) as last_at'),
            ]);
    }

    /**
     * Balances for the given profile ids.
     *
     * @return Collection<int, object{received: float, paid: float, net: float, entries: int}>  keyed by profile id
     */
    public function balancesFor(iterable $profileIds): Collection
    {
        $ids = collect($profileIds)->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::query()->fromSub($this->balancesQuery(), 'b')
            ->whereIn('master_profile_id', $ids)
            ->get()
            ->keyBy('master_profile_id');
    }

    /**
     * The profile's entries split by role: each entry goes to the role whose
     * linked record it references (installment → Customer, purchase invoice →
     * Supplier, payroll → Employee…). Entries with no such record land in "other".
     *
     * @return array{roles: array<int, array>, other: array}
     *   each bucket: received, paid, net, count, entries (newest first)
     */
    public function byRole(MasterProfile $profile): array
    {
        $resolver = app(ProfileTransactionResolver::class);
        $roleByRecord = $profile->roles()
            ->whereNotNull('linkable_type')
            ->get(['id', 'linkable_type', 'linkable_id'])
            ->mapWithKeys(fn ($r) => [$r->linkable_type . ':' . $r->linkable_id => $r->id]);

        $empty = fn () => ['received' => 0.0, 'paid' => 0.0, 'net' => 0.0, 'count' => 0, 'entries' => collect()];
        $buckets = ['roles' => [], 'other' => $empty()];

        $entries = $this->entries($profile, PHP_INT_MAX);

        foreach ($entries as $entry) {
            $record = $entry->reference_type === 'banking_payment_request'
                ? $this->requestRecord($resolver, (int) $entry->reference_id)
                : $resolver->moduleRecordFor($entry->reference_type, $entry->reference_id ? (int) $entry->reference_id : null);

            $roleId = $record ? ($roleByRecord[$record[0] . ':' . $record[1]] ?? null) : null;

            if ($roleId) {
                $buckets['roles'][$roleId] ??= $empty();
                $bucket = &$buckets['roles'][$roleId];
            } else {
                $bucket = &$buckets['other'];
            }

            match ($entry->direction) {
                'in'    => $bucket['received'] += (float) $entry->amount,
                'out'   => $bucket['paid'] += (float) $entry->amount,
                default => null,
            };
            $bucket['count']++;
            $bucket['entries']->push($entry);
            unset($bucket);
        }

        foreach ($buckets['roles'] as &$b) {
            $b['net'] = round($b['received'] - $b['paid'], 2);
        }
        unset($b);
        $buckets['other']['net'] = round($buckets['other']['received'] - $buckets['other']['paid'], 2);

        return $buckets;
    }

    /** Module record behind a payment request (its source record), if any. */
    private function requestRecord(ProfileTransactionResolver $resolver, int $requestId): ?array
    {
        $req = DB::table('banking_payment_requests')->where('id', $requestId)->first(['sourceable_type', 'sourceable_id']);

        return $req ? $resolver->moduleRecordFor($req->sourceable_type, $req->sourceable_id ? (int) $req->sourceable_id : null) : null;
    }

    /** Latest entries, newest first. */
    public function entries(MasterProfile $profile, int $limit = 10): Collection
    {
        return $this->baseQuery($profile->id)
            ->orderByDesc('t.datetime')->orderByDesc('t.id')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $type = TransactionType::tryFrom((string) $row->type);
                $row->direction = self::direction($row->type);
                $row->type_label = $type?->label() ?? ucfirst(str_replace('_', ' ', (string) $row->type));
                $row->badge = $type?->badgeClass() ?? 'bg-gray-100 text-gray-600';

                return $row;
            });
    }
}
