<?php

use function Livewire\Volt\{mount, state};

// Componente reutilizable: el anticipo (50% inicial) de un "padre" (Cotización o Proyecto).
// El padre debe exponer las columnas anticipo_monto / anticipo_pagado / anticipo_fecha_pago.
// La referencia (monto sobre el que se calcula el 50% y el saldo) y su etiqueta las define
// quien lo incluye: la cotización usa su total cotizado; el proyecto, el total de la factura.
state([
    'parent' => null,
    'referenciaMonto' => 0,
    'referenciaLabel' => 'el total',
    'anticipo_monto' => 0,
    'anticipo_pagado' => false,
    'anticipo_fecha_pago' => '',
]);

mount(function ($parent, float $referenciaMonto = 0, string $referenciaLabel = 'el total') {
    $this->parent = $parent;
    $this->referenciaMonto = $referenciaMonto;
    $this->referenciaLabel = $referenciaLabel;
    $this->anticipo_monto = (float) ($parent->anticipo_monto ?? 0);
    $this->anticipo_pagado = (bool) $parent->anticipo_pagado;
    $this->anticipo_fecha_pago = $parent->anticipo_fecha_pago?->toDateString() ?? '';
});

$sugerirMitad = function () {
    if ($this->referenciaMonto > 0) {
        $this->anticipo_monto = round($this->referenciaMonto / 2);
    }
};

$guardar = function () {
    $datos = $this->validate([
        'anticipo_monto' => ['required', 'numeric', 'min:0'],
        'anticipo_fecha_pago' => ['nullable', 'date'],
    ]);

    // Si se marca pagado sin fecha, se asume hoy; si no está pagado, se limpia la fecha.
    $this->parent->update([
        'anticipo_monto' => $datos['anticipo_monto'],
        'anticipo_pagado' => (bool) $this->anticipo_pagado,
        'anticipo_fecha_pago' => $this->anticipo_pagado
            ? ($datos['anticipo_fecha_pago'] ?: today()->toDateString())
            : null,
    ]);

    $this->parent->refresh();
    $this->anticipo_fecha_pago = $this->parent->anticipo_fecha_pago?->toDateString() ?? '';

    session()->flash('status', 'Anticipo guardado.');
};

$eliminar = function () {
    $this->parent->update([
        'anticipo_monto' => null,
        'anticipo_pagado' => false,
        'anticipo_fecha_pago' => null,
    ]);
    $this->parent->refresh();

    $this->anticipo_monto = 0;
    $this->anticipo_pagado = false;
    $this->anticipo_fecha_pago = '';

    session()->flash('status', 'Anticipo eliminado.');
};

?>

@php
    $anticipoRegistrado = (float) ($parent->anticipo_monto ?? 0);
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Anticipo para iniciar (50%)</h2>
        <div class="flex items-center gap-2">
            @if ($parent->anticipo_pagado)
                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                    Pagado{{ $parent->anticipo_fecha_pago ? ' · '.$parent->anticipo_fecha_pago->format('d-m-Y') : '' }}
                </span>
            @elseif ($anticipoRegistrado > 0)
                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">Por cobrar</span>
            @else
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Pendiente</span>
            @endif
        </div>
    </div>

    <p class="mt-1 text-xs text-gray-500">
        Pago inicial que solicita Leufu Sur para comprar insumos y comenzar los trabajos.
    </p>

    <form wire:submit="guardar" class="mt-4">
        <div class="sm:w-72">
            <x-input-label for="anticipo_monto" value="Monto del anticipo" />
            <input id="anticipo_monto" type="number" step="0.01" min="0" wire:model="anticipo_monto"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
            <x-input-error :messages="$errors->get('anticipo_monto')" class="mt-2" />
            @if ($referenciaMonto > 0)
                <p class="mt-2 text-xs text-gray-500">
                    50% de {{ $referenciaLabel }} (${{ number_format($referenciaMonto, 0, ',', '.') }}) =
                    ${{ number_format(round($referenciaMonto / 2), 0, ',', '.') }}
                    <button type="button" wire:click="sugerirMitad" class="ml-1 font-medium text-teal-600 hover:text-teal-500">Usar 50%</button>
                </p>
            @endif
        </div>

        {{-- Estado de pago del anticipo --}}
        <div class="mt-4 rounded-md bg-gray-50 p-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="checkbox" wire:model.live="anticipo_pagado" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                Anticipo recibido (el cliente ya lo pagó)
            </label>
            @if ($anticipo_pagado)
                <div class="mt-3 sm:w-56">
                    <x-input-label for="anticipo_fecha_pago" value="Fecha de pago" />
                    <x-date-picker id="anticipo_fecha_pago" model="anticipo_fecha_pago" class="mt-1" />
                    <x-input-error :messages="$errors->get('anticipo_fecha_pago')" class="mt-2" />
                </div>
            @endif
        </div>

        @if ($anticipoRegistrado > 0 && $referenciaMonto > 0)
            <p class="mt-4 text-sm text-gray-500">
                Saldo por cobrar (tras anticipo):
                <span class="font-semibold text-gray-900">${{ number_format(max($referenciaMonto - $anticipoRegistrado, 0), 0, ',', '.') }}</span>
            </p>
        @endif

        <div class="mt-6 flex items-center justify-end gap-3">
            @if ($anticipoRegistrado > 0)
                <button type="button" wire:click="eliminar"
                    wire:confirm="¿Quitar el anticipo?"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                    Quitar
                </button>
            @endif
            <x-primary-button>
                {{ $anticipoRegistrado > 0 ? 'Guardar cambios' : 'Registrar anticipo' }}
            </x-primary-button>
        </div>
    </form>
</div>
