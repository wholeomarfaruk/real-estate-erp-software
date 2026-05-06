<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-medium">Project Status</p>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Active:</span>
                    <span class="text-lg font-semibold text-blue-600">{{ number_format($activeProjects) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Completed:</span>
                    <span class="text-lg font-semibold text-green-600">{{ number_format($completedProjects) }}</span>
                </div>
            </div>
        </div>
        <div class="bg-purple-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>
    </div>
</div>
