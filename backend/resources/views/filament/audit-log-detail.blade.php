@php
    $properties = is_array($record->properties) ? $record->properties : ($record->properties?->toArray() ?? []);
    $attributes = $properties['attributes'] ?? [];
    $old = $properties['old'] ?? [];
    $changedKeys = array_unique(array_merge(array_keys($attributes), array_keys($old)));
@endphp

<div class="space-y-4 text-sm">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
        <dt class="text-gray-500">Wanneer</dt>
        <dd>{{ $record->created_at?->format('d-m-Y H:i:s') }}</dd>

        <dt class="text-gray-500">Door</dt>
        <dd>{{ $record->causer?->name ?? 'systeem' }}</dd>

        <dt class="text-gray-500">Actie</dt>
        <dd>
            <span @class([
                'text-xs px-2 py-0.5 rounded',
                'bg-green-100 text-green-800' => $record->event === 'created',
                'bg-amber-100 text-amber-800' => $record->event === 'updated',
                'bg-red-100 text-red-800' => $record->event === 'deleted',
                'bg-gray-100 text-gray-800' => ! in_array($record->event, ['created', 'updated', 'deleted']),
            ])>{{ $record->event }}</span>
        </dd>

        <dt class="text-gray-500">Object</dt>
        <dd>{{ $record->subject_type ? class_basename($record->subject_type) : '—' }} #{{ $record->subject_id }}</dd>

        @if ($record->description)
            <dt class="text-gray-500">Beschrijving</dt>
            <dd>{{ $record->description }}</dd>
        @endif
    </dl>

    @if ($changedKeys)
        <div>
            <h3 class="font-semibold text-gray-700 mb-2">Wijzigingen</h3>
            <table class="w-full border border-gray-200 rounded">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Veld</th>
                        <th class="px-3 py-2 text-left">Oud</th>
                        <th class="px-3 py-2 text-left">Nieuw</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($changedKeys as $key)
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs">{{ $key }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ is_scalar($old[$key] ?? null) ? ($old[$key] ?? '—') : json_encode($old[$key] ?? null) }}</td>
                            <td class="px-3 py-2">{{ is_scalar($attributes[$key] ?? null) ? ($attributes[$key] ?? '—') : json_encode($attributes[$key] ?? null) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
