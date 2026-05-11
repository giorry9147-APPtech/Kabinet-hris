<div class="px-4 py-3 bg-gradient-to-r from-mas-blue/5 to-transparent border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-6 text-sm">
    <div class="flex items-center gap-2">
        <span class="text-gray-500 dark:text-gray-400">Totaal dienstverbanden:</span>
        <span class="font-semibold text-gray-900 dark:text-white text-base">{{ number_format($total, 0, ',', '.') }}</span>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-gray-500 dark:text-gray-400">Actief:</span>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
            {{ number_format($active, 0, ',', '.') }}
        </span>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-gray-500 dark:text-gray-400">Beëindigd:</span>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            {{ number_format($total - $active, 0, ',', '.') }}
        </span>
    </div>
</div>
