<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-medium">Stock Summary</p>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Items:</span>
                    <span class="text-lg font-semibold text-gray-900">{{ number_format($totalItems) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Low Stock:</span>
                    <span class="text-lg font-semibold text-orange-600">{{ number_format($lowStockItems) }}</span>
                </div>
            </div>
        </div>
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
        </div>
    </div>
</div>
