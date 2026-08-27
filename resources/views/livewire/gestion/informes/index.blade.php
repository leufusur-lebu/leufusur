<?php

use App\Http\Controllers\Gestion\InformeController;

use function Livewire\Volt\{computed, layout, state};

layout('layouts.gestion');

state([
    'reporte' => 'facturas-emitidas',
    'desde' => '',
    'hasta' => '',
]);

$datos = computed(fn () => InformeController::datos(
    $this->reporte,
    $this->desde ?: null,
    $this->hasta ?: null,
));

$reportes = computed(fn () => InformeController::REPORTES);

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Informes</h1>
                <p class="mt-1 text-sm text-gray-500">Consulta y descarga tus reportes en Excel o PDF.</p>
            </div>

            {{-- Selector de informe --}}
            <div class="flex flex-wrap gap-2">
                @foreach ($this->reportes as $clave => $etiqueta)
                    <button type="button" wire:click="$set('reporte', '{{ $clave }}')"
                        @class([
                            'rounded-full px-3 py-1.5 text-sm font-medium',
                            'bg-teal-600 text-white' => $reporte === $clave,
                            'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' => $reporte !== $clave,
                        ])>{{ $etiqueta }}</button>
                @endforeach
            </div>

            {{-- Filtros y descargas --}}
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div>
                            <x-input-label for="desde" value="Desde" />
                            <x-date-picker id="desde" model="desde" class="mt-1 sm:w-44" />
                        </div>
                        <div>
                            <x-input-label for="hasta" value="Hasta" />
                            <x-date-picker id="hasta" model="hasta" class="mt-1 sm:w-44" />
                        </div>
                        @if ($desde || $hasta)
                            <button type="button" wire:click="$set('desde', ''); $set('hasta', '')"
                                class="self-end text-sm text-gray-500 hover:text-gray-700">Limpiar</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('gestion.informes.export', ['reporte' => $reporte, 'formato' => 'csv', 'desde' => $desde, 'hasta' => $hasta]) }}"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            ⬇ Excel
                        </a>
                        <a href="{{ route('gestion.informes.export', ['reporte' => $reporte, 'formato' => 'pdf', 'desde' => $desde, 'hasta' => $hasta]) }}" target="_blank"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            ⬇ PDF
                        </a>
                    </div>
                </div>
            </div>

            {{-- Resultado --}}
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900">{{ $this->reportes[$reporte] }}</h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                @foreach ($this->datos['columnas'] as $i => $columna)
                                    <th @class([
                                        'py-2 px-3 text-xs font-medium uppercase tracking-wide text-gray-500',
                                        'text-right' => isset($this->datos['filas'][0][$i]) && str_starts_with((string) $this->datos['filas'][0][$i], '$'),
                                        'text-left' => ! (isset($this->datos['filas'][0][$i]) && str_starts_with((string) $this->datos['filas'][0][$i], '$')),
                                    ])>{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->datos['filas'] as $fila)
                                <tr wire:key="fila-{{ $loop->index }}">
                                    @foreach ($fila as $valor)
                                        <td @class([
                                            'px-3 py-2 text-sm',
                                            'text-right text-gray-900' => str_starts_with((string) $valor, '$'),
                                            'text-gray-600' => ! str_starts_with((string) $valor, '$'),
                                        ])>{{ $valor }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(1, count($this->datos['columnas'])) }}" class="py-8 text-center text-sm text-gray-500">
                                        No hay datos para el período seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (! empty($this->datos['totales']) && count($this->datos['filas']) > 0)
                    <div class="mt-4 ml-auto max-w-sm space-y-1">
                        @foreach ($this->datos['totales'] as $etiqueta => $valor)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $etiqueta }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
