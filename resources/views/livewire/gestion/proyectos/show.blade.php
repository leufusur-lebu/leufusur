<?php

use App\Enums\EstadoProyecto;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.gestion');

state(['proyecto' => null]);

mount(function (Proyecto $proyecto) {
    $this->proyecto = $proyecto->load('cliente', 'gastos', 'facturaVenta');
});

$cambiarEstado = function (string $estado) {
    $this->proyecto->update(['estado' => $estado]);
    $this->proyecto->refresh();
    Session::flash('status', 'Estado actualizado a "'.$this->proyecto->estado->label().'".');
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

            {{-- Anticipo (50% inicial). El 50% y el saldo se calculan sobre el total de la factura. --}}
            <livewire:gestion.anticipo :parent="$proyecto"
                :referencia-monto="(float) ($proyecto->facturaVenta?->total_calculado ?? 0)"
                referencia-label="la factura" :key="'anticipo-proyecto-'.$proyecto->id" />

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
