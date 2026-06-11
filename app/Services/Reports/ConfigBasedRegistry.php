<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

class ConfigBasedRegistry
{
    private ?Collection $reports = null;

    /**
     * Get all reports grouped by category for navigation
     */
    public function getCategorized(): array
    {
        $config = config('reports');
        $result = [];

        foreach ($config as $categoryKey => $categoryData) {
            $categoryResult = [
                'key' => $categoryKey,
                'name' => $categoryData['name'],
                'desc' => $categoryData['description'],
                'icon' => $categoryData['icon'],
                'items' => [],
            ];

            foreach ($categoryData['reports'] as $reportSlug => $reportData) {
                $categoryResult['items'][] = [
                    'name' => $reportData['title'],
                    'desc' => $reportData['description'],
                    'route' => route('admin.reports.' . $categoryKey . '.' . str_replace('_', '-', $reportSlug)),
                ];
            }

            $result[$categoryKey] = $categoryResult;
        }

        return $result;
    }

    /**
     * Get slug => service class mapping for export controller
     */
    public function getServiceMap(): array
    {
        $config = config('reports');
        $map = [];

        foreach ($config as $categoryData) {
            foreach ($categoryData['reports'] as $slug => $reportData) {
                $map[$slug] = $reportData['service'];
            }
        }

        return $map;
    }

    /**
     * Get report definition by slug
     */
    public function find(string $slug): ?array
    {
        $config = config('reports');

        foreach ($config as $categoryData) {
            if (isset($categoryData['reports'][$slug])) {
                return array_merge(
                    $categoryData['reports'][$slug],
                    ['slug' => $slug]
                );
            }
        }

        return null;
    }

    /**
     * Get all reports as flat collection
     */
    public function all(): Collection
    {
        if ($this->reports !== null) {
            return $this->reports;
        }

        $config = config('reports');
        $reports = collect();

        foreach ($config as $categoryKey => $categoryData) {
            foreach ($categoryData['reports'] as $slug => $reportData) {
                $reports->push(array_merge(
                    $reportData,
                    [
                        'slug' => $slug,
                        'category' => $categoryKey,
                    ]
                ));
            }
        }

        return $this->reports = $reports;
    }

    /**
     * Get service class for a report
     */
    public function getServiceClass(string $slug): ?string
    {
        $report = $this->find($slug);
        return $report ? $report['service'] : null;
    }

    /**
     * Get component class for a report
     */
    public function getComponentClass(string $slug): ?string
    {
        $report = $this->find($slug);
        return $report ? $report['component'] : null;
    }

    /**
     * Get view path for a report
     */
    public function getViewPath(string $slug): ?string
    {
        $report = $this->find($slug);
        return $report ? $report['view'] : null;
    }

    /**
     * Get permission for a report
     */
    public function getPermission(string $slug): ?string
    {
        $report = $this->find($slug);
        return $report ? $report['permission'] : null;
    }

    /**
     * Count total reports
     */
    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * Check if report exists
     */
    public function exists(string $slug): bool
    {
        return $this->find($slug) !== null;
    }
}
