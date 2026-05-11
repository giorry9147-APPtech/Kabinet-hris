<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Deadline-radar — wat verloopt binnenkort?
        </x-slot>

        <x-slot name="description">
            Certificaten, contracten en resoluties die binnen 180 dagen vervallen of reeds verlopen zijn.
            Totaal in zicht: <span class="font-semibold">{{ $totalCount }}</span>.
        </x-slot>

        @if ($rows->isEmpty())
            <div class="text-sm text-gray-500 italic py-6 text-center">
                Geen deadlines binnen de horizon. Alles in orde.
            </div>
        @else
            <div class="overflow-x-auto -mx-2">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Soort</th>
                            <th class="px-3 py-2 text-left font-medium">Referentie</th>
                            <th class="px-3 py-2 text-left font-medium">Onderwerp / medewerker</th>
                            <th class="px-3 py-2 text-left font-medium">Vervaldatum</th>
                            <th class="px-3 py-2 text-left font-medium">Termijn</th>
                            <th class="px-3 py-2 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                <td class="px-3 py-2.5 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['kind_color'] }}">
                                        {{ $row['kind'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 align-top font-mono text-xs">{{ $row['reference'] }}</td>
                                <td class="px-3 py-2.5 align-top">{{ $row['subject'] }}</td>
                                <td class="px-3 py-2.5 align-top whitespace-nowrap">{{ $row['deadline_formatted'] }}</td>
                                <td class="px-3 py-2.5 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 {{ $row['badge_color'] }}">
                                        {{ $row['days_label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 align-top text-right">
                                    <a href="{{ $row['url'] }}"
                                       class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-700 hover:underline">
                                        Open
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($totalCount > $rows->count())
                <div class="text-xs text-gray-500 mt-3 text-right">
                    Eerste {{ $rows->count() }} van {{ $totalCount }} getoond — open de afzonderlijke overzichten voor de rest.
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
