<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
        Hieronder zie je alle medewerkers die vandaag goedgekeurd verlof hebben (start ≤ vandaag ≤ einde).
    </p>

    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="1"
    />
</x-filament-panels::page>
