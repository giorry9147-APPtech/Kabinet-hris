<x-filament-panels::page>
    <div class="space-y-6 mas-organogram-page">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 mas-no-print">
            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background: #1F5E3A"></span> Directie
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background: #194a2e"></span> Afdeling
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background: #408a5e"></span> Dienst
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background: #97c6a8"></span> Sectie
                </span>
            </div>
            <button type="button" onclick="window.print()"
                    class="bg-[#1F5E3A] hover:bg-[#133722] text-white text-sm font-medium px-4 py-2 rounded-md flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Afdrukken / PDF
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4 mas-no-print">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-xs uppercase tracking-wide text-gray-500">Eenheden</div>
                <div class="text-2xl font-bold text-[#1F5E3A] mt-1">{{ $totals['units'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-xs uppercase tracking-wide text-gray-500">Functies</div>
                <div class="text-2xl font-bold text-[#1F5E3A] mt-1">{{ $totals['positions'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-xs uppercase tracking-wide text-gray-500">Bezet (actief)</div>
                <div class="text-2xl font-bold text-[#1F5E3A] mt-1">{{ $totals['employees'] }}</div>
            </div>
        </div>

        {{-- Print header (only visible when printing) --}}
        <div class="mas-print-only">
            <div class="mb-4">
                <div class="text-xs uppercase tracking-widest text-[#1F5E3A] font-semibold">Kabinet van de President</div>
                <h1 class="text-2xl font-bold text-[#1F5E3A] mt-1">Organogram Kabinet</h1>
                <div class="italic text-[#D4A017] text-sm">Republiek Suriname</div>
                <div class="text-xs text-gray-500 mt-2">Gegenereerd op {{ now()->format('d-m-Y H:i') }} — {{ $totals['units'] }} eenheden, {{ $totals['positions'] }} functies, {{ $totals['employees'] }} actief bezet</div>
            </div>
        </div>

        {{-- List --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mas-tree-wrap">
            @if ($tree)
                @include('filament.pages.partials.org-node', ['node' => $tree, 'depth' => 0])
            @else
                <p class="text-gray-500">Geen organogram beschikbaar.</p>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        .mas-org-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 4px;
            transition: background 120ms ease;
        }
        .mas-org-row:hover { background: #eef2f7; }
        .dark .mas-org-row { background: #1e293b; border-color: #334155; }
        .dark .mas-org-row:hover { background: #273449; }

        .mas-org-band {
            width: 4px;
            align-self: stretch;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .mas-org-name {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1 1 auto;
            min-width: 0;
        }
        .mas-org-type {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .mas-org-titles { min-width: 0; flex: 1; }
        .mas-org-unitname {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .mas-org-unitname { color: #f1f5f9; }
        .mas-org-code {
            font-size: 10px;
            font-family: ui-monospace, monospace;
            color: #64748b;
            margin-top: 1px;
        }

        .mas-org-head {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex-shrink: 0;
            max-width: 280px;
        }
        .mas-org-head-empty {
            font-size: 11px;
            color: #94a3b8;
            font-style: italic;
        }
        .mas-org-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #1F5E3A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }
        .mas-org-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .mas-org-headinfo { min-width: 0; }
        .mas-org-headname {
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .mas-org-headname { color: #e2e8f0; }
        .mas-org-headtitle {
            font-size: 10px;
            color: #64748b;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mas-org-stats {
            font-size: 11px;
            color: #475569;
            background: white;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .mas-org-stats strong { color: #1F5E3A; font-weight: 700; }
        .mas-org-stats-empty {
            background: transparent;
            border-color: transparent;
            color: #94a3b8;
        }
        .dark .mas-org-stats { background: #0f172a; color: #cbd5e1; border-color: #334155; }
        .dark .mas-org-stats strong { color: #93c5fd; }

        @media (max-width: 768px) {
            .mas-org-head { display: none; }
        }

        .mas-print-only { display: none; }

        @media print {
            body * { visibility: hidden !important; }
            .mas-organogram-page, .mas-organogram-page * { visibility: visible !important; }
            .mas-organogram-page { position: absolute; left: 0; top: 0; width: 100%; padding: 8mm; }
            .mas-no-print { display: none !important; }
            .mas-print-only { display: block !important; }
            .mas-tree-wrap {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .mas-org-row {
                page-break-inside: avoid;
                background: white !important;
                border-color: #cbd5e1 !important;
            }
            .mas-org-stats { background: white !important; }
            @page { size: A4 portrait; margin: 8mm; }
        }
    </style>
    @endpush
</x-filament-panels::page>
