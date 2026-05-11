<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
        Genereer in één klik een Excel- of CSV-rapport over de hele organisatie. Exports lopen via een
        achtergrond-queue en verschijnen onder <strong>Notificaties</strong> zodra ze klaar zijn om te downloaden.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($sections as $section)
            <x-filament::section
                :icon="$section['icon']"
                :heading="$section['title']"
                :description="$section['count_label']"
                icon-color="primary"
            >
                <div class="flex flex-col gap-4 h-full">
                    <p class="text-sm text-gray-600 dark:text-gray-300 flex-1">
                        {{ $section['description'] }}
                    </p>
                    <div>
                        {{ $this->{$section['action'] . 'Action'} }}
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
