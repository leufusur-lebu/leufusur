<?php

use App\Enums\EstadoProyecto;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.gestion');

state([
    'proyecto' => null,
    'anticipo_monto' => 0,
    'anticipo_pagado' => false,
    'anticipo_fecha_pago' => '',
]);

mount(function (Proyecto $proyecto) {
    $this->proyecto = $proyecto->load('cliente', 'gastos', 'facturaVenta');
    $this->anticipo_monto = (float) ($proyecto->anticipo_monto ?? 0);
    $this->anticipo_pagado = (bool) $proyecto->anticipo_pagado;
    $this->anticipo_fecha_pago = $proyecto->anticipo_fecha_pago?->toDateString() ?? '';
});

$cambiarEstado = function (string $estado) {
    $this->proyecto->update(['estado' => $estado]);
    $this->proyecto->refresh();
    Session::flash('status', 'Estado actualizado a "'.$this->proyecto->estado->label().'".');
};

$sugerirMitad = function () {
    if ($total = (float) ($this->proyecto->facturaVenta?->total_calculado ?? 0)) {
        $this->anticipo_monto = round($total / 2);
    }
};

$guardarAnticipo = function () {
    $datos = $this->validate([
        'anticipo_monto' => ['required', 'numeric', 'min:0'],
        'anticipo_fecha_pago' => ['nullable', 'date'],
    ]);

    // Si se marca pagado sin fecha, se asume hoy; si no está pagado, se limpia la fecha.
    $this->proyecto->update([
        'anticipo_monto' => $datos['anticipo_monto'],
        'anticipo_pagado' => (bool) $this->anticipo_pagado,
        'anticipo_fecha_pago' => $this->anticipo_pagado
            ? ($datos['anticipo_fecha_pago'] ?: today()->toDateString())
            : null,
    ]);

    $this->proyecto->refresh();
    $this->anticipo_fecha_pago = $this->proyecto->anticipo_fecha_pago?->toDateString() ?? '';

    Session::flash('status', 'Anticipo guardado.');
};

$eliminarAnticipo = function () {
    $this->proyecto->update([
        'anticipo_monto' => null,
        'anticipo_pagado' => false,
        'anticipo_fecha_pago' => null,
    ]);
    $this->proyecto->refresh();

    $this->anticipo_monto = 0;
    $this->anticipo_pagado = false;
    $this->anticipo_fecha_pago = '';

    Session::flash('status', 'Anticipo eliminado.');
};

$eliminar = function () {
    $nombre = $this->proyecto->nombre;
    $this->proyecto->delete();

    Session::flash('status', 'Proyecto "'.$nombre.'" eliminado.');
    $this->redirectRoute('gestion.proyectos.index', navigate: true);
};

?>

<div>
    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Encabezado y acciones --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-lg font-semibold text-gray-900">{{ $proyecto->nombre }}</h1>
                            <span
                                @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-blue-50 text-blue-700' => $proyecto->estado === EstadoProyecto::Activo,
                                    'bg-green-50 text-green-700' => $proyecto->estado === EstadoProyecto::Facturado,
                                    'bg-gray-100 text-gray-700' => $proyecto->estado === EstadoProyecto::Cerrado,
                                ])>
                                {{ $proyecto->estado->label() }}
                            </span>
                        </div>
                        @if ($proyecto->descripcion)
                            <p class="mt-1 text-sm text-gray-500">{{ $proyecto->descripcion }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($proyecto->estado === EstadoProyecto::Activo)
                            <button wire:click="cambiarEstado('facturado')"
                                class="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
                                Marcar como facturado
                            </button>
                        @elseif ($proyecto->estado === EstadoProyecto::Facturado)
                            <button wire:click="cambiarEstado('activo')"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Reabrir
                            </button>
                            <button wire:click="cambiarEstado('cerrado')"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Cerrar proyecto
                            </button>
                        @else
                            <button wire:click="cambiarEstado('activo')"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Reabrir
                            </button>
                        @endif

                        <a href="{{ route('gestion.proyectos.edit', $proyecto) }}" wire:navigate
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Editar
                        </a>

                        <button wire:click="eliminar"
                            wire:confirm="¿Eliminar este proyecto? Se borrarán también sus gastos. Esta acción no se puede deshacer."
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cliente e info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-900">Cliente</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900">{{ $proyecto->cliente->nombre }}</dd></div>
                    @if ($proyecto->cliente->nombre_empresa)
                        <div><dt class="text-gray-500">Empresa</dt><dd class="text-gray-900">{{ $proyecto->cliente->nombre_empresa }}</dd></div>
                    @endif
                    @if ($proyecto->cliente->email)
                        <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $proyecto->cliente->email }}</dd></div>
                    @endif
                    @if ($proyecto->cliente->telefono)
                        <div><dt class="text-gray-500">Teléfono</dt><dd class="text-gray-900">{{ $proyecto->cliente->telefono }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500">RUT</dt><dd class="text-gray-900">{{ $proyecto->cliente->rut_run }}</dd></div>
                    <div><dt class="text-gray-500">Fecha de inicio</dt><dd class="text-gray-900">{{ $proyecto->fecha_inicio->format('d-m-Y') }}</dd></div>
                </dl>
            </div>

            {{-- Anticipo (50% inicial para comenzar los trabajos) --}}
            @php
                $anticipoRegistrado = (float) ($proyecto->anticipo_monto ?? 0);
                $totalFactura = (float) ($proyecto->facturaVenta?->total_calculado ?? 0);
            @endphp
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Anticipo para iniciar (50%)</h2>
                    <div class="flex items-center gap-2">
                        @if ($proyecto->anticipo_pagado)
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                Pagado{{ $proyecto->anticipo_fecha_pago ? ' · '.$proyecto->anticipo_fecha_pago->format('d-m-Y') : '' }}
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

                <form wire:submit="guardarAnticipo" class="mt-4">
                    <div class="sm:w-72">
                        <x-input-label for="anticipo_monto" value="Monto del anticipo" />
                        <input id="anticipo_monto" type="number" step="0.01" min="0" wire:model="anticipo_monto"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('anticipo_monto')" class="mt-2" />
                        @if ($totalFactura > 0)
                            <p class="mt-2 text-xs text-gray-500">
                                50% de la factura (${{ number_format($totalFactura, 0, ',', '.') }}) =
                                ${{ number_format(round($totalFactura / 2), 0, ',', '.') }}
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

                    @if ($anticipoRegistrado > 0 && $totalFactura > 0)
                        <p class="mt-4 text-sm text-gray-500">
                            Saldo por cobrar (tras anticipo):
                            <span class="font-semibold text-gray-900">${{ number_format(max($totalFactura - $anticipoRegistrado, 0), 0, ',', '.') }}</span>
                        </p>
                    @endif

                    <div class="mt-6 flex items-center justify-end gap-3">
                        @if ($anticipoRegistrado > 0)
                            <button type="button" wire:click="eliminarAnticipo"
                                wire:confirm="¿Quitar el anticipo de este proyecto?"
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

            {{-- Factura de venta (reutiliza el componente compartido) --}}
            <livewire:gestion.cotizaciones.factura-venta :parent="$proyecto" :key="'factura-venta-proyecto-'.$proyecto->id" />

            {{-- Gastos de insumos/materiales (reutiliza el componente compartido) --}}
            <livewire:gestion.cotizaciones.gastos :parent="$proyecto"
                :referencia-monto="(float) ($proyecto->facturaVenta?->total_calculado ?? 0)"
                referencia-label="Margen vs. factura" :key="'gastos-proyecto-'.$proyecto->id" />

            {{-- Bitácora de actividades --}}
            <livewire:gestion.proyectos.bitacora :proyecto="$proyecto" :key="'bitacora-'.$proyecto->id" />

            <a href="{{ route('gestion.proyectos.index') }}" wire:navigate class="inline-block text-sm text-gray-600 hover:text-gray-900">
                ← Volver a proyectos
            </a>
        </div>
    </div>
</div>
