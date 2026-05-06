<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-medium">Total Expense</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
                {{ number_format($totalExpense, 2) }}
            </p>
        </div>
        <div class="bg-red-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </div>
</div>
